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

    function get($start, $limit, $groupids) {
        $groups = [];
        $msgs = [];

        if ($start) {
            $args = [
                $start,
                $this->collection
            ];
            $startq = "messages.date < ? ";
        } else {
            $args = [
                $this->collection
            ];
            $startq = '1=1';
        }

        if (count($groupids) > 0) {
            $groupq = " AND groupid IN (" . implode(',', $groupids) . ") ";

            # At the moment we only support ordering by date DESC.
            #
            # Put a limit on this query to stop it being stupid, though we enforce the $limit parameter in the loop.
            $sql = "SELECT msgid AS id FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id AND messages.deleted IS NULL WHERE $startq $groupq AND collection = ? AND messages_groups.deleted = 0 ORDER BY messages.date DESC LIMIT 1000";
            $msglist = $this->dbhr->preQuery($sql, $args);

            # Get an array of just the message ids.
            $msgids = [];
            foreach ($msglist as $msg) {
                $msgids[] = [ 'id' => $msg['id'] ];
            }
            list($groups, $msgs) = $this->fillIn($msgids, $limit);
        }

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
                        $n = $m->getPublic(TRUE, TRUE, FALSE);
                        unset($n['message']);
                        $n['matchedon'] = presdef('matchedon', $msg, NULL);
                        $msgs[] = $n;
                        $limit--;
                        break;
                    case MessageCollection::PENDING:
                        if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                            # Only visible to moderators or owners
                            $n = $m->getPublic(TRUE, TRUE, TRUE);
                            unset($n['message']);
                            $n['matchedon'] = presdef('matchedon', $msg, NULL);
                            $msgs[] = $n;
                            $limit--;
                        }
                        break;
                    case MessageCollection::SPAM:
                        if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                            # Only visible to moderators or owners
                            $n = $m->getPublic(TRUE, TRUE, FALSE);
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