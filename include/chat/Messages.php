<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');

class ChatMessage extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'chatid', 'userid', 'date', 'message');
    var $settableatts = array('name');

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'chat_messages', 'chatmessage', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($chatid, $userid, $message) {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO chat_messages (chatid, userid, message) VALUES (?,?,?)", [
                $chatid,
                $userid,
                $message
            ]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'chat_messages', 'chatmessage', $this->publicatts);
            return($id);
        } else {
            return(NULL);
        }
    }

    public function getPublic() {
        $ret = $this->getAtts($this->publicatts);
        return($ret);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM chat_messages WHERE id = ?;", [$this->id]);
        return($rc);
    }
}