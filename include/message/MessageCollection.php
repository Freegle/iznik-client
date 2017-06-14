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
    const ALLUSER = 'AllUser';
    const OWNPOSTS = 120;

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

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $collection = NULL)
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
                $this->collection = [ $collection ];
                break;
            case MessageCollection::ALLUSER:
                # The ones users should be able to see, e.g. on home page.
                $this->collection = [
                    MessageCollection::APPROVED,
                    MessageCollection::PENDING,
                    MessageCollection::REJECTED,
                    MessageCollection::QUEUED_YAHOO_USER
                ];
                break;
            default:
                $this->collection = [];
        }
    }

    function get(&$ctx, $limit, $groupids, $userids = NULL, $types = NULL, $age = NULL) {
        do {
            $msgids = [];

            if (in_array(MessageCollection::DRAFT, $this->collection)) {
                # Draft messages are handled differently, as they're not attached to any group.
                $me = whoAmI($this->dbhr, $this->dbhm);
                $userids = $userids ? $userids : ($me ? [ $me->getId() ] : NULL);

                $sql = (count($userids) > 0) ? ("SELECT msgid FROM messages_drafts WHERE session = ? OR userid IN (" . implode(',', $userids) . ");") : "SELECT msgid FROM messages_drafts WHERE session = ?;";
                $msgs = $this->dbhr->preQuery($sql, [
                    session_id()
                ]);

                foreach ($msgs as $msg) {
                    $msgids[] = [
                        'id' => $msg['msgid'],
                        'collection' => MessageCollection::DRAFT
                    ];
                }
            }

            $collection = array_filter($this->collection, function ($val) {
                return ($val != MessageCollection::DRAFT);
            });

            if (count($collection) > 0) {
                $typeq = $types ? (" AND `msgtype` IN ('" . implode("','", $types) . "') ") : '';

                # At the moment we only support ordering by arrival DESC.  Note that arrival can either be when this
                # message arrived for the very first time, or when it was reposted.
                $date = ($ctx == NULL || !pres('Date', $ctx)) ? NULL : $this->dbhr->quote(date("Y-m-d H:i:s", intval($ctx['Date'])));
                $dateq = !$date ? ' 1=1 ' : (" (messages_groups.arrival < $date OR messages_groups.arrival = $date AND messages_groups.msgid < " . $this->dbhr->quote($ctx['id']) . ") ");
                $oldest = '';

                if (in_array(MessageCollection::SPAM, $collection)) {
                    # We only want to show spam messages upto 31 days old to avoid seeing too many, especially on first use.
                    # See also Group.
                    #
                    # This fits with Yahoo's policy on deleting pending activity.
                    #
                    # This code assumes that if we're called to retrieve SPAM, it's the only collection.  That's true at
                    # the moment as the only use of multiple collection values is via ALLUSER, which doesn't include SPAM.
                    $mysqltime = date("Y-m-d", strtotime("Midnight 31 days ago"));
                    $oldest = " AND messages_groups.arrival >= '$mysqltime' ";
                } else if ($age !== NULL) {
                    $mysqltime = date("Y-m-d", strtotime("Midnight $age days ago"));
                    $oldest = " AND messages_groups.arrival >= '$mysqltime' ";
                }

                # We may have some groups to filter by.
                $groupq = count($groupids) > 0 ? (" AND groupid IN (" . implode(',', $groupids) . ") ") : '';

                # We have a complicated set of different queries we can do.  This is because we want to make sure that
                # the query is as fast as possible, which means:
                # - access as few tables as we need to
                # - use multicolumn indexes
                $collectionq = " AND collection IN ('" . implode("','", $collection) . "') ";
                if ($userids) {
                    # We only query on a small set of userids, so it's more efficient to get the list of messages from them
                    # first.
                    $seltab = "(SELECT id, arrival, fromuser, deleted, `type` FROM messages WHERE fromuser IN (" . implode(',', $userids) . ")) messages";
                    $sql = "SELECT msgid AS id, messages.arrival, messages_groups.collection FROM messages_groups INNER JOIN $seltab ON messages_groups.msgid = messages.id AND messages.deleted IS NULL WHERE $dateq $oldest $typeq $groupq $collectionq AND messages_groups.deleted = 0 ORDER BY messages_groups.arrival DESC LIMIT $limit";
                } else if (count($groupids) > 0) {
                    # The messages_groups table has a multi-column index which makes it quick to find the relevant messages.
                    $typeq = $types ? (" AND `msgtype` IN ('" . implode("','", $types) . "') ") : '';
                    $sql = "SELECT msgid as id, arrival, messages_groups.collection FROM messages_groups WHERE 1=1 $groupq $collectionq AND messages_groups.deleted = 0 AND $dateq $oldest $typeq ORDER BY arrival DESC LIMIT $limit;";
                } else {
                    # We are not searching within a specific group, so we have no choice but to do a larger join.
                    $sql = "SELECT msgid AS id, messages.arrival, messages_groups.collection FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id AND messages.deleted IS NULL WHERE $dateq $oldest $typeq $collectionq AND messages_groups.deleted = 0 ORDER BY messages_groups.arrival DESC LIMIT $limit";
                }

                #error_log("Messages get $sql");

                $msglist = $this->dbhr->preQuery($sql);

                # Get an array of just the message ids.  Save off context for next time.
                $ctx = ['Date' => NULL, 'id' => PHP_INT_MAX];

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
//            error_log("Filled in " . count($msgs) . " from " . count($msgids));

            # We might have excluded all the messages we found; if so, keep going.
        } while (count($msgids) > 0 && count($msgs) == 0);

        return([$groups, $msgs]);
    }

    public function fillIn($msglist, $limit, $messagetype) {
        $msgs = [];
        $groups = [];

        # Don't return the message attribute as it will be huge.  They can get that via a call to the
        # message API call.
        foreach ($msglist as $msg) {
            $m = new Message($this->dbhr, $this->dbhm, $msg['id']);
            $public = $m->getPublic(TRUE, TRUE);

            $type = $m->getType();
            if (!$messagetype || $type == $messagetype) {
                $role = $m->getRoleForMessage(FALSE);
                $cansee = $m->canSee($public);
                $coll = presdef('collection', $msg, MessageCollection::APPROVED);

                if ($cansee && $coll != MessageCollection::DRAFT) {
                    $thisgroups = $m->getGroups(TRUE);

                    # Make sure we only return this if it's on a group.
                    $cansee = FALSE;

                    foreach ($thisgroups as $groupid) {
                        $cansee = TRUE;
                        $g = Group::get($this->dbhr, $this->dbhm, $groupid);
                        $groups[$groupid] = $g->getPublic();
                    }
                }

                if ($cansee) {
                    switch ($coll) {
                        case MessageCollection::DRAFT:
                        case MessageCollection::QUEUED_YAHOO_USER:
                            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                                # Only visible to moderators or owners, or self (which returns a role of moderator).
                                $n = $public;
                                unset($n['message']);
                                $msgs[] = $n;
                                $limit--;
                            }
                            break;
                        case MessageCollection::APPROVED:
                            $n = $public;
                            unset($n['message']);
                            $n['matchedon'] = presdef('matchedon', $msg, NULL);
                            $msgs[] = $n;
                            $limit--;
                            break;
                        case MessageCollection::PENDING:
                        case MessageCollection::REJECTED:
                            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                                # Only visible to moderators or owners
                                $n = $public;
                                unset($n['message']);
                                $n['matchedon'] = presdef('matchedon', $msg, NULL);
                                $msgs[] = $n;
                                $limit--;
                            }
                            break;
                        case MessageCollection::SPAM:
                            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                                # Only visible to moderators or owners
                                $n = $public;
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

    function getRecentMessages($type = Group::GROUP_FREEGLE) {
        $groupq = $type ? " AND groups.type = '$type' " : "";
        $mysqltime = date("Y-m-d H:i:s", strtotime('30 minutes ago'));
        $messages = $this->dbhr->preQuery("SELECT messages.id, messages_groups.arrival, messages_groups.groupid, messages.subject FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid INNER JOIN groups ON messages_groups.groupid = groups.id INNER JOIN users ON messages.fromuser = users.id WHERE messages_groups.arrival > ? AND collection = ? AND publishconsent = 1 $groupq ORDER BY messages_groups.arrival ASC;", [
            $mysqltime,
            MessageCollection::APPROVED
        ]);

        $ret = [];

        $last = NULL;
        foreach ($messages as $message) {
            $g = Group::get($this->dbhr, $this->dbhm, $message['groupid']);
            $namedisplay = $g->getPrivate('namefull') ? $g->getPrivate('namefull') : $g->getPrivate('nameshort');
            $arrival = strtotime($message['arrival']);
            $delta = $last !== NULL ? ($arrival - $last) : 0;
            $last = $arrival;

            $ret[] = [
                'id' => $message['id'],
                'message' => [
                    'id' => $message['id'],
                    'subject' => $message['subject'],
                    'arrival' => ISODate($message['arrival']),
                    'delta' => $delta,
                ],
                'group' => [
                    'id' => $g->getId(),
                    'nameshort' => $g->getPrivate('nameshort'),
                    'namedisplay' => $namedisplay,
                    'lat' => $g->getPrivate('lat'),
                    'lng' => $g->getPrivate('lng')
                ]
            ];
        }

        return($ret);
    }
}