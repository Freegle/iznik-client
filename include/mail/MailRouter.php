<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
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

    /**
     * @param LoggedPDO $dbhn
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

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
            $this->msg = new Message($this->dbhr, $this->dbhm, $id);
        } else {
            $this->msg = new Message($this->dbhr, $this->dbhm);
        }
    }

    public function received($source, $from, $to, $msg) {
        # We parse it and save it to the DB.  Then it will get picked up by background
        # processing.
        $this->msg->parse($source, $from, $to, $msg);
        return($this->msg->save());
    }

    private function markAsSpam($reason) {
        return($this->dbhm->preExec("UPDATE messages SET collection = 'Spam' WHERE id = ?;", [ $this->id ]));
    }

    private function markApproved() {
        # A message we are marking as approved may previously have been in our pending queue.  This can happen if a
        # message is handled on another system, e.g. moderated directly on Yahoo.
        #
        # We don't need a transaction for this - transactions aren't great for scalability and worst case we
        # leave a spurious message around which a mod will handle.
        $p = new Message($this->dbhm, $this->dbhm);
        $p->removeByMessageID($this->msg);
        $s = new Message($this->dbhm, $this->dbhm);
        $s->removeByMessageID($this->msg);

        return($this->dbhm->preExec("UPDATE messages SET collection = 'Approved' WHERE id = ?;", [ $this->id ]));
    }

    private function markPending() {
        return($this->dbhm->preExec("UPDATE messages SET collection = 'Pending' WHERE id = ?;", [ $this->id ]));
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
            $this->log->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                'msgid' => $this->msg->getID(),
                'text' => "{$rc[1]}",
                'groupid' => $this->msg->getGroupID()
            ]);

            $this->markAsSpam("{$rc[1]}");

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
                        'msgid' => $this->msg->getID(),
                        'text' => "SpamAssassin score $spamscore",
                        'groupid' => $this->msg->getGroupID()
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
                    if ($this->msg->getSource() == Message::YAHOO_PENDING &&
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
        $msgs = $this->dbhr->preQuery("SELECT id FROM messages WHERE collection = 'Incoming';");
        foreach ($msgs as $m) {
            try {
                $msg = new Message($this->dbhr, $this->dbhm, $m['id']);
                $this->route($msg);
            } catch (Exception $e) {
                # Ignore this and continue routing the rest.
                error_log("Route failed " . $e->getMessage());
                $this->dbhm->rollBack();
            }
        }
    }
}