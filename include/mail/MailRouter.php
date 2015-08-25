<?php

require_once(BASE_DIR . '/include/utils.php');
require_once(BASE_DIR . '/include/message/IncomingMessage.php');
require_once(BASE_DIR . '/include/Log.php');
require_once(BASE_DIR . '/lib/spamc.php');

# This class routes an incoming message
class MailRouter
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $msg;
    private $spam;

    /**
     * @param mixed $spam
     */
    public function setSpam($spam)
    {
        $this->spam = $spam;
    }

    /**
     * @param mixed $msg
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    }

    const FAILURE = -1;
    const INCOMING_SPAM = 1;
    const TO_GROUP = 2;
    const TO_USER = 3;

    function __construct($dbhr, $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($this->dbhr, $this->dbhm);
        $this->spam = new spamc;

        if ($id) {
            $this->msg = new IncomingMessage($this->dbhr, $this->dbhm, $id);
        } else {
            $this->msg = new IncomingMessage($this->dbhr, $this->dbhm);
        }
    }

    public function received($from, $to, $msg) {
        # We parse it and save it to the DB.  Then it will get picked up by background
        # processing.
        $this->msg->parse($from, $to, $msg);
        $this->msg->save();
    }

    private function markAsSpam($reason) {
        # Move into the spam queue.  Use a transaction to avoid leaving rows lying around if we fail partway
        # through.
        $rc = $this->dbhm->beginTransaction();
        $ret = true;

        if ($rc) {
            $rollback = true;

            # Copy the relevant fields in the row to the table, and add the reason.
            $rc = $this->dbhm->preExec("INSERT INTO messages_spam (arrival, source, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      textbody, htmlbody, reason)
                      SELECT arrival, source, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      textbody, htmlbody, " . $this->dbhm->quote($reason) .
                    " AS reason FROM messages_incoming WHERE id = ?;",
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
                      textbody, htmlbody)
                      SELECT arrival, source, message,
                      envelopefrom, fromname, fromaddr, envelopeto, groupid, subject, messageid,
                      textbody, htmlbody AS reason FROM messages_incoming WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql, [ $this->msg->getID() ]);

            if ($rc) {
                $rc = $this->msg->delete();
                error_log("Delete returned $rc");

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

    public function route() {
        # We route messages to one of the following destinations:
        # - to a group
        # - to a user
        # - to a spam queue
        #
        # First, check if we think this is just plain spam.
        $this->spam->command = 'CHECK';

        if ($this->spam->filter($this->msg->getMessage())) {
            $spamscore = $this->spam->result['SCORE'];

            if ($spamscore >= 5) {
                # This might be spam.  We'll mark it as such, then it will get reviewed.
                $this->log->log([
                    'type' => Log::TYPE_MESSAGE,
                    'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                    'message_incoming' => $this->msg->getID(),
                    'text' => "SpamAssassin score $spamscore"
                ]);

                if ($this->markAsSpam("SpamAssassin flagged this as likely spam; score $spamscore (high is bad)")) {
                    $ret = MailRouter::INCOMING_SPAM;
                } else {
                    $ret = MailRouter::FAILURE;
                }
            } else {
                # Not obviously spam.
                if ($this->markApproved()) {
                    $ret = MailRouter::TO_GROUP;
                } else {
                    $ret = MailRouter::FAILURE;
                }
            }
        } else {
            # We have failed to check that this is spam.  Record the failure.
            $this->msg->recordFailure('Spam Assassin check failed');
            $ret = MailRouter::FAILURE;
        }

        return($ret);
    }
}