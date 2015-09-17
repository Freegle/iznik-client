<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/message/IncomingMessage.php');

# This class represents a pending message, i.e. one we have put into the messages_pending table.
class PendingMessage extends Message
{
    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        if ($id) {
            $this->id = $id;

            $msgs = $dbhr->preQuery("SELECT * FROM messages_pending WHERE id = ?;", [$id]);
            foreach ($msgs as $msg) {
                foreach (array_merge($this->nonMemberAtts, $this->memberAtts, $this->moderatorAtts, $this->ownerAtts) as $attr) {
                    if (pres($attr, $msg)) {
                        $this->$attr = $msg[$attr];
                    }
                }
            }

            $this->parser = new PhpMimeMailParser\Parser();
            $this->parser->setText($this->message);
        }
    }

    public static function findByIncomingId(LoggedPDO $dbhr, $id) {
        $msgs = $dbhr->preQuery("SELECT id FROM messages_pending WHERE incomingid = ?;",
            [$id]);
        foreach ($msgs as $msg) {
            return($msg['id']);
        }

        return(NULL);
    }

    public function getHeader($hdr) {
        return($this->parser->getHeader($hdr));
    }

    public function getTo() {
        return(mailparse_rfc822_parse_addresses($this->parser->getHeader('to')));
    }

    public function removeApprovedMessage(IncomingMessage $msg) {
        # Try to find by message id.
        $msgid = $msg->getMessageID();
        if ($msgid) {
            $sql = "SELECT id FROM messages_pending WHERE messageid LIKE ?;";
            $pendings = $this->dbhr->preQuery($sql, [$msgid]);

            foreach ($pendings as $pending) {
                $this->dbhm->preExec("DELETE FROM messages_pending WHERE id = ?;", [$pending['id']]);
            }
        }

        # Try to find by TN post id - TN doesn't put a messageid in.
        # TODO It would be nice to remove this.
        $tnpostid = $msg->getTnpostid();
        if ($tnpostid) {
            $sql = "SELECT id FROM messages_pending WHERE tnpostid LIKE ?;";
            $pendings = $this->dbhr->preQuery($sql,[$tnpostid]);

            foreach ($pendings as $pending) {
                $this->dbhm->preExec("DELETE FROM messages_pending WHERE id = ?;", [$pending['id']]);
            }
        }
    }

    function delete()
    {
        $rc = true;

        if ($this->id) {
            $rc = $this->dbhm->preExec("DELETE FROM messages_pending WHERE id = ?;", [$this->id]);
        }

        return($rc);
    }
}