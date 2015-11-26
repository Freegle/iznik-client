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

    public function received($source, $from, $to, $msg, $groupid = NULL) {
        # We parse it and save it to the DB.  Then it will get picked up by background
        # processing.
        #
        # We have a groupid override because it's possible that we are syncing a message
        # from a group which has changed name and the To field might therefore not match
        # a current group name.
        $this->msg->parse($source, $from, $to, $msg, $groupid);
        return($this->msg->save());
    }

    # Public for UT
    public function markAsSpam($reason) {
        error_log("Mark " . $this->msg->getID() . " as spam");
        return(
            $this->dbhm->preExec("UPDATE messages SET spamreason = ? WHERE id = ?;", [
                $reason,
                $this->msg->getID()
            ]) &&
            $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Spam' WHERE msgid = ?;", [
                $this->msg->getID()
            ]));
    }

    # Public for UT
    public function markApproved() {
        return($this->dbhm->preExec("UPDATE messages_groups SET collection = 'Approved' WHERE msgid = ?;", [ $this->msg->getID() ]));
    }

    # Public for UT
    public function markPending() {
        return($this->dbhm->preExec("UPDATE messages_groups SET collection = 'Pending' WHERE msgid = ?;", [ $this->msg->getID() ]));
    }

    public function route($msg = NULL, $notspam = FALSE) {
        $ret = NULL;

        # We route messages to one of the following destinations:
        # - to a group
        # - to a user
        # - to a spam queue
        if ($msg) {
            $this->msg = $msg;
        }

        if (!$notspam) {
            # First check if this message is spam based on our own checks.
            $rc = $this->spam->check($this->msg);
            if ($rc) {
                $this->log->log([
                    'type' => Log::TYPE_MESSAGE,
                    'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                    'msgid' => $this->msg->getID(),
                    'text' => "{$rc[1]}",
                    'groupid' => $this->msg->getGroups()[0]
                ]);

                $ret = MailRouter::FAILURE;

                if ($this->markAsSpam("{$rc[1]}")) {
                    $ret = MailRouter::INCOMING_SPAM;
                }
            } else {
                # Now check if we think this is just plain spam.
                $this->spamc->command = 'CHECK';

                if ($this->spamc->filter($this->msg->getMessage())) {
                    $spamscore = $this->spamc->result['SCORE'];

                    if ($spamscore >= 8) {
                        # This might be spam.  We'll mark it as such, then it will get reviewed.
                        $groups = $this->msg->getGroups();

                        if (count($groups) > 0) {
                            foreach ($groups as $groupid) {
                                $this->log->log([
                                    'type' => Log::TYPE_MESSAGE,
                                    'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                                    'msgid' => $this->msg->getID(),
                                    'text' => "SpamAssassin score $spamscore",
                                    'groupid' => $groupid
                                ]);
                            }
                        } else {
                            $this->log->log([
                                'type' => Log::TYPE_MESSAGE,
                                'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                                'msgid' => $this->msg->getID(),
                                'text' => "SpamAssassin score $spamscore"
                            ]);
                        }

                        if ($this->markAsSpam("SpamAssassin flagged this as possible spam; score $spamscore (high is bad)")) {
                            $ret = MailRouter::INCOMING_SPAM;
                        } else {
                            $this->msg->recordFailure('Failed to mark spam');
                            $ret = MailRouter::FAILURE;
                        }
                    }
                } else {
                    # We have failed to check that this is spam.  Record the failure.
                    $this->msg->recordFailure('Spam Assassin check failed');
                    $ret = MailRouter::FAILURE;
                }
            }
        }

        if (!$ret) {
            # Not obviously spam.
            #
            # For now move all pending messages into the pending queue.  This will change when we know the
            # moderation status of the member and the group settings.
            # TODO
            $ret = MailRouter::FAILURE;
            if ($this->msg->getSource() == Message::YAHOO_PENDING &&
                $this->markPending()
            ) {
                $ret = MailRouter::PENDING;
            } else if ($this->markApproved()) {
                $ret = MailRouter::APPROVED;
            } else {
                $ret = MailRouter::FAILURE;
            }
        }

        error_log("Routed " . $this->msg->getSubject() . " " . $ret);

        return($ret);
    }

    public function routeAll() {
        $msgs = $this->dbhr->preQuery("SELECT msgid FROM messages_groups WHERE collection = 'Incoming';");
        foreach ($msgs as $m) {
            try {
                // @codeCoverageIgnoreStart This seems to be needed due to a presumed bug in phpUnit.  This line
                // doesn't show as covered even though the next one does, which is clearly not possible.
                $msg = new Message($this->dbhr, $this->dbhm, $m['msgid']);
                // @codeCoverageIgnoreEnd

                if (!$msg->getDeleted()) {
                    $this->route($msg);
                }
            } catch (Exception $e) {
                # Ignore this and continue routing the rest.
                error_log("Route failed " . $e->getMessage());
                $this->dbhm->rollBack();
            }
        }
    }
}