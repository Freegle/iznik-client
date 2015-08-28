<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/IncomingMessage.php');
require_once(IZNIK_BASE . '/include/Log.php');
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
            $sql = "INSERT INTO messages_spam (incomingid, arrival, `source`, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      textbody, htmlbody, fromip, reason)
                      SELECT id, arrival, `source`, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      textbody, htmlbody, fromip, " . $this->dbhm->quote($reason) .
                " AS reason FROM messages_incoming WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql,
                [
                    $this->msg->getID()
                ]);

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
        # Move into the approved queue.  Use a transaction to avoid leaving rows lying around if we fail partway
        # through.
        $rc = $this->dbhm->beginTransaction();
        $ret = true;

        if ($rc) {
            $rollback = true;

            # Copy the relevant fields in the row to the table, and add the reason.
            $sql = "INSERT INTO messages_approved (arrival, source, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      textbody, htmlbody, fromip)
                      SELECT arrival, source, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      textbody, htmlbody, fromip FROM messages_incoming WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql, [ $this->msg->getID() ]);

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
                    if ($this->markApproved()) {
                        $ret = MailRouter::APPROVED;
                    } else {
                        $this->msg->recordFailure('Failed to mark approved');
                        $ret = MailRouter::FAILURE;
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