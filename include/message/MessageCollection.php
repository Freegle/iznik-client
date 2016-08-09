<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

class MessageCollection
{
    # These match the collection enumeration.
    const INCOMING = 'Incoming';
    const APPROVED = 'Approved';
    const PENDING = 'Pending';
    const SPAM = 'Spam';
    const DRAFT = 'Draft';
    const QUEUED_YAHOO_USER = 'QueuedYahooUser'; # Awaiting a user on the Yahoo group before it can be sent
    const REJECTED = 'Rejected'; # Rejected by mod; user can see and resend.

    /** @var  $dbhr LoggedPDO */
    public $dbhr;
    /** @var  $dbhm LoggedPDO */
    public $dbhm;

    private $collection;

    /**
     * @return null
     */
    public function getCollection()
    {
        return $this->collection;
    }
    
    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $collection)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        switch ($collection) {
            case MessageCollection::APPROVED:
            case MessageCollection::PENDING:
            case MessageCollection::SPAM:
            case MessageCollection::DRAFT:
            case MessageCollection::QUEUED_YAHOO_USER:
            case MessageCollection::REJECTED:
                $this->collection = $collection;
                break;
            default:
                $this->collection = NULL;
        }
    }

    function get(&$ctx, $limit, $groupids, $userids = NULL, $types = NULL, $recentonly = FALSE) {
        $msgids = [];

        if ($this->collection == MessageCollection::DRAFT) {
            # Draft messages are handled differently, as they're not attached to any group.
            $me = whoAmI($this->dbhr, $this->dbhm);
            $sql = "SELECT msgid FROM messages_drafts WHERE session = ? OR (userid = ? AND userid IS NOT NULL);";
            $msgs = $this->dbhr->preQuery($sql, [
                session_id(),
                $me ? $me->getId() : NULL
            ]);
            #error_log($sql . " " . ($me ? $me->getId() : NULL));

            foreach ($msgs as $msg) {
                $msgids[] = ['id' => $msg['msgid']];
            }
        } else {
            $typeq = $types ? (" AND `type` IN (" . implode(',', $types) . ") ") : '';

            # At the moment we only support ordering by arrival DESC.
            $date = ($ctx == NULL || !pres('Date', $ctx)) ? NULL : $this->dbhr->quote(date("Y-m-d H:i:s", intval($ctx['Date'])));
            $dateq = $ctx == NULL ? ' 1=1 ' : (" (messages_groups.arrival < $date OR messages_groups.arrival = $date AND messages_groups.msgid < " . $this->dbhr->quote($ctx['id']) . ") ");

            # We only want to show spam messages upto 31 days old to avoid seeing too many, especially on first use.
            # See also Group.
            #
            # This fits with Yahoo's policy on deleting pending activity.
            $mysqltime = date ("Y-m-d", strtotime("Midnight 31 days ago"));
            $oldest = ($recentonly || $this->collection == MessageCollection::SPAM) ? " AND messages_groups.arrival >= '$mysqltime' " : '';

            # We may have some groups to filter by.
            $groupq = count($groupids) > 0 ? (" AND groupid IN (" . implode(',', $groupids) . ") ") : '';

            # We have a complicated set of different queries we can do.  This is because we want to make sure that
            # the query is as fast as possible, which means:
            # - access as few tables as we need to
            # - use multicolumn indexes
            if ($userids) {
                # We only query on a small set of userids, so it's more efficient to get the list of messages from them
                # first.
                $seltab = "(SELECT id, arrival, fromuser, deleted, `type` FROM messages WHERE fromuser IN (" . implode(',', $userids) . ")) messages";
                $sql = "SELECT msgid AS id, messages.arrival FROM messages_groups INNER JOIN $seltab ON messages_groups.msgid = messages.id AND messages.deleted IS NULL WHERE $dateq $oldest $typeq $groupq AND collection = ? AND messages_groups.deleted = 0 ORDER BY messages.arrival DESC LIMIT $limit";
            } else if (count($groupids) > 0) {
                # The messages_groups table has a multi-column index which makes it quick to find the relevant messages.
                if ($typeq != '') {
                    # We need to touch the messages table to find this.
                    $sql = "SELECT id, messages.arrival FROM messages INNER JOIN (SELECT msgid FROM messages_groups WHERE 1=1 $groupq AND collection = ? AND messages_groups.deleted = 0 AND $dateq $oldest ORDER BY arrival DESC, msgid DESC LIMIT $limit) t ON messages.id = t.msgid AND messages.deleted IS NULL $typeq ORDER BY messages.arrival DESC LIMIT $limit;";
                } else {
                    # We can do it all from messages_groups.
                    $sql = "SELECT msgid as id, arrival FROM messages_groups WHERE 1=1 $groupq AND collection = ? AND messages_groups.deleted = 0 AND $dateq $oldest ORDER BY arrival DESC LIMIT $limit;";
                }
            } else {
                # We are not searching within a specific group, so we have no choice but to do a larger join.
                $sql = "SELECT msgid AS id, messages.arrival FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id AND messages.deleted IS NULL WHERE $dateq $oldest $typeq AND collection = ? AND messages_groups.deleted = 0 ORDER BY messages.arrival DESC LIMIT $limit";
            }

            #error_log("Messages get $sql, {$this->collection}");

            $msglist = $this->dbhr->preQuery($sql, [
                $this->collection
            ]);

            # Get an array of just the message ids.  Save off context for next time.
            $ctx = [ 'Date' => NULL, 'id' => PHP_INT_MAX ];

            foreach ($msglist as $msg) {
                $msgids[] = ['id' => $msg['id']];

                $thisepoch = strtotime($msg['arrival']);

                if ($ctx['Date'] == NULL || $thisepoch < $ctx['Date']) {
                    $ctx['Date'] = $thisepoch;
                }

                $ctx['id'] = min($msg['id'], $ctx['id']);
            }
        }

        list($groups, $msgs) = $this->fillIn($msgids, $limit, NULL);

        return([$groups, $msgs]);
    }

    public function fillIn($msglist, $limit, $messagetype) {
        $msgs = [];
        $groups = [];

        # Don't return the message attribute as it will be huge.  They can get that via a call to the
        # message API call.
        foreach ($msglist as $msg) {
            $m = new Message($this->dbhr, $this->dbhm, $msg['id']);

            $type = $m->getType();
            if (!$messagetype || $type == $messagetype) {
                $role = $m->getRoleForMessage();
                #error_log("Role $role for {$msg['id']}");

                $thisgroups = $m->getGroups();
                $cansee = ($role == User::ROLE_MODERATOR) || ($role == User::ROLE_OWNER);

                foreach ($thisgroups as $groupid) {
                    if (!array_key_exists($groupid, $groups)) {
                        $g = new Group($this->dbhr, $this->dbhm, $groupid);
                        $atts = $g->getPublic();

                        # For Freegle groups, we can see the message even if not a member.  For other groups using ModTools,
                        # that isn't true, and we don't even want to return the information that there was a match on
                        # this group.
                        if (($role == User::ROLE_MEMBER || $role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) ||
                            ($atts['type'] == Group::GROUP_FREEGLE)
                        ) {
                            $groups[$groupid] = $g->getPublic();
                        }
                    }

                    $cansee = array_key_exists($groupid, $groups);
                }

                if ($cansee) {
                    switch ($this->collection) {
                        case MessageCollection::DRAFT:
                        case MessageCollection::QUEUED_YAHOO_USER:
                            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                                # Only visible to moderators or owners, or self (which returns a role of moderator).
                                $n = $m->getPublic(TRUE, TRUE);
                                unset($n['message']);
                                $msgs[] = $n;
                                $limit--;
                            }
                            break;
                        case MessageCollection::APPROVED:
                            $n = $m->getPublic(TRUE, TRUE);
                            unset($n['message']);
                            $n['matchedon'] = presdef('matchedon', $msg, NULL);
                            $msgs[] = $n;
                            $limit--;
                            break;
                        case MessageCollection::PENDING:
                        case MessageCollection::REJECTED:
                            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                                # Only visible to moderators or owners
                                $n = $m->getPublic(TRUE, TRUE);
                                unset($n['message']);
                                $n['matchedon'] = presdef('matchedon', $msg, NULL);
                                $msgs[] = $n;
                                $limit--;
                            }
                            break;
                        case MessageCollection::SPAM:
                            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                                # Only visible to moderators or owners
                                $n = $m->getPublic(TRUE, TRUE);
                                unset($n['message']);
                                $n['matchedon'] = presdef('matchedon', $msg, NULL);
                                $msgs[] = $n;
                                $limit--;
                            }
                            break;
                    }
                }
            }

            if ($limit <= 0) { break; }
        }

        return([$groups, $msgs]);
    }

    function findByYahooApprovedId($groupid, $id) {
        # We need to include deleted messages, otherwise we could delete something and then recreate it during a
        # sync, before our delete had hit Yahoo.
        $sql = "SELECT msgid FROM messages_groups WHERE groupid = ? AND yahooapprovedid = ?;";
        $msglist = $this->dbhr->preQuery($sql, [
            $groupid,
            $id
        ]);

        if (count($msglist) == 1) {
            return($msglist[0]['msgid']);
        } else {
            return NULL;
        }
    }

    function findByYahooPendingId($groupid, $id) {
        # We need to include deleted messages, otherwise we could delete something and then recreate it during a
        # sync, before our delete had hit Yahoo.
        $sql = "SELECT msgid FROM messages_groups WHERE groupid = ? AND yahoopendingid = ? AND collection = 'Pending';";
        $msglist = $this->dbhr->preQuery($sql, [
            $groupid,
            $id
        ]);

        if (count($msglist) == 1) {
            return($msglist[0]['msgid']);
        } else {
            return NULL;
        }
    }
}