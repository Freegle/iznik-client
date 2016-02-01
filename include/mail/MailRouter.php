<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');
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
    const TO_SYSTEM ='ToSystem';
    const DROPPED ='Dropped';

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
        $rc = $this->msg->parse($source, $from, $to, $msg, $groupid);
        return($rc ? $this->msg->save() : NULL);
    }

    # Public for UT
    public function markAsSpam($type, $reason) {
        return(
            $this->dbhm->preExec("UPDATE messages SET spamtype = ?, spamreason = ? WHERE id = ?;", [
                $type,
                $reason,
                $this->msg->getID()
            ]) &&
            $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Spam' WHERE msgid = ?;", [
                $this->msg->getID()
            ]));
    }

    # Public for UT
    public function markApproved() {
        # It's possible that we had the message in Pending.  Ensure it's gone.
        $sql = "DELETE FROM messages WHERE messageid = ? AND fromaddr LIKE ? AND source = ?;";
        $this->dbhm->preExec($sql, [
            $this->msg->getMessageID(),
            $this->msg->getFromaddr(),
            Message::YAHOO_PENDING
        ]);

        # Now set this message to be in the Approved collection.
        $rc = $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Approved' WHERE msgid = ?;", [
            $this->msg->getID()
        ]);

        return($rc);
    }

    # Public for UT
    public function markPending() {
        return($this->dbhm->preExec("UPDATE messages_groups SET collection = 'Pending' WHERE msgid = ?;", [ $this->msg->getID() ]));
    }

    public function route($msg = NULL, $notspam = FALSE) {
        $ret = NULL;

        # We route messages to one of the following destinations:
        # - to a handler for system messages
        #   - confirmation of Yahoo mod status
        #   - confirmation of Yahoo subscription requests
        # - to a group, either pending or approved
        # - to a user
        # - to a spam queue
        if ($msg) {
            $this->msg = $msg;
        }

        if ($this->msg->getSource() == Message::YAHOO_SYSTEM) {
            $ret = MailRouter::DROPPED;

            # This is a message which is from Yahoo's system, rather than a message for a group.
            $to = $this->msg->getEnvelopeto();
            $from = $this->msg->getEnvelopefrom();
            $replyto = $this->msg->getHeader('reply-to');

            if (preg_match('/modconfirm-(.*)-(.*)-(.*)@/', $to, $matches) !== FALSE && count($matches) == 4) {
                # This purports to be a mail to confirm moderation status on Yahoo.
                $groupid = $matches[1];
                $userid = $matches[2];
                $key = $matches[3];
                #error_log("Confirm moderation status for $userid on $groupid using $key");

                # Get the first header.  This is added by our local EXIM and therefore can't be faked by a remote
                # system.  Check that it comes from Yahoo.
                $rcvd = $this->msg->getHeader('received');
                #error_log("Headers " . var_export($rcvd, true));

                if (preg_match('/from .*yahoo\.com \(/', $rcvd)) {
                    # See if we can find the group with this key.  If not then we just drop it - it's either a fake
                    # or obsolete.
                    $sql = "SELECT id FROM groups WHERE id = ? AND confirmkey = ?;";
                    $groups = $this->dbhr->preQuery($sql, [$groupid, $key]);

                    #error_log("Check key $key for group $groupid");

                    foreach ($groups as $group) {
                        # The confirm looks valid.  Promote this user.  We only promote to moderator because we can't
                        # distinguish between owner and moderator via this route.
                        $u = new User($this->dbhr, $this->dbhm, $userid);

                        if ($u->getPublic()['id'] == $userid) {
                            #error_log("Userid $userid is valid");
                            $role = $u->getRole($groupid, FALSE);
                            #error_log("Role is $role");

                            if ($role == User::ROLE_NONMEMBER) {
                                # We aren't a member yet.  Add ourselves.
                                #
                                # We don't know which email we use but it'll get set on the next sync.
                                #error_log("Not a member yet");
                                $u->addMembership($groupid, User::ROLE_MODERATOR, NULL);
                                $ret = MailRouter::TO_SYSTEM;
                            } else if ($role == User::ROLE_MEMBER) {
                                # We're already a member.  Promote.
                                #error_log("We were a member, promote");
                                $u->setRole(User::ROLE_MODERATOR, $groupid);
                                $ret = MailRouter::TO_SYSTEM;
                            } else {
                                # Mod or owner.  Don't demote owner to a mod!
                                #error_log("Already a mod/owner, no action");
                                $ret = MailRouter::TO_SYSTEM;
                            }
                        }

                        # Key is single use
                        $this->dbhm->preExec("UPDATE groups SET confirmkey = NULL WHERE id = ?;", [$groupid]);
                    }
                }
            } else if ($replyto && preg_match('/confirm-s2-(.*)-(.*)=(.*)@yahoogroups.co.*/', $replyto, $matches) !== FALSE && count($matches) == 4) {
                # This is a request by Yahoo to confirm a subscription for one of our members.  We always do that.
                $this->mail($replyto, $to, "Yes please", "I confirm this");
                $ret = MailRouter::TO_SYSTEM;
            } else if ($replyto && preg_match('/(.*)-acceptsub(.*)@yahoogroups.co.*/', $replyto, $matches) !== FALSE && count($matches) == 3) {
                # This is a notification that a member has applied to the group.
                #
                # TODO There are some slight timing windows in the code below:
                # - the user could be created after us checking that the email is not known
                #
                # The user could also be approved/rejected elsewhere - but that'll sort itself out when we do a sync,
                # or worst case a mod will handle it.
                $ret = MailRouter::DROPPED;
                $all = $this->msg->getMessage();
                $approve = $replyto;
                $reject = NULL;
                $email = NULL;
                $name = NULL;
                $comment = NULL;

                // Looks like this: FreeglePlayground-rejectsub-stiwqcnufdzy3dlyulnumshsrvva@yahoogroups.com
                if (preg_match('/^(.*-rejectsub-.*yahoogroups.*?)($| |=)/im', $all, $matches) && count($matches) == 3) {
                    $reject = trim($matches[1]);
                }

                if (preg_match('/^Email address\: (.*)($| |=)/im', $all, $matches) && count($matches) == 3) {
                    $email = trim($matches[1]);

                    if (preg_match('/(.*) \<(.*)\>/', $email, $matches) && count($matches) == 3) {
                        $name = $matches[1];
                        $email = $matches[2];
                    }
                }

                if (preg_match('/^Comment from user\:(.*?)This membership request/ims', $all, $matches) && count($matches) == 2) {
                    $comment = trim($matches[1]);
                }

                if ($approve && $reject && $email) {
                    $nameshort = $this->msg->getHeader('x-egroups-moderators');
                    $g = new Group($this->dbhr, $this->dbhm);
                    $gid = $g->findByShortName($nameshort);

                    if ($gid) {
                        $g = new Group($this->dbhr, $this->dbhm, $gid);

                        # Check that this user exists.
                        $u = new User($this->dbhr, $this->dbhm);
                        $uid = $u->findByEmail($email);

                        if (!$uid) {
                            # We don't know them yet.  Add them.
                            $u->create(NULL, NULL, $name);
                            $emailid = $u->addEmail($email);
                        } else {
                            $u = new User($this->dbhr, $this->dbhm, $uid);
                            $emailid = $u->getIdForEmail($email)['id'];

                            if ($name) {
                                $u->setPrivate('fullname', $name);
                            }
                        }

                        # Now add them as a pending member.
                        if ($u->addMembership($gid, User::ROLE_MEMBER, $emailid, MembershipCollection::PENDING)) {
                            $u->setMembershipAtt($gid, 'yahooapprove', $approve);
                            $u->setMembershipAtt($gid, 'yahooreject', $reject);
                            $u->setMembershipAtt($gid, 'joincomment', $comment);

                            # We handled it.
                            $ret = MailRouter::TO_SYSTEM;
                        }

                        if ($g->getSetting('autoapprove', [ 'members' => 0])['members']) {
                            # We want to auto-approve members on this group.  This is a feature to work around
                            # a Yahoo issue which means that you can't shift a group from approving members to
                            # not doing so.
                            $u->approve($gid, "Auto-approved", NULL, NULL);
                        }
                    }
                }
            } else {
                $ret = MailRouter::DROPPED;
            }
        } else {
            if (!$notspam) {
                # First check if this message is spam based on our own checks.
                $rc = $this->spam->check($this->msg);
                if ($rc) {
                    $groups = $this->msg->getGroups();

                    if (count($groups) > 0) {
                        foreach ($groups as $groupid) {
                            $this->log->log([
                                'type' => Log::TYPE_MESSAGE,
                                'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                                'msgid' => $this->msg->getID(),
                                'text' => "{$rc[2]}",
                                'groupid' => $this->msg->getGroups()[0]
                            ]);
                        }
                    } else {
                        $this->log->log([
                            'type' => Log::TYPE_MESSAGE,
                            'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                            'msgid' => $this->msg->getID(),
                            'text' => "{$rc[2]}"
                        ]);
                    }

                    $ret = MailRouter::FAILURE;

                    if ($this->markAsSpam($rc[1], $rc[2])) {
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

                            if ($this->markAsSpam(Spam::REASON_SPAMASSASSIN, "SpamAssassin flagged this as possible spam; score $spamscore (high is bad)")) {
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
                $ret = MailRouter::FAILURE;

                if ($this->msg->getSource() == Message::YAHOO_PENDING) {
                    if ($this->markPending()) {
                        $ret = MailRouter::PENDING;
                    }
                } else if ($this->msg->getSource() == Message::YAHOO_APPROVED) {
                    if ($this->markApproved()) {
                        $ret = MailRouter::APPROVED;
                    }
                }
            }
        }

        error_log("Routed " . $this->msg->getSubject() . " " . $ret);

        return($ret);
    }

    public function routeAll() {
        $msgs = $this->dbhr->preQuery("SELECT msgid FROM messages_groups WHERE collection = 'Incoming' AND deleted = 0;");
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
                error_log("Route failed " . $e->getMessage() . " stack " . $e->getTraceAsString());
                $this->dbhm->rollBack();
            }
        }
    }

    # Default mailer is to use the standard PHP one, but this can be overridden in UT.
    private function mailer() {
        call_user_func_array('mail', func_get_args());
    }

    public function mail($to, $from, $subject, $body) {
        $headers = "From: $from <$from>\r\n";

        $this->mailer(
            $to,
            $subject,
            $body,
            $headers,
            "-f$from"
        );
    }
}