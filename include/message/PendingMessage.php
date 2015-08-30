<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/IncomingMessage.php');

# This class represents a pending message, i.e. one we have put into the messages_pending table.
class PendingMessage
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $id;
    private $source, $message, $textbody, $htmlbody, $subject, $fromname, $fromaddr, $envelopefrom, $envelopeto,
        $messageid, $parser, $groupid, $fromip, $fromhost;

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return mixed
     */
    public function getFromIP()
    {
        return $this->fromip;
    }

    /**
     * @return mixed
     */
    public function getFromhost()
    {
        return $this->fromhost;
    }

    /**
     * @return mixed
     */
    public function getGroupID()
    {
        return $this->groupid;
    }

    /**
     * @return null
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getMessageID()
    {
        return $this->messageid;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getEnvelopefrom()
    {
        return $this->envelopefrom;
    }

    /**
     * @return mixed
     */
    public function getEnvelopeto()
    {
        return $this->envelopeto;
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        if ($id) {
            $this->id = $id;

            $msgs = $dbhr->preQuery("SELECT * FROM messages_pending WHERE id = ?;", [$id]);
            foreach ($msgs as $msg) {
                foreach (['message', 'source', 'envelopefrom', 'fromname', 'fromaddr',
                             'envelopeto', 'subject', 'textbody', 'htmlbody', 'subject',
                             'messageid', 'groupid', 'fromip', 'fromname', 'incomingid'] as $attr) {
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

    /**
     * @return mixed
     */
    public function getFromname()
    {
        return $this->fromname;
    }

    /**
     * @return mixed
     */
    public function getFromaddr()
    {
        return $this->fromaddr;
    }

    /**
     * @return mixed
     */
    public function getTextbody()
    {
        return $this->textbody;
    }

    /**
     * @return mixed
     */
    public function getHtmlbody()
    {
        return $this->htmlbody;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
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
                error_log("Found prior pending by message id");
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
                error_log("Found prior pending by TN ID");
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