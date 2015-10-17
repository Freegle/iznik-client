<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

class Collection
{
    # These match the collection enumeration
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
            case Collection::APPROVED:
            case Collection::PENDING:
            case Collection::SPAM:
                $this->collection = $collection;
                break;
            default:
                $this->collection = NULL;
        }
    }

    function get($start, $limit, $groupfilter) {
        $groups = [];
        $groupids = [];
        $msgs = [];
        $start = $start ? $start : 0;

        foreach ($groupfilter as $groupid) {
            $g = new Group($this->dbhr, $this->dbhm, $groupid);
            $groups[$groupid] = $g->getPublic();
            $groupids[] = $groupid;
        }

        if (count($groupids) > 0) {
            $groupq = " AND groupid IN (" . implode(',', $groupids) . ") ";

            # We don't have to worry much about ordering, because that is done by the client, but we might as well return
            # something predictable.
            $sql = "SELECT msgid, groupid FROM messages_groups WHERE msgid > ? $groupq AND collection = ? AND deleted = 0 ORDER BY msgid DESC LIMIT $limit";
            $msglist = $this->dbhr->preQuery($sql, [
                $start,
                $this->collection
            ]);

            # Don't return the message attribute as it will be huge.  They can get that via a call to the
            # message API call.
            foreach ($msglist as $msg) {
                $m = new Message($this->dbhr, $this->dbhm, $msg['msgid']);
                $role = $m->getRoleForMessage();

                switch ($this->collection) {
                    case Collection::APPROVED:
                        $n = $m->getPublic();
                        unset($n['message']);
                        $msgs[] = $n;
                        break;
                    case Collection::PENDING:
                        if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                            # Only visible to moderators or owners
                            $n = $m->getPublic();
                            unset($n['message']);
                            $msgs[] = $n;
                        }
                        break;
                    case Collection::SPAM:
                        if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                            # Only visible to moderators or owners
                            $n = $m->getPublic();
                            unset($n['message']);
                            $msgs[] = $n;
                        }
                        break;
                }
            }
        }

        return([$groups, $msgs]);
    }

    function findByYahooApprovedId($groupid, $id) {
        $sql = "SELECT id FROM messages INNER JOIN messages_groups ON messages.yahooapprovedid = ? AND messages_groups.msgid = messages.id AND messages_groups.groupid = ?;";
        $msglist = $this->dbhr->preQuery($sql, [
            $id,
            $groupid
        ]);

        if (count($msglist) == 1) {
            return($msglist[0]['id']);
        } else {
            return NULL;
        }
    }

    function findByYahooPendingId($groupid, $id) {
        $sql = "SELECT id FROM messages INNER JOIN messages_groups ON messages.yahoopendingid = ? AND messages_groups.msgid = messages.id AND messages_groups.groupid = ?;";
        error_log("$sql $id $groupid");
        $msglist = $this->dbhr->preQuery($sql, [
            $id,
            $groupid
        ]);

        if (count($msglist) == 1) {
            return($msglist[0]['id']);
        } else {
            return NULL;
        }
    }
}