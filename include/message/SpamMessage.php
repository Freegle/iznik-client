<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

# This class represents a spam message, i.e. one we have put into the messages_spam table.
class SpamMessage extends Message
{
    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        if ($id) {
            $this->id = $id;

            $msgs = $dbhr->preQuery("SELECT * FROM messages_spam WHERE id = ?;", [$id]);
            foreach ($msgs as $msg) {
                foreach (['message', 'source', 'envelopefrom', 'fromname', 'fromaddr',
                             'envelopeto', 'subject', 'textbody', 'htmlbody', 'subject',
                             'messageid', 'groupid', 'fromip', 'fromname', 'reason'] as $attr) {
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
        $msgs = $dbhr->preQuery("SELECT id FROM messages_spam WHERE incomingid = ?;",
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

    function delete()
    {
        $rc = true;

        if ($this->id) {
            $rc = $this->dbhm->preExec("DELETE FROM messages_spam WHERE id = ?;", [$this->id]);
        }

        return($rc);
    }
}