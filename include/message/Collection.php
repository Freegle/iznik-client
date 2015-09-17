<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/message/ApprovedMessage.php');
require_once(IZNIK_BASE . '/include/message/IncomingMessage.php');
require_once(IZNIK_BASE . '/include/message/PendingMessage.php');
require_once(IZNIK_BASE . '/include/message/SpamMessage.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

class Collection
{
    # These match the table names
    const APPROVED = 'messages_approved';
    const PENDING = 'messages_pending';
    const SPAM = 'messages_spam';

    /** @var  $dbhr LoggedPDO */
    public $dbhr;
    /** @var  $dbhm LoggedPDO */
    public $dbhm;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $collection)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        switch ($collection) {
            case Collection::APPROVED:
            case Collection::PENDING:
            case Collection::SPAM:
                $this->table = $collection;
                break;
            default:
                $this->table = NULL;
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
        }

        if (count($groupids) > 0) {
            $groupq = " AND groupid IN (" . implode(',', $groupids) . ") ";

            $sql = "SELECT id, groupid FROM {$this->table} WHERE id > ? $groupq ORDER BY id DESC LIMIT $limit";
            $msglist = $this->dbhr->preQuery($sql, [
                $start
            ]);

            foreach ($msglist as $msg) {
                switch ($this->table) {
                    case Collection::APPROVED:
                        $m = new ApprovedMessage($this->dbhr, $this->dbhm, $msg['id']);
                        $msgs[] = $m->getPublic();
                        break;
                    case Collection::PENDING:
                        if ($roles[$msg['groupid']] == User::ROLE_MODERATOR ||
                            $roles[$msg['groupid']] == User::ROLE_OWNER
                        ) {
                            # Only visible to moderators or owners
                            $m = new PendingMessage($this->dbhr, $this->dbhm, $msg['id']);
                            $msgs[] = $m->getPublic();
                        }
                        break;
                    case Collection::SPAM:
                        if ($roles[$msg['groupid']] == User::ROLE_MODERATOR ||
                            $roles[$msg['groupid']] == User::ROLE_OWNER
                        ) {
                            # Only visible to moderators or owners
                            $m = new SpamMessage($this->dbhr, $this->dbhm, $msg['id']);
                            $msgs[] = $m->getPublic();
                        }
                        break;
                }
            }
        }

        return([$groups, $msgs]);
    }
}