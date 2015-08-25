<?php

require_once(BASE_DIR . '/include/utils.php');
require_once(BASE_DIR . '/include/Log.php');

# This class represents an incoming message, i.e. one we have received (usually by email).  It is used to parse
# a message and store it in the incoming DB table.
class IncomingMessage
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $id;
    private $message, $textbody, $htmlbody, $subject, $fromname, $fromaddr, $envelopefrom, $envelopeto;

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
                        'envelopeto', 'subject', 'textbody', 'htmlbody', 'subject'] as $attr) {
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
    public function parse($envelopefrom, $envelopeto, $msg)
    {
        $this->message = $msg;

        $Parser = new PhpMimeMailParser\Parser();
        $Parser->setText($msg);

        $this->envelopefrom = $envelopefrom;
        $this->envelopeto = $envelopeto;

        $from = mailparse_rfc822_parse_addresses($Parser->getHeader('from'));
        $this->fromname = $from[0]['display'];
        $this->fromaddr = $from[0]['address'];
        $this->subject = $Parser->getHeader('subject');

        $this->textbody = $Parser->getMessageBody('text');
        $this->htmlbody = $Parser->getMessageBody('html');

        # We save the attachments to a temp directory.  This is tidied up on destruction or save.
        $this->attach_dir = tmpdir();
        $Parser->saveAttachments($this->attach_dir);

        $this->attachments = $Parser->getAttachments();
    }

    # Save a parsed message to the DB
    public function save() {
        $sql = "INSERT INTO messages_incoming (message, envelopefrom, envelopeto, fromname, fromaddr, subject, textbody, htmlbody) VALUES(?,?,?,?,?,?,?,?);";
        $rc = $this->dbhm->preExec($sql, [
            $this->message,
            $this->envelopefrom,
            $this->envelopeto,
            $this->fromname,
            $this->fromaddr,
            $this->subject,
            $this->textbody,
            $this->htmlbody
        ]);

        $id = NULL;
        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->id = $id;

            $l = new Log($this->dbhr, $this->dbhm);
            $l->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_RECEIVED,
                'message_incoming' => $id,
            ]);
        }

        return($id);
    }

    function delete()
    {
        if ($this->attach_dir) {
            rrmdir($this->attach_dir);
        }

        error_log("Destructor for {$this->id}");

        if ($this->id) {
            $this->dbhm->preExec("DELETE FROM messages_incoming WHERE id = ?;", [$this->id]);
        }
    }
}