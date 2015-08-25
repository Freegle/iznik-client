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
    private $source, $message, $textbody, $htmlbody, $subject, $fromname, $fromaddr, $envelopefrom, $envelopeto, $messageid, $retrycount, $retrylastfailure;

    const EMAIL = 'Email';
    const YAHOO_APPROVED = 'Yahoo Approved';
    const YAHOO_PENDING = 'Yahoo Pending';

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
    public function getRetrycount()
    {
        return $this->retrycount;
    }

    /**
     * @return mixed
     */
    public function getRetrylastfailure()
    {
        return $this->retrylastfailure;
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
                         'messageid','retrycount', 'retrylastfailure'] as $attr) {
                    $this->$attr = $msg[$attr];
                }
            }
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
        $Parser->setText($msg);

        # We save the attachments to a temp directory.  This is tidied up on destruction or save.
        $this->attach_dir = tmpdir();
        $Parser->saveAttachments($this->attach_dir);
        $this->attachments = $Parser->getAttachments();

        if ($source == IncomingMessage::YAHOO_PENDING) {
            # This is an APPROVE mail; we need to extract the included copy of the original message.
            $atts = $this->getAttachments();
            if (count($atts) >= 1 && $atts[0]->contentType == 'message/rfc822') {
                error_log("Found attached message " . var_export($atts, true));
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
    }

    # Save a parsed message to the DB
    public function save() {
        $sql = "INSERT INTO messages_incoming (source, message, envelopefrom, envelopeto, fromname, fromaddr, subject, messageid, textbody, htmlbody) VALUES(?,?,?,?,?,?,?,?,?,?);";
        $rc = $this->dbhm->preExec($sql, [
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
        $rc = $this->dbhm->preExec("UPDATE messages_incoming SET retrycount = LAST_INSERT_ID(retrycount),
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