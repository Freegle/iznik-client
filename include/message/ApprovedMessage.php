<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

# This class represents an approved message, i.e. one we have put into the messages_approvedtable.
class ApprovedMessage extends Message
{
    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        if ($id) {
            $this->id = $id;

            $msgs = $dbhr->preQuery("SELECT * FROM messages_approved WHERE id = ?;", [$id]);
            foreach ($msgs as $msg) {
                foreach (array_merge($this->nonMemberAtts, $this->memberAtts, $this->moderatorAtts, $this->ownerAtts) as $attr) {
                    if (pres($attr, $msg)) {
                        $this->$attr = $msg[$attr];
                    }
                }
            }
        }
    }

    public static function findByIncomingId(LoggedPDO $dbhr, $id) {
        $msgs = $dbhr->preQuery("SELECT id FROM messages_approved WHERE incomingid = ?;",
            [$id]);
        foreach ($msgs as $msg) {
            return($msg['id']);
        }

        return(NULL);
    }

    function delete()
    {
        $rc = true;

        if ($this->id) {
            $rc = $this->dbhm->preExec("DELETE FROM messages_approved WHERE id = ?;", [$this->id]);
        }

        return($rc);
    }
}