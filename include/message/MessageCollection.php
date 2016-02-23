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
                $this->collection = $collection;
                break;
            default:
                $this->collection = NULL;
        }
    }

    function get(&$ctx, $limit, $groupids, $userids = NULL) {
        $groups = [];
        $msgs = [];

        $date = $ctx == NULL ? NULL : $this->dbhr->quote(date("Y-m-d H:i:s", $ctx['Date']));
        $dateq = $ctx == NULL ? ' 1=1 ' : (" (messages.date < $date OR messages.date = $date AND messages.id < " . $this->dbhr->quote($ctx['id']) . ") ");

        # We only want to show spam messages upto 7 days old to avoid seeing too many, especially on first use.
        $mysqltime = date ("Y-m-d", strtotime("Midnight 7 days ago"));
        $oldest = $this->collection == MessageCollection::SPAM ? " AND messages.date >= '$mysqltime' " : '';

        $ctx = [ 'Date' => NULL, 'id' ];

        $groupq = count($groupids) > 0 ? (" AND groupid IN (" . implode(',', $groupids) . ") ") : '';

        # At the moment we only support ordering by date DESC.
        #
        # If we have a set of users, then it is more efficient to get the relevant messages first (because there
        # are few and it's well-indexed).
        if ($userids) {
            $seltab = "(SELECT id, date, fromuser, deleted FROM messages WHERE fromuser IN (" . implode(',', $userids) . ")) messages";
        } else {
            $seltab = "messages";
        }

        $sql = "SELECT msgid AS id, date FROM messages_groups INNER JOIN $seltab ON messages_groups.msgid = messages.id AND messages.deleted IS NULL WHERE $dateq $oldest $groupq AND collection = ? AND messages_groups.deleted = 0 ORDER BY messages.date DESC, messages.id DESC LIMIT $limit";

        $msglist = $this->dbhr->preQuery($sql, [
            $this->collection
        ]);

        # Get an array of just the message ids.
        $msgids = [];
        foreach ($msglist as $msg) {
            $msgids[] = ['id' => $msg['id']];

            $thisepoch = strtotime($msg['date']);

            if ($ctx['Date'] == NULL || $thisepoch < $ctx['Date']) {
                $ctx['Date'] = $thisepoch;
            }

            $ctx['id'] = $msg['id'];
        }

        list($groups, $msgs) = $this->fillIn($msgids, $limit);

        return([$groups, $msgs]);
    }

    public function fillIn($msglist, $limit) {
        $msgs = [];
        $groups = [];

        # Don't return the message attribute as it will be huge.  They can get that via a call to the
        # message API call.
        foreach ($msglist as $msg) {
            $m = new Message($this->dbhr, $this->dbhm, $msg['id']);
            $role = $m->getRoleForMessage();

            $thisgroups = $m->getGroups();
            $cansee = FALSE;

            foreach ($thisgroups as $groupid) {
                if (!array_key_exists($groupid, $groups)) {
                    $g = new Group($this->dbhr, $this->dbhm, $groupid);
                    $atts = $g->getPublic();

                    # For Freegle groups, we can see the message even if not a member.  For other groups using ModTools,
                    # that isn't true, and we don't even want to return the information that there was a match on
                    # this group.
                    if (($role == User::ROLE_MEMBER || $role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) ||
                        ($atts['type'] == Group::GROUP_FREEGLE)) {
                        $groups[$groupid] = $g->getPublic();
                    }
                }

                $cansee = array_key_exists($groupid, $groups);
            }

            if ($cansee) {
                switch ($this->collection) {
                    case MessageCollection::APPROVED:
                        $n = $m->getPublic(TRUE, TRUE);
                        unset($n['message']);
                        $n['matchedon'] = presdef('matchedon', $msg, NULL);
                        $msgs[] = $n;
                        $limit--;
                        break;
                    case MessageCollection::PENDING:
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