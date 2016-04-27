<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');
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
        $ret = NULL;
        $rc = $this->msg->parse($source, $from, $to, $msg, $groupid);
        
        if ($rc) {
            list($id, $already) = $this->msg->save();
            $ret = $id;
        }
        
        return($ret);
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
        # Set this message to be in the Approved collection.
        # TODO Handle message on multiple groups
        $rc = $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Approved' WHERE msgid = ?;", [
            $this->msg->getID()
        ]);

        return($rc);
    }

    # Public for UT
    public function markPending() {
        # Set the message as pending.
        $rc = $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Pending' WHERE msgid = ?;", [ $this->msg->getID() ]);

        # Notify mods of new work
        $groups = $this->msg->getGroups();
        $n = new Notifications($this->dbhr, $this->dbhm);

        foreach ($groups as $groupid) {
            $n->notifyGroupMods($groupid);
        }

        return($rc);
    }

    public function route($msg = NULL, $notspam = FALSE) {
        $ret = NULL;
        $log = FALSE;

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

            if ($log) { error_log("To is $to"); }

            if (preg_match('/modconfirm-(.*)-(.*)-(.*)@/', $to, $matches) !== FALSE && count($matches) == 4) {
                # This purports to be a mail to confirm moderation status on Yahoo.
                $groupid = $matches[1];
                $userid = $matches[2];
                $key = $matches[3];
                if ($log) { error_log("Confirm moderation status for $userid on $groupid using $key"); }

                # Get the first header.  This is added by our local EXIM and therefore can't be faked by a remote
                # system.  Check that it comes from Yahoo.
                $rcvd = $this->msg->getHeader('received');
                if ($log) { error_log("Headers " . var_export($rcvd, true)); }

                if (preg_match('/from .*yahoo\.com \(/', $rcvd)) {
                    # See if we can find the group with this key.  If not then we just drop it - it's either a fake
                    # or obsolete.
                    $sql = "SELECT id FROM groups WHERE id = ? AND confirmkey = ?;";
                    $groups = $this->dbhr->preQuery($sql, [$groupid, $key]);

                    if ($log) { error_log("Check key $key for group $groupid"); }

                    foreach ($groups as $group) {
                        # The confirm looks valid.  Promote this user.  We only promote to moderator because we can't
                        # distinguish between owner and moderator via this route.
                        $u = new User($this->dbhr, $this->dbhm, $userid);

                        if ($u->getPublic()['id'] == $userid) {
                            if ($log) { error_log("Userid $userid is valid"); }
                            $role = $u->getRole($groupid, FALSE);
                            if ($log) { error_log("Role is $role"); }

                            if ($role == User::ROLE_NONMEMBER) {
                                # We aren't a member yet.  Add ourselves.
                                #
                                # We don't know which email we use but it'll get set on the next sync.
                                if ($log) { error_log("Not a member yet"); }
                                $u->addMembership($groupid, User::ROLE_MODERATOR, NULL);
                                $ret = MailRouter::TO_SYSTEM;
                            } else if ($role == User::ROLE_MEMBER) {
                                # We're already a member.  Promote.
                                if ($log) { error_log("We were a member, promote"); }
                                $u->setRole(User::ROLE_MODERATOR, $groupid);
                                $ret = MailRouter::TO_SYSTEM;
                            } else {
                                # Mod or owner.  Don't demote owner to a mod!
                                if ($log) { error_log("Already a mod/owner, no action"); }
                                $ret = MailRouter::TO_SYSTEM;
                            }
                        }

                        # Key is single use
                        $this->dbhm->preExec("UPDATE groups SET confirmkey = NULL WHERE id = ?;", [$groupid]);
                    }
                }
            } else if ($replyto && preg_match('/confirm-s2-(.*)-(.*)=(.*)@yahoogroups.co.*/', $replyto, $matches) !== FALSE && count($matches) == 4) {
                # This is a request by Yahoo to confirm a subscription for one of our members.  We always do that.
                if ($log) { error_log("Confirm subscription"); }

                for ($i = 0; $i < 10; $i++) {
                    # Yahoo is sluggish - sending the confirm multiple times helps.
                    $this->mail($replyto, $to, "Yes please", "I confirm this");
                }

                $u = new User($this->dbhr, $this->dbhm);
                $uid = $u->findByEmail($to);
                $this->log([
                    'type' => Log::TYPE_USER,
                    'subtype' => Log::SUBTYPE_YAHOO_CONFIRMED,
                    'user' => $uid,
                    'text' => $to
                ]);

                $ret = MailRouter::TO_SYSTEM;
            } else if ($replyto && preg_match('/confirm-invite-(.*)-(.*)=(.*)@yahoogroups.co.*/', $replyto, $matches) !== FALSE && count($matches) == 4) {
                # This is an invitation by Yahoo to join a group, triggered by us in triggerYahooApplication.
                if ($log) { error_log("Confirm invitation"); }

                for ($i = 0; $i < 10; $i++) {
                    # Yahoo is sluggish - sending the confirm multiple times helps.
                    $this->mail($replyto, $to, "Yes please", "I confirm this");
                }

                $u = new User($this->dbhr, $this->dbhm);
                $uid = $u->findByEmail($to);
                $this->log([
                    'type' => Log::TYPE_USER,
                    'subtype' => Log::SUBTYPE_YAHOO_CONFIRMED,
                    'user' => $uid,
                    'text' => $to
                ]);

                $ret = MailRouter::TO_SYSTEM;
            } else if ($replyto && preg_match('/(.*)-acceptsub(.*)@yahoogroups.co.*/', $replyto, $matches) !== FALSE && count($matches) == 3) {
                # This is a notification that a member has applied to the group.
                #
                # TODO There are some slight timing windows in the code below:
                # - the user could be created after us checking that the email is not known
                #
                # The user could also be approved/rejected elsewhere - but that'll sort itself out when we do a sync,
                # or worst case a mod will handle it.
                if ($log) { error_log("Member applied to group"); }
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
                        $email = $matches[2];

                        if (strpos($email, '-owner@yahoogroups') === FALSE) {
                            $name = $matches[1];
                        }
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
                            $u->create(NULL, NULL, $name, "Yahoo application from $email to $nameshort");
                            $emailid = $u->addEmail($email, 0);
                        } else {
                            $u = new User($this->dbhr, $this->dbhm, $uid);
                            $emailid = $u->getIdForEmail($email)['id'];

                            if ($name && stripos('FBUser', $name) === FALSE) {
                                $u->setPrivate('fullname', $name);
                            }
                        }

                        $notify = FALSE;

                        # Now add them as a pending member.
                        if ($u->addMembership($gid, User::ROLE_MEMBER, $emailid, MembershipCollection::PENDING)) {
                            $u->setYahooMembershipAtt($gid, $emailid, 'yahooapprove', $approve);
                            $u->setYahooMembershipAtt($gid, $emailid, 'yahooreject', $reject);
                            $u->setYahooMembershipAtt($gid, $emailid, 'joincomment', $comment);

                            # Notify mods of new work
                            $notify = TRUE;

                            # We handled it.
                            $ret = MailRouter::TO_SYSTEM;
                        }

                        if ($g->getSetting('autoapprove', ['members' => 0])['members']) {
                            # We want to auto-approve members on this group.  This is a feature to work around
                            # a Yahoo issue which means that you can't shift a group from approving members to
                            # not doing so.
                            $u->approve($gid, "Auto-approved", NULL, NULL);
                            $notify = FALSE;
                        }

                        if ($notify) {
                            $n = new Notifications($this->dbhr, $this->dbhm);
                            $n->notifyGroupMods($gid);
                        }
                    }
                }
            } else if (preg_match('/New (.*) member/', $this->msg->getSubject(), $matches)) {
                $nameshort = $matches[1];
                if ($log) { error_log("New member joined $nameshort"); }
                $all = $this->msg->getMessage();

                if (preg_match('/^(.*) joined your/m', $all, $matches)) {
                    $email = $matches[1];
                    if ($log) { error_log("Email is $email"); }
                    $g = new Group($this->dbhr, $this->dbhm);
                    $gid = $g->findByShortName($nameshort);

                    if ($gid) {
                        $u = new User($this->dbhr, $this->dbhm);
                        $uid = $u->findByEmail($email);

                        if ($uid) {
                            # We have the user and the group.  Mark the membership as no longer pending (if
                            if ($log) { error_log("Found them $uid"); }
                            $u = new User($this->dbhr, $this->dbhm, $uid);

                            $u->markYahooApproved($gid);

                            # Dispatch any messages which are queued awaiting this group membership.
                            $u->submitYahooQueued($gid);
                        }

                        $ret = MailRouter::TO_SYSTEM;
                    }
                }
            } else if (preg_match('/Request to join (.*)/', $this->msg->getSubject(), $matches)) {
                # We get this if we respond to the confirmation multiple times (which we do) and
                # we haven't got the new member notification in the previous arm (which we might
                # not).  It means that we are already a member, so we can treat it as a confirmation.
                $nameshort = $matches[1];
                if ($log) { error_log("Request to join $nameshort"); }
                $all = $this->msg->getMessage();

                if (preg_match('/Because you are already a member/m', $all, $matches)) {
                    if ($log) { error_log("Already a member"); }
                    $g = new Group($this->dbhr, $this->dbhm);
                    $gid = $g->findByShortName($nameshort);

                    if ($gid) {
                        $u = new User($this->dbhr, $this->dbhm);
                        $uid = $u->findByEmail($to);

                        if ($uid) {
                            # We have the user and the group.  Mark the membership as no longer pending.
                            if ($log) { error_log("Found them $uid"); }
                            $u = new User($this->dbhr, $this->dbhm, $uid);
                            $u->markYahooApproved($gid);

                            # Dispatch any messages which are queued awaiting this group membership.
                            $u->submitYahooQueued($gid);
                        }

                        $ret = MailRouter::TO_SYSTEM;
                    }
                }
            } else {
                if ($log) { error_log("Dropped 1"); }
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

                    error_log("Classified as spam {$rc[2]}");
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
                                error_log("Failed to mark as spam");
                                $this->msg->recordFailure('Failed to mark spam');
                                $ret = MailRouter::FAILURE;
                            }
                        }
                    } else {
                        # We have failed to check that this is spam.  Record the failure.
                        error_log("Failed to check spam");
                        $this->msg->recordFailure('Spam Assassin check failed');
                        $ret = MailRouter::FAILURE;
                    }
                }
            }

            if (!$ret) {
                # Not obviously spam.
                $groups = $this->msg->getGroups();
                error_log("Groups " . var_export($groups, TRUE));
                if ($log) { error_log("Not obviously spam, groups " . var_export($groups, TRUE)); }

                if (count($groups) > 0) {
                    # We're expecting to do something with this.
                    $ret = MailRouter::FAILURE;

                    if ($this->msg->getSource() == Message::YAHOO_PENDING) {
                        if ($log) { error_log("Mark as pending"); }
                        if ($this->markPending()) {
                            $ret = MailRouter::PENDING;
                        }
                    } else if ($this->msg->getSource() == Message::YAHOO_APPROVED) {
                        if ($log) { error_log("Mark as approved"); }
                        if ($this->markApproved()) {
                            $ret = MailRouter::APPROVED;
                        }
                    }
                } else {
                    # It's not to one of our groups - but it could be a reply to one of our users.
                    #error_log("Look for reply");
                    $u = new User($this->dbhr, $this->dbhm);
                    $to = $this->msg->getEnvelopeto();
                    $uid = $u->findByEmail($to);
                    $ret = MailRouter::DROPPED;

                    if ($uid) {
                        # This is to one of our users.  We try to pair it as best we can with one of the posts.
                        $original = $this->msg->findFromReply($uid);
                        #error_log("Paired with $original");

                        if ($original) {
                            # We've found (probably) the original message to which this is a reply.
                            $ret = MailRouter::TO_USER;

                            # Try to get the text we care about by stripping out quoted text.  This can't be
                            # perfect - quoting varies and it's a well-known hard problem.
                            $htmlbody = $this->msg->getHtmlbody();
                            $textbody = $this->msg->getTextbody();

                            if ($htmlbody && !$textbody) {
                                $html = new \Html2Text\Html2Text($htmlbody);
                                $textbody = $html->getText();
                                #error_log("Converted HTML text $textbody");
                            }

                            $textbody = trim(preg_replace('#(^\w.+:\n)?(^>.*(\n|$))+#mi', "", $textbody));

                            #error_log("Pruned text to $textbody");

                            # Get/create the chat room between the two users.
                            #error_log("Create chat between " . $this->msg->getFromuser() . " and " . $uid);
                            $r = new ChatRoom($this->dbhr, $this->dbhm);
                            $rid = $r->createConversation($this->msg->getFromuser(), $uid);
                            #error_log("Got chat id $rid");

                            if ($rid) {
                                # And now add our text into the chat room as a message.  This will notify them.
                                $m = new ChatMessage($this->dbhr, $this->dbhm);
                                $mid = $m->create($rid, $this->msg->getFromuser(), $textbody, FALSE, $this->msg->getID());
                                #error_log("Created chat message $mid");
                            }
                        }
                    }
                }
            }
        }

        # Dropped messages will get tidied up by an event in the DB, but we leave them around in case we need to
        # look at them for PD.
        error_log("Routed #" . $this->msg->getID(). " " . $this->msg->getMessageID() . " " . $this->msg->getSubject() . " " . $ret);

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
                error_log("Route #" . $this->msg->getID() . " failed " . $e->getMessage() . " stack " . $e->getTraceAsString());
                $this->dbhm->rollBack();
            }
        }
    }

    # Default mailer is to use the standard PHP one, but this can be overridden in UT.
    public function mailer() {
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