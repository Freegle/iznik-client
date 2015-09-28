<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/user/User.php');

# This class represents an incoming message, i.e. one we have received (usually by email).  It is used to parse
# a message and store it in the incoming DB table.
class IncomingMessage extends Message
{
    private $attach_dir, $attach_files, $parser;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($this->dbhr, $this->dbhm);

        # Add in properties specific to this class.
        $this->moderatorAtts = array_merge($this->moderatorAtts, [
            'retrycount', 'retrylastfailure', 'yahoopendingid', 'yahooreject', 'yahooapprove'
        ]);

        if ($id) {
            $this->id = $id;

            $msgs = $dbhr->preQuery("SELECT * FROM messages_incoming WHERE id = ?;", [$id]);
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

    # Parse a raw SMTP message.
    public function parse($source, $envelopefrom, $envelopeto, $msg)
    {
        $this->message = $msg;

        $Parser = new PhpMimeMailParser\Parser();
        $this->parser = $Parser;
        $Parser->setText($msg);

        # We save the attachments to a temp directory.  This is tidied up on destruction or save.
        $this->attach_dir = tmpdir();
        $this->attach_files = $Parser->saveAttachments($this->attach_dir . DIRECTORY_SEPARATOR);
        $this->attachments = $Parser->getAttachments();
        $this->yahooapprove = NULL;
        $this->yahooreject = NULL;

        if ($source == Message::YAHOO_PENDING) {
            error_log("Pending, approve $envelopefrom");
            # This is an APPROVE mail; we need to extract the included copy of the original message.
            $this->yahooapprove = $envelopefrom;
            if (preg_match('/^(.*-reject-.*yahoogroups.*?)($| |=)/im', $msg, $matches)) {
                $this->yahooreject = trim($matches[1]);
                error_log("Reject {$this->yahooreject}");
            }

            $atts = $this->getParsedAttachments();
            if (count($atts) >= 1 && $atts[0]->contentType == 'message/rfc822') {
                $attachedmsg = $atts[0]->getContent();
                $Parser->setText($attachedmsg);
                $this->attach_files = $Parser->saveAttachments($this->attach_dir);
                $this->attachments = $Parser->getAttachments();
            }
        }

        if (count($this->attachments) == 0) {
            # No attachments - tidy up temp dir.
            rrmdir($this->attach_dir);
        }

        $this->source = $source;
        $this->envelopefrom = $envelopefrom;
        $this->envelopeto = $envelopeto;
        $this->yahoopendingid = NULL;

        # Yahoo posts messages from the group address, but with a header showing the
        # original from address.
        $originalfrom = $Parser->getHeader('x-original-from');

        if ($originalfrom) {
            $from = mailparse_rfc822_parse_addresses($originalfrom);
        } else {
            $from = mailparse_rfc822_parse_addresses($Parser->getHeader('from'));
        }

        $this->fromname = $from[0]['display'];
        $this->fromaddr = $from[0]['address'];
        $this->date = gmdate("Y-m-d H:i:s", strtotime($Parser->getHeader('date')));

        $this->sourceheader = $Parser->getHeader('x-freegle-source');
        $this->sourceheader = ($this->sourceheader == 'Unknown' ? NULL : $this->sourceheader);

        if (!$this->sourceheader) {
            $this->sourceheader = $Parser->getHeader('x-trash-nothing-source');
            if ($this->sourceheader) {
                $this->sourceheader = "TN-" . $this->sourceheader;
            }
        }

        if (!$this->sourceheader && $Parser->getHeader('x-mailer') == 'Yahoo Groups Message Poster') {
            $this->sourceheader = 'Yahoo-Web';
        }

        if (!$this->sourceheader && (strpos($Parser->getHeader('x-mailer'), 'Freegle Message Maker') !== FALSE)) {
            $this->sourceheader = 'MessageMaker';
        }

        if (!$this->sourceheader) {
            if (stripos($this->fromaddr, 'ilovefreegle.org') !== FALSE) {
                $this->sourceheader = 'FDv2';
            } else {
                $this->sourceheader = 'Yahoo-Email';
            }
        }

        $this->subject = $Parser->getHeader('subject');
        $this->messageid = $Parser->getHeader('message-id');
        $this->messageid = str_replace('<', '', $this->messageid);
        $this->messageid = str_replace('>', '', $this->messageid);
        $this->tnpostid = $Parser->getHeader('x-trash-nothing-post-id');

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

            if ($this->groupid) {
                # If this is a reuse group, we need to determine the type.
                $g = new Group($this->dbhr, $this->dbhm, $this->groupid);
                if ($g->getPrivate('type') == Group::GROUP_FREEGLE ||
                    $g->getPrivate('type') == Group::GROUP_REUSE
                ) {
                    $this->type = $this->determineType($this->subject);
                }

                if ($source == IncomingMessage::YAHOO_PENDING || $source == IncomingMessage::YAHOO_APPROVED) {
                    # Make sure we have a user and a membership for the originator of this message; they were a member
                    # at the time they sent this.  If they have since left we'll pick that up later via a sync.
                    $u = new User($this->dbhr, $this->dbhm);
                    $userid = $u->findByEmail($this->fromaddr);

                    if (!$userid) {
                        # We don't know them.  Add.
                        #
                        # We don't have a first and last name, so use what we have. If the friendly name is set to an
                        # email address, take the first part.
                        $name = $this->fromname;
                        if (preg_match('/(.*)@/', $name, $matches)) {
                            $name = $matches[1];
                        }

                        if ($userid = $u->create(NULL, NULL, $name)) {
                            # If any of these fail, then we'll pick it up later when we do a sync with the source group,
                            # so no need for a transaction.
                            $u = new User($this->dbhr, $this->dbhm, $userid);
                            $u->addEmail($this->fromaddr, TRUE);
                            $l = new Log($this->dbhr, $this->dbhm);
                            $l->log([
                                'type' => Log::TYPE_USER,
                                'subtype' => Log::SUBTYPE_CREATED,
                                'message_incoming' => $this->id,
                                'user' => $userid,
                                'text' => 'First seen on incoming message',
                                'group' => $this->groupid
                            ]);

                            $u->addMembership($this->groupid);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getSourceheader()
    {
        return $this->sourceheader;
    }

    # Save a parsed message to the DB
    public function save() {
        # Save into the incoming messages table.
        $sql = "INSERT INTO messages_incoming (date, groupid, source, sourceheader, message, envelopefrom, envelopeto, fromname, fromaddr, subject, messageid, tnpostid, textbody, htmlbody, type, yahoopendingid, yahooreject, yahooapprove) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
        $rc = $this->dbhm->preExec($sql, [
            $this->date,
            $this->groupid,
            $this->source,
            $this->sourceheader,
            $this->message,
            $this->envelopefrom,
            $this->envelopeto,
            $this->fromname,
            $this->fromaddr,
            $this->subject,
            $this->messageid,
            $this->tnpostid,
            $this->textbody,
            $this->htmlbody,
            $this->type,
            $this->yahoopendingid,
            $this->yahooreject,
            $this->yahooapprove
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
                'text' => $this->messageid,
                'group' => $this->groupid
            ]);
        }

        # Save the attachments.
        #
        # If we crash or fail at this point, we would have mislaid an attachment for a message.  That's not great, but the
        # perf cost of a transaction for incoming messages is significant, and we can live with it.
        foreach ($this->attachments as $att) {
            /** @var Attachment $att */
            $ct = $att->getContentType();
            $fn = $this->attach_dir . DIRECTORY_SEPARATOR . $att->getFilename();
            $len = filesize($fn);
            $sql = "INSERT INTO messages_attachments (incomingid, contenttype, data) VALUES (?,?,LOAD_FILE(?));";
            $this->dbhm->preExec($sql, [
                $this->id,
                $ct,
                $fn
            ]);
        }

        # Also save into the history table, for spamc checking.
        $sql = "INSERT INTO messages_history (groupid, source, message, envelopefrom, envelopeto, fromname, fromaddr, subject, prunedsubject, messageid, textbody, htmlbody, incomingid) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?);";
        $this->dbhm->preExec($sql, [
            $this->groupid,
            $this->source,
            $this->message,
            $this->envelopefrom,
            $this->envelopeto,
            $this->fromname,
            $this->fromaddr,
            $this->subject,
            $this->getPrunedSubject(),
            $this->messageid,
            $this->textbody,
            $this->htmlbody,
            $this->id
        ]);

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

    public function getHeader($hdr) {
        return($this->parser->getHeader($hdr));
    }

    public function getTo() {
        return(mailparse_rfc822_parse_addresses($this->parser->getHeader('to')));
    }

    function delete($reason = NULL)
    {
        $rc = true;

        if ($this->attach_dir) {
            rrmdir($this->attach_dir);
        }

        if ($this->id) {
            $this->log->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_DELETED,
                'message_incoming' => $this->id,
                'text' => $reason,
                'group' => $this->groupid
            ]);
            $rc = $this->dbhm->preExec("DELETE FROM messages_incoming WHERE id = ?;", [$this->id]);
        }

        return($rc);
    }
}