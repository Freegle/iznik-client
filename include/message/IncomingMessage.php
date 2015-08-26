<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/Log.php');

# This class represents an incoming message, i.e. one we have received (usually by email).  It is used to parse
# a message and store it in the incoming DB table.
class IncomingMessage
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $id;
    private $source, $message, $textbody, $htmlbody, $subject, $fromname, $fromaddr, $envelopefrom, $envelopeto,
        $messageid, $retrycount, $retrylastfailure, $parser, $groupid, $fromip;

    /**
     * @return mixed
     */
    public function getFromIP()
    {
        return $this->fromip;
    }

    /**
     * @param mixed $fromip
     */
    public function setFromIP($fromip)
    {
        $this->fromip = $fromip;
        $this->dbhm->preExec("UPDATE messages_incoming SET fromip = ? WHERE id = ?;", [$fromip, $this->id]);
    }

    const EMAIL = 'Email';
    const YAHOO_APPROVED = 'Yahoo Approved';
    const YAHOO_PENDING = 'Yahoo Pending';

    /**
     * @return mixed
     */
    public function getGroupID()
    {
        return $this->groupid;
    }

    /**
     * @param mixed $groupid
     */
    public function setGroupID($groupid)
    {
        $this->groupid = $groupid;
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

            $msgs = $dbhr->preQuery("SELECT * FROM messages_incoming WHERE id = ?;", [$id]);
            foreach ($msgs as $msg) {
                foreach (['message', 'source', 'envelopefrom', 'fromname', 'fromaddr',
                        'envelopeto', 'subject', 'textbody', 'htmlbody', 'subject',
                         'messageid','retrycount', 'retrylastfailure', 'groupid', 'fromip'] as $attr) {
                    if (pres($attr, $msg)) {
                        $this->$attr = $msg[$attr];
                    }
                }
            }

            $this->parser = new PhpMimeMailParser\Parser();
            $this->parser->setText($this->message);
        }
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

    private $attachments, $attach_dir;

    /**
     * @return PhpMimeMailParser\Attachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    # Parse a raw SMTP message.
    public function parse($source, $envelopefrom, $envelopeto, $msg)
    {
        $this->message = $msg;

        $Parser = new PhpMimeMailParser\Parser();
        $this->parser = $Parser;
        $Parser->setText($msg);

        # We save the attachments to a temp directory.  This is tidied up on destruction or save.
        $this->attach_dir = tmpdir();
        $Parser->saveAttachments($this->attach_dir);
        $this->attachments = $Parser->getAttachments();

        if ($source == IncomingMessage::YAHOO_PENDING) {
            # This is an APPROVE mail; we need to extract the included copy of the original message.
            $atts = $this->getAttachments();
            if (count($atts) >= 1 && $atts[0]->contentType == 'message/rfc822') {
                $attachedmsg = $atts[0]->getContent();
                $Parser->setText($attachedmsg);
                $Parser->saveAttachments($this->attach_dir);
                $this->attachments = $Parser->getAttachments();
            }
        }

        $this->source = $source;
        $this->envelopefrom = $envelopefrom;
        $this->envelopeto = $envelopeto;

        $from = mailparse_rfc822_parse_addresses($Parser->getHeader('from'));
        $this->fromname = $from[0]['display'];
        $this->fromaddr = $from[0]['address'];
        $this->subject = $Parser->getHeader('subject');
        $this->messageid = $Parser->getHeader('message-id');
        $this->messageid = str_replace('<', '', $this->messageid);
        $this->messageid = str_replace('>', '', $this->messageid);

        $this->textbody = $Parser->getMessageBody('text');
        $this->htmlbody = $Parser->getMessageBody('html');

        # See if we can find a group this is intended for.
        $groupname = NULL;
        $to = $this->getTo();
        foreach ($to as $t) {
            if (preg_match('/(.*)@yahoogroups\.co.*/', $t['address'], $matches)) {
                $groupname = $matches[1];
            }
        }

        if ($groupname) {
            # Check if it's a group we host.
            $g = new Group($this->dbhr, $this->dbhm);
            $this->setGroupID($g->findByShortName($groupname));
        }
    }

    public function getHeader($hdr) {
        return($this->parser->getHeader($hdr));
    }

    public function getTo() {
        return(mailparse_rfc822_parse_addresses($this->parser->getHeader('to')));
    }

    # Save a parsed message to the DB
    public function save() {
        $sql = "INSERT INTO messages_incoming (groupid, source, message, envelopefrom, envelopeto, fromname, fromaddr, subject, messageid, textbody, htmlbody) VALUES(?,?,?,?,?,?,?,?,?,?,?);";
        $rc = $this->dbhm->preExec($sql, [
            $this->groupid,
            $this->source,
            $this->message,
            $this->envelopefrom,
            $this->envelopeto,
            $this->fromname,
            $this->fromaddr,
            $this->subject,
            $this->messageid,
            $this->textbody,
            $this->htmlbody
        ]);
        error_log($sql);

        $id = NULL;
        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->id = $id;

            $l = new Log($this->dbhr, $this->dbhm);
            $l->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_RECEIVED,
                'message_incoming' => $id,
                'text' => $this->messageid
            ]);
        }

        return($id);
    }

    function recordFailure($reason) {
        $this->dbhm->preExec("UPDATE messages_incoming SET retrycount = LAST_INSERT_ID(retrycount),
          retrylastfailure = NOW() WHERE id = ?;", [$this->id]);
        $count = $this->dbhm->lastInsertId();

        $l = new Log($this->dbhr, $this->dbhm);
        $l->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => Log::SUBTYPE_FAILURE,
            'message_incoming' => $this->id,
            'text' => $reason
        ]);

        return($count);
    }

    function delete()
    {
        $rc = true;

        if ($this->attach_dir) {
            rrmdir($this->attach_dir);
        }

        if ($this->id) {
            $rc = $this->dbhm->preExec("DELETE FROM messages_incoming WHERE id = ?;", [$this->id]);
        }

        return($rc);
    }
}