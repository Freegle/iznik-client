<?php

require_once(BASE_DIR . '/include/utils.php');

# This class represents an incoming message, i.e. one we have received (usually by email).  It is used to parse
# a message and store it in the incoming DB table.
class IncomingMessage
{
    private $dbhr;
    private $dbhm;
    private $msg, $text, $html, $subject, $from, $to;

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->html;
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

    function __construct($dbhr, $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function parse($msg)
    {
        $this->msg = $msg;

        # Parse it
        $Parser = new PhpMimeMailParser\Parser();
        $Parser->setText($msg);

        $this->to = mailparse_rfc822_parse_addresses($Parser->getHeader('to'));
        $this->from = mailparse_rfc822_parse_addresses($Parser->getHeader('from'));
        $this->subject = $Parser->getHeader('subject');

        $this->text = $Parser->getMessageBody('text');
        $this->html = $Parser->getMessageBody('html');

        # We save the attachments to a temp directory.  This is tidied up on destruction.
        $this->attach_dir = tmpdir();
        $Parser->saveAttachments($this->attach_dir);

        $this->attachments = $Parser->getAttachments();
    }

    function __destruct()
    {
        rrmdir($this->attach_dir);
    }
}