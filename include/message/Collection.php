<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/message/ApprovedMessage.php');
require_once(IZNIK_BASE . '/include/message/IncomingMessage.php');
require_once(IZNIK_BASE . '/include/message/PendingMessage.php');
require_once(IZNIK_BASE . '/include/message/SpamMessage.php');

class Collection
{
    # These match the collection names
    const APPROVED = 'messages_approved';
    const PENDING = 'messages_pending';
    const SPAM = 'messages_spam';

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
        $roles = [];
        $start = $start ? $start : 0;
        $me = whoAmI($this->dbhr, $this->dbhm);

        foreach ($groupfilter as $groupid) {
            $g = new Group($this->dbhr, $this->dbhm, $groupid);
            $groups[$groupid] = $g->getPublic();
            $groupids[] = $groupid;
            $roles[$groupid] = $me ? $me->getRole($groupid) : User::ROLE_NONE;
            $groups[$groupid]['role'] = $roles[$groupid];
        }

        if (count($groupids) > 0) {
            $groupq = " AND groupid IN (" . implode(',', $groupids) . ") ";

            $sql = "SELECT id, groupid FROM {$this->collection} WHERE id > ? $groupq ORDER BY id DESC LIMIT $limit";
            $msglist = $this->dbhr->preQuery($sql, [
                $start
            ]);

            # Don't return the message attribute as it will be huge.  They can get that via a call to the
            # message API call.
            foreach ($msglist as $msg) {
                switch ($this->collection) {
                    case Collection::APPROVED:
                        $m = new ApprovedMessage($this->dbhr, $this->dbhm, $msg['id']);
                        $n = $m->getPublic();
                        unset($n['message']);
                        $msgs[] = $n;
                        break;
                    case Collection::PENDING:
                        if ($roles[$msg['groupid']] == User::ROLE_MODERATOR ||
                            $roles[$msg['groupid']] == User::ROLE_OWNER
                        ) {
                            # Only visible to moderators or owners
                            $m = new PendingMessage($this->dbhr, $this->dbhm, $msg['id']);
                            $n = $m->getPublic();
                            unset($n['message']);
                            $msgs[] = $n;
                        }
                        break;
                    case Collection::SPAM:
                        if ($roles[$msg['groupid']] == User::ROLE_MODERATOR ||
                            $roles[$msg['groupid']] == User::ROLE_OWNER
                        ) {
                            # Only visible to moderators or owners
                            $m = new SpamMessage($this->dbhr, $this->dbhm, $msg['id']);
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

    function find($sender, $groupid, $date) {
        $sql = "SELECT id FROM {$this->collection} WHERE fromaddr = ? AND groupid = ? AND date = ?;";
        $msglist = $this->dbhr->preQuery($sql, [
            $sender,
            $groupid,
            $date
        ]);

        return(count($msglist) > 0);
    }
}