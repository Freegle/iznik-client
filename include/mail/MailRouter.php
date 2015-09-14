<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/IncomingMessage.php');
require_once(IZNIK_BASE . '/include/message/PendingMessage.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');
require_once(IZNIK_BASE . '/lib/spamc.php');

# This class routes an incoming message
class MailRouter
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $msg;
    private $spamc;
    private $spam;

    /**
     * @param mixed $spamc
     */
    public function setSpamc($spamc)
    {
        $this->spamc = $spamc;
    }

    /**
     * @param mixed $msg
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    }

    const FAILURE = "Failure";
    const INCOMING_SPAM = "IncomingSpam";
    const APPROVED = "Approved";
    const PENDING = 'Pending';
    const TO_USER = "ToUser";

    function __construct($dbhr, $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($this->dbhr, $this->dbhm);
        $this->spamc = new spamc;
        $this->spam = new Spam($this->dbhr, $this->dbhm);

        if ($id) {
            $this->msg = new IncomingMessage($this->dbhr, $this->dbhm, $id);
        } else {
            $this->msg = new IncomingMessage($this->dbhr, $this->dbhm);
        }
    }

    public function received($source, $from, $to, $msg) {
        # We parse it and save it to the DB.  Then it will get picked up by background
        # processing.
        $this->msg->parse($source, $from, $to, $msg);
        return($this->msg->save());
    }

    private function markAsSpam($reason) {
        # Move into the spamc queue.  Use a transaction to avoid leaving rows lying around if we fail partway
        # through.
        $rc = $this->dbhm->beginTransaction();
        $ret = true;

        if ($rc) {
            $rollback = true;

            # Copy the relevant fields in the row to the table, and add the reason.
            $sql = "INSERT INTO messages_spam (incomingid, arrival, `source`, sourceheader, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      tnpostid, textbody, htmlbody, fromip, reason, type)
                      SELECT id, arrival, `source`, sourceheader, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      tnpostid, textbody, htmlbody, fromip, " . $this->dbhm->quote($reason) .
                " AS reason, type FROM messages_incoming WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql,
                [
                    $this->msg->getID()
                ]);

            $approvedid = $this->dbhm->lastInsertId();

            if ($rc) {
                # If our DB ops fail we drop an attachment - better than failing the message.
                $this->dbhm->preExec("UPDATE messages_attachments SET spamid = NULL, pendingid = ? WHERE incomingid = ?;",
                    [
                        $approvedid,
                        $this->msg->getID()
                    ]);
            }

            if ($rc) {
                $rc = $this->msg->delete();

                if ($rc) {
                    $rc = $this->dbhm->commit();

                    if ($rc) {
                        $rollback = false;
                    }
                }
            }

            if ($rollback) {
                $this->dbhm->rollBack();
                $ret = false;
            }
        }

        return($ret);
    }

    private function markApproved() {
        # A message we are marking as approved may previously have been in our pending queue.  This can happen if a
        # message is handled on another system, e.g. moderated directly on Yahoo.
        #
        # We don't need a transaction for this part.
        $p = new PendingMessage($this->dbhm, $this->dbhm);
        $p->removeApprovedMessage($this->msg);

        # Move into the approved queue.  Use a transaction to avoid leaving rows lying around if we fail partway
        # through.
        $rc = $this->dbhm->beginTransaction();
        $ret = true;

        if ($rc) {
            $rollback = true;

            # Copy the relevant fields in the row to the table.
            $sql = "INSERT INTO messages_approved (incomingid, arrival, source, sourceheader, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      tnpostid, textbody, htmlbody, fromip, type)
                      SELECT id, arrival, source, sourceheader, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      tnpostid, textbody, htmlbody, fromip, type FROM messages_incoming WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql, [ $this->msg->getID() ]);
            $approvedid = $this->dbhm->lastInsertId();

            if ($rc) {
                # If our DB ops fail we drop an attachment - better than failing the message.
                $this->dbhm->preExec("UPDATE messages_attachments SET incomingid = NULL, approvedid = ? WHERE incomingid = ?;",
                    [
                        $approvedid,
                        $this->msg->getID()
                    ]);
            }

            if ($rc) {
                $rc = $this->msg->delete();

                if ($rc) {
                    $rc = $this->dbhm->commit();

                    if ($rc) {
                        $rollback = false;
                    }
                }
            }

            if ($rollback) {
                $this->dbhm->rollBack();
                $ret = false;
            }
        }

        return($ret);
    }

    private function markPending() {
        # Move into the pending queue.  Use a transaction to avoid leaving rows lying around if we fail partway
        # through.
        $rc = $this->dbhm->beginTransaction();
        $ret = true;

        if ($rc) {
            $rollback = true;

            # Copy the relevant fields in the row to the table, and add the reason.
            $sql = "INSERT INTO messages_pending (incomingid, arrival, source, sourceheader, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      tnpostid, textbody, htmlbody, fromip, type)
                      SELECT id, arrival, source, sourceheader, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      tnpostid, textbody, htmlbody, fromip, type FROM messages_incoming WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql, [ $this->msg->getID() ]);
            $approvedid = $this->dbhm->lastInsertId();

            if ($rc) {
                # If our DB ops fail we drop an attachment - better than failing the message.
                $this->dbhm->preExec("UPDATE messages_attachments SET incomingid = NULL, pendingid = ? WHERE incomingid = ?;",
                    [
                        $approvedid,
                        $this->msg->getID()
                    ]);
            }

            if ($rc) {
                $rc = $this->msg->delete();

                if ($rc) {
                    $rc = $this->dbhm->commit();

                    if ($rc) {
                        $rollback = false;
                    }
                }
            }

            if ($rollback) {
                $this->dbhm->rollBack();
                $ret = false;
            }
        }

        return($ret);
    }

    public function route($msg = NULL) {
        # The originator of this message
        # We route messages to one of the following destinations:
        # - to a group
        # - to a user
        # - to a spam queue
        if ($msg) {
            $this->msg = $msg;
        }

        # First check if this message is spam based on our own checks.
        $rc = $this->spam->check($this->msg);
        if ($rc) {
            error_log("Message is spam: " . var_export($rc, true));
            $this->log->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                'message_incoming' => $this->msg->getID(),
                'text' => "Spam check failed: {$rc[1]}",
                'group' => $this->msg->getGroupID()
            ]);

            $this->markAsSpam("Spam check failed: {$rc[1]}");

            $ret = MailRouter::INCOMING_SPAM;
        } else {
            # Now check if we think this is just plain spam.
            $this->spamc->command = 'CHECK';

            if ($this->spamc->filter($this->msg->getMessage())) {
                $spamscore = $this->spamc->result['SCORE'];

                if ($spamscore >= 5) {
                    # This might be spam.  We'll mark it as such, then it will get reviewed.
                    $this->log->log([
                        'type' => Log::TYPE_MESSAGE,
                        'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                        'message_incoming' => $this->msg->getID(),
                        'text' => "SpamAssassin score $spamscore",
                        'group' => $this->msg->getGroupID()
                    ]);

                    if ($this->markAsSpam("SpamAssassin flagged this as likely spam; score $spamscore (high is bad)")) {
                        $ret = MailRouter::INCOMING_SPAM;
                    } else {
                        $this->msg->recordFailure('Failed to mark spam');
                        $ret = MailRouter::FAILURE;
                    }
                } else {
                    # Not obviously spam.
                    #
                    # For now move all pending messages into the pending queue.  This will change when we know the
                    # moderation status of the member and the group settings.
                    # TODO
                    $ret = MailRouter::FAILURE;
                    if ($this->msg->getSource() == IncomingMessage::YAHOO_PENDING &&
                        $this->markPending()) {
                        $ret = MailRouter::PENDING;
                    } else if ($this->markApproved()) {
                        $ret = MailRouter::APPROVED;
                    }
                }
            } else {
                # We have failed to check that this is spam.  Record the failure.
                $this->msg->recordFailure('Spam Assassin check failed');
                $ret = MailRouter::FAILURE;
            }
        }

        error_log("Routed " . $this->msg->getSubject() . " " . $ret);

        return($ret);
    }

    public function routeAll() {
        $msgs = $this->dbhr->preQuery("SELECT id FROM messages_incoming FOR UPDATE;");
        foreach ($msgs as $m) {
            $msg = new IncomingMessage($this->dbhr, $this->dbhm, $m['id']);
            $this->route($msg);
        }
    }
}