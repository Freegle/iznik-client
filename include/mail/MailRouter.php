<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');
require_once(IZNIK_BASE . '/include/user/PushNotifications.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');
require_once(IZNIK_BASE . '/include/mail/Digest.php');
require_once(IZNIK_BASE . '/include/mail/EventDigest.php');
require_once(IZNIK_BASE . '/include/mail/VolunteeringDigest.php');
require_once(IZNIK_BASE . '/include/mail/Newsletter.php');
require_once(IZNIK_BASE . '/include/mail/Relevant.php');

if (!class_exists('spamc')) {
    require_once(IZNIK_BASE . '/lib/spamc.php');
}

# This class routes an incoming message
class MailRouter
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $msg;
    private $spamc;

    CONST ASSASSIN_THRESHOLD = 8;

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
    const TO_VOLUNTEERS = "ToVolunteers";

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
        $rc = $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Approved', approvedat = NOW() WHERE msgid = ?;", [
            $this->msg->getID()
        ]);

        # Now visible in search
        $this->msg->index();

        return($rc);
    }

    # Public for UT
    public function markPending($force, $onyahoo) {
        # Set the message as pending.
        #
        # If we're forced we just do it.  The force is to allow us to move from Spam to Pending.
        #
        # If we're not forced, then the mainline case is that this is an incoming message.  We might get a
        # pending notification after approving it, and in that case we don't generally want to move it back to
        # pending.  However if we approved/rejected it a while ago, then it's likely that the action didn't stick (for
        # example if we approved by email to Yahoo and Yahoo ignored it).  In that case we should move it
        # back to Pending, otherwise it will stay stuck on Yahoo.
        $overq = '';

        if (!$force) {
            $groups = $this->dbhr->preQuery("SELECT collection, approvedat, rejectedat FROM messages_groups WHERE msgid = ? AND ((collection = 'Approved' AND (approvedat IS NULL OR approvedat < DATE_SUB(NOW(), INTERVAL 2 HOUR))) OR (collection = 'Rejected' AND (rejectedat IS NULL OR rejectedat < DATE_SUB(NOW(), INTERVAL 2 HOUR))));",  [ $this->msg->getID() ]);
            $overq = count($groups) == 0 ? " AND collection = 'Incoming' " : '';
            #error_log("MarkPending " . $this->msg->getID() . " from collection $overq");
        }

        $rc = $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Pending', senttoyahoo = ? WHERE msgid = ? $overq;", [
            $onyahoo ? 1 : 0,
            $this->msg->getID()
        ]);

        # Notify mods of new work
        $groups = $this->msg->getGroups();
        $n = new PushNotifications($this->dbhr, $this->dbhm);

        foreach ($groups as $groupid) {
            $n->notifyGroupMods($groupid);
        }

        return($rc);
    }

    public function route($msg = NULL, $notspam = FALSE) {
        $ret = NULL;
        $log = TRUE;

        # We route messages to one of the following destinations:
        # - to a handler for system messages
        #   - confirmation of Yahoo mod status
        #   - confirmation of Yahoo subscription requests
        # - to a group, either pending or approved
        # - to group moderators
        # - to a user
        # - to a spam queue
        if ($msg) {
            $this->msg = $msg;
        }

        if ($notspam) {
            # Record that this message has been flagged as not spam.
            if ($this->log) { error_log("Record message as not spam"); }
            $this->msg->setPrivate('spamtype', Spam::REASON_NOT_SPAM, TRUE);
        }

        # Check if we know that this is not spam.  This means if we receive a later copy of it,
        # then we will know that we don't need to spam check it, otherwise we might move it back into spam
        # to the annoyance of the moderators.
        $notspam = $this->msg->getPrivate('spamtype') === Spam::REASON_NOT_SPAM;
        if ($this->log) { error_log("Consider not spam $notspam from " . $this->msg->getPrivate('spamtype')); }

        $to = $this->msg->getEnvelopeto();
        $from = $this->msg->getEnvelopefrom();
        $replyto = $this->msg->getHeader('reply-to');
        $fromheader = $this->msg->getHeader('from');

        if ($fromheader) {
            $fromheader = mailparse_rfc822_parse_addresses($fromheader);
        }

        if ($this->spam->isSpammer($from)) {
            # Mail from spammer. Drop it.
            $ret = MailRouter::DROPPED;
        } else if ($this->msg->getSource() == Message::YAHOO_SYSTEM) {
            $ret = MailRouter::DROPPED;

            # This is a message which is from Yahoo's system, rather than a message for a group.

            if ($log) { error_log("To is $to "); }

            if (preg_match('/modconfirm-(.*)-(.*)-(.*)@/', $to, $matches) === 1) {
                # This purports to be a mail to confirm moderation status on Yahoo.
                $groupid = $matches[1];
                $userid = $matches[2];
                $key = $matches[3];
                if ($log) {
                    error_log("Confirm moderation status for $userid on $groupid using $key");
                }

                # Get the first header.  This is added by our local EXIM and therefore can't be faked by a remote
                # system.  Check that it comes from Yahoo.
                $rcvd = $this->msg->getHeader('received');
                if ($log) {
                    error_log("Headers " . var_export($rcvd, true));
                }

                if (preg_match('/from .*yahoo\.com \(/', $rcvd)) {
                    # See if we can find the group with this key.  If not then we just drop it - it's either a fake
                    # or obsolete.
                    $sql = "SELECT id FROM groups WHERE id = ? AND confirmkey = ?;";
                    $groups = $this->dbhr->preQuery($sql, [$groupid, $key]);

                    if ($log) {
                        error_log("Check key $key for group $groupid");
                    }

                    foreach ($groups as $group) {
                        # The confirm looks valid.  Promote this user.  We only promote to moderator because we can't
                        # distinguish between owner and moderator via this route.
                        $u = User::get($this->dbhr, $this->dbhm, $userid);

                        if ($u->getPublic()['id'] == $userid) {
                            if ($log) {
                                error_log("Userid $userid is valid");
                            }
                            $role = $u->getRoleForGroup($groupid, FALSE);
                            if ($log) {
                                error_log("Role is $role");
                            }

                            if ($role == User::ROLE_NONMEMBER) {
                                # We aren't a member yet.  Add ourselves.
                                #
                                # We don't know which email we use but it'll get set on the next sync.
                                if ($log) {
                                    error_log("Not a member yet");
                                }
                                $u->addMembership($groupid, User::ROLE_MODERATOR, NULL);
                                $ret = MailRouter::TO_SYSTEM;
                            } else if ($role == User::ROLE_MEMBER) {
                                # We're already a member.  Promote.
                                if ($log) {
                                    error_log("We were a member, promote");
                                }
                                $u->setRole(User::ROLE_MODERATOR, $groupid);
                                $ret = MailRouter::TO_SYSTEM;
                            } else {
                                # Mod or owner.  Don't demote owner to a mod!
                                if ($log) {
                                    error_log("Already a mod/owner, no action");
                                }
                                $ret = MailRouter::TO_SYSTEM;
                            }
                        }

                        # Key is single use after a successful confirm.
                        $this->dbhm->preExec("UPDATE groups SET confirmkey = NULL WHERE id = ?;", [$groupid]);
                        Group::clearCache($groupid);
                    }
                }
            } else if ($fromheader && preg_match('/confirm-nomail(.*)@yahoogroups.co.*/', $fromheader[0]['address'], $matches) === 1) {
                # We have requested to turn off email; conform that.  Only once, as if it keeps happening we'll keep
                # trying to turn it off.
                if ($log) { error_log("Confirm noemail change"); }
                $this->mail($fromheader[0]['address'], $to, "Yes please", "I confirm this");
                $ret = MailRouter::TO_SYSTEM;
            } else if ($replyto && preg_match('/confirm-s2-(.*)-(.*)=(.*)@yahoogroups.co.*/', $replyto, $matches) === 1) {
                # This is a request by Yahoo to confirm a subscription for one of our members.  We always confirm this.
                # If you are tempted to only confirm if the member is pending or approved then be aware that this caused
                # a problem that I can no longer remember and turned out to be a bad idea.
                if ($log) { error_log("Confirm subscription"); }

                if (preg_match('/Please confirm your request to join (.*)/', $this->msg->getSubject(), $matches)) {
                    $groupname = $matches[1];
                    if ($log) { error_log("For group $groupname"); }
                    $g = new Group($this->dbhr, $this->dbhm);
                    $gid = $g->findByShortName($groupname);
                    $g = new Group($this->dbhr, $this->dbhm, $gid);
                    if ($log) { error_log("Found group $gid"); }

                    $u = User::get($this->dbhr, $this->dbhm);
                    $uid = $u->findByEmail($to);
                    $u = User::get($this->dbhr, $this->dbhm, $uid);
                    if ($log) { error_log("Found $uid for $to, onhere " . $g->getPrivate('onhere') . ", pending " . $u->isPendingMember($gid) . " approved " . $u->isApprovedMember($gid)); }

                    if ($g->getPrivate('onhere') && !$u->isRejected($gid)) {
                        if ($log) { error_log("Confirm it"); }
                        $this->mail($replyto, $to, "Yes please", "I confirm this to $replyto");

                        $this->log->log([
                            'type' => Log::TYPE_USER,
                            'subtype' => Log::SUBTYPE_YAHOO_CONFIRMED,
                            'user' => $uid,
                            'text' => $to
                        ]);

                        $ret = MailRouter::TO_SYSTEM;
                    }
                }
            } else if ($replyto && preg_match('/confirm-invite-(.*)-(.*)=(.*)@yahoogroups.co.*/', $replyto, $matches) === 1) {
                # This is an invitation by Yahoo to join a group, triggered by us in triggerYahooApplication.
                if ($log) { error_log("Confirm invitation"); }

                $this->mail($replyto, $to, "Yes please", "I confirm this to $replyto");

                $u = User::get($this->dbhr, $this->dbhm);
                $uid = $u->findByEmail($to);
                $this->log->log([
                    'type' => Log::TYPE_USER,
                    'subtype' => Log::SUBTYPE_YAHOO_CONFIRMED,
                    'user' => $uid,
                    'text' => $to
                ]);

                $ret = MailRouter::TO_SYSTEM;
            } else if ($replyto && preg_match('/confirm-unsub-(.*)-(.*)=(.*)@yahoogroups.co.*/', $replyto, $matches) === 1) {
                # We have tried to unsubscribe from a group - we need to confirm it.
                if ($log) { error_log("Confirm unsubscribe"); }

                $this->mail($replyto, $to, "Yes please", "I confirm this to $replyto");

                $ret = MailRouter::TO_SYSTEM;
            } else if ($replyto && preg_match('/(.*)-acceptsub(.*)@yahoogroups.co.*/', $replyto, $matches) === 1) {
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
                    if ($log) { error_log("Found email $email"); }

                    if (preg_match('/(.*) \<(.*)\>/', $email, $matches) && count($matches) == 3) {
                        $email = $matches[2];
                        if ($log) { error_log("Found second email $email"); }

                        if (strpos($email, '-owner@yahoogroups') === FALSE) {
                            $name = $matches[1];
                            if ($log) { error_log("Found name $name"); }
                        }
                    }
                }

                if (preg_match('/^Comment from user\:(.*?)This membership request/ims', $all, $matches) && count($matches) == 2) {
                    $comment = trim($matches[1]);
                }

                if ($approve && $reject && $email) {
                    $nameshort = $this->msg->getHeader('x-egroups-moderators');
                    $g = Group::get($this->dbhr, $this->dbhm);
                    $gid = $g->findByShortName($nameshort);

                    if ($gid) {
                        $g = Group::get($this->dbhr, $this->dbhm, $gid);

                        # Check that this user exists.
                        $u = User::get($this->dbhr, $this->dbhm);
                        $uid = $u->findByEmail($email);
                        if ($log) { error_log("Found #$uid for $email"); }

                        if (!$uid) {
                            # We don't know them yet.  Add them.
                            $u->create(NULL, NULL, $name, "Yahoo application from $email to $nameshort");
                            $emailid = $u->addEmail($email, 0);
                        } else {
                            $u = User::get($this->dbhr, $this->dbhm, $uid);
                            $emailid = $u->getIdForEmail($email)['id'];

                            error_log("Consider upgrade " . $u->getName(FALSE) . " vs $name");
                            if (!$u->getName(FALSE) && $name && stripos('FBUser', $name) === FALSE) {
                                $u->setPrivate('fullname', $name);
                            }
                        }

                        $notify = FALSE;
                        $waspending = $u->isPendingMember($gid);

                        # Now add them as a pending member.
                        if ($u->addMembership($gid, User::ROLE_MEMBER, $emailid, MembershipCollection::PENDING, NULL, NULL, FALSE)) {
                            $u->setYahooMembershipAtt($gid, $emailid, 'yahooapprove', $approve);
                            $u->setYahooMembershipAtt($gid, $emailid, 'yahooreject', $reject);
                            $u->setYahooMembershipAtt($gid, $emailid, 'joincomment', $comment);

                            # Notify mods of new work
                            $notify = !$waspending;

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
                            $n = new PushNotifications($this->dbhr, $this->dbhm);
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
                    $g = Group::get($this->dbhr, $this->dbhm);
                    $gid = $g->findByShortName($nameshort);

                    if ($gid) {
                        $u = User::get($this->dbhr, $this->dbhm);
                        $uid = $u->findByEmail($email);

                        if ($uid) {
                            # We have the user and the group.
                            if ($log) { error_log("Found them $uid"); }
                            $u = User::get($this->dbhr, $this->dbhm, $uid);

                            $eid = $u->getIdForEmail($email);
                            $eid = $eid ? $eid['id'] : NULL;

                            if (ourDomain($email)) {
                                if ($log) { error_log("$email is ours"); }

                                $cont = TRUE;

                                if (!$u->isPendingMember($gid) && !$u->isApprovedMember($gid)) {
                                    # We've somehow lost the Yahoo membership.
                                    if ($log) { error_log("Readd membership for $email on $gid using $eid"); }
                                    $cont = $u->addMembership($gid, User::ROLE_MEMBER, $eid, MembershipCollection::APPROVED);
                                }

                                if ($cont) {
                                    # Mark the membership as no longer pending.
                                    if ($log) { error_log("Mark $eid on $gid as approved"); }
                                    $u->markYahooApproved($gid, $eid);

                                    # Dispatch any messages which are queued awaiting this group membership.
                                    if ($log) { error_log("Submit"); }
                                    $u->submitYahooQueued($gid);
                                }
                            }
                        }

                        $ret = MailRouter::TO_SYSTEM;
                    }
                }
            } else if (preg_match('/Request to join (.*) approved/', $this->msg->getSubject(), $matches) ||
                preg_match('/Request to join (.*)/', $this->msg->getSubject(), $matches)) {
                # Mainline path for an approval.
                #
                # We also get this if we respond to the confirmation multiple times (which we do) and
                # we haven't got the new member notification in the previous arm (which we might
                # not).  It means that we are already a member, so we can treat it as a confirmation.
                $nameshort = trim($matches[1]);
                if ($log) { error_log("Request to join $nameshort from $to"); }
                $all = $this->msg->getMessage();

                if (preg_match('/Because you are already a member/m', $all, $matches) ||
                    preg_match('/has approved your request for membership/m', $all, $matches)) {
                    if ($log) { error_log("$to Now or already a member of $nameshort"); }
                    $g = Group::get($this->dbhr, $this->dbhm);
                    $gid = $g->findByShortName($nameshort);

                    if ($gid) {
                        $u = User::get($this->dbhr, $this->dbhm);
                        $uid = $u->findByEmail($to);

                        if ($uid) {
                            # We have the user and the group.
                            if ($log) { error_log("Found them $uid"); }
                            $u = User::get($this->dbhr, $this->dbhm, $uid);

                            $eid = $u->getIdForEmail($to);
                            $eid = $eid ? $eid['id'] : NULL;

                            $cont = TRUE;

                            if (!$u->isPendingMember($gid) && !$u->isApprovedMember($gid)) {
                                # We've somehow lost the Yahoo membership.
                                if ($log) { error_log("Readd membership for $to on $gid using $eid"); }
                                $cont = $u->addMembership($gid, User::ROLE_MEMBER, $eid, MembershipCollection::APPROVED);
                            }

                            if ($cont) {
                                # Mark the membership as no longer pending.
                                if ($log) { error_log("Mark $eid on $gid as approved"); }
                                $u->markYahooApproved($gid, $eid);

                                # Dispatch any messages which are queued awaiting this group membership.
                                if ($log) { error_log("Submit"); }
                                $u->submitYahooQueued($gid);
                            }
                        }

                        $ret = MailRouter::TO_SYSTEM;
                    }
                }
            }
        } else if (preg_match('/digestoff-(.*)-(.*)@/', $to, $matches) == 1) {
            # Request to turn email off.
            $uid = intval($matches[1]);
            $groupid = intval($matches[2]);

            if ($uid && $groupid) {
                $d = new Digest($this->dbhr, $this->dbhm);
                $d->off($uid, $groupid);

                $ret = MailRouter::TO_SYSTEM;
            }
        } else if (preg_match('/eventsoff-(.*)-(.*)@/', $to, $matches) == 1) {
            # Request to turn events email off.
            $uid = intval($matches[1]);
            $groupid = intval($matches[2]);

            if ($uid && $groupid) {
                $d = new EventDigest($this->dbhr, $this->dbhm);
                $d->off($uid, $groupid);

                $ret = MailRouter::TO_SYSTEM;
            }
        } else if (preg_match('/newslettersoff-(.*)@/', $to, $matches) == 1) {
            # Request to turn newsletters off.
            $uid = intval($matches[1]);

            if ($uid) {
                $d = new Newsletter($this->dbhr, $this->dbhm);
                $d->off($uid);

                $ret = MailRouter::TO_SYSTEM;
            }
        } else if (preg_match('/relevantoff-(.*)@/', $to, $matches) == 1) {
            # Request to turn "interested in" off.
            $uid = intval($matches[1]);

            if ($uid) {
                $d = new Relevant($this->dbhr, $this->dbhm);
                $d->off($uid);

                $ret = MailRouter::TO_SYSTEM;
            }
        } else if (preg_match('/volunteeringoff-(.*)-(.*)@/', $to, $matches) == 1) {
            # Request to turn volunteering email off.
            $uid = intval($matches[1]);
            $groupid = intval($matches[2]);

            if ($uid && $groupid) {
                $d = new VolunteeringDigest($this->dbhr, $this->dbhm);
                $d->off($uid, $groupid);

                $ret = MailRouter::TO_SYSTEM;
            }
        } else if (preg_match('/notificationmailsoff-(.*)@/', $to, $matches) == 1) {
            # Request to turn notification email off.
            $uid = intval($matches[1]);

            if ($uid) {
                $d = new Notifications($this->dbhr, $this->dbhm);
                $d->off($uid);

                $ret = MailRouter::TO_SYSTEM;
            }
        } else if (preg_match('/(.*)-volunteers@' . GROUP_DOMAIN . '/', $to, $matches) ||
            preg_match('/(.*)-auto@' . GROUP_DOMAIN . '/', $to, $matches)) {
            # Mail to our owner address.  First check if it's spam according to SpamAssassin.
            if ($this->log) { error_log("To volunteers"); }

            $this->spamc->command = 'CHECK';

            $ret = MailRouter::INCOMING_SPAM;

            if ($this->spamc->filter($this->msg->getMessage())) {
                $spamscore = $this->spamc->result['SCORE'];

                if ($spamscore < MailRouter::ASSASSIN_THRESHOLD) {
                    # Now do our own checks.
                    if ($this->log) { error_log("Passed SpamAssassin $spamscore"); }
                    $rc = $this->spam->checkMessage($this->msg);

                    if (!$rc) {
                        $ret = MailRouter::FAILURE;

                        # It's not.  Find the group
                        $g = new Group($this->dbhr, $this->dbhm);
                        $sn = $matches[1];

                        # TODO Remove after 1/1/18
                        $sn = str_ireplace('hertfordfreegle', 'hertford_freegle', $sn);

                        $gid = $g->findByShortName($sn);
                        if ($this->log) { error_log("Found $gid from $sn"); }

                        if ($gid) {
                            # It's one of our groups.  Find the user this is from.
                            $envfrom = $this->msg->getFromaddr();
                            $u = new User($this->dbhr, $this->dbhm);
                            $uid = $u->findByEmail($envfrom);

                            if ($this->log) { error_log("Found $uid from $envfrom"); }

                            # We should always find them as Message::parse should create them
                            if ($uid) {
                                if ($this->log) { error_log("From user $uid to group $gid"); }
                                $u = User::get($this->dbhr, $this->dbhm, $uid);

                                $ret = MailRouter::DROPPED;

                                # Don't want to pass on OOF etc.
                                if (!$this->msg->isAutoreply()) {
                                    # Create/get a change between the sender and the group mods.
                                    $r = new ChatRoom($this->dbhr, $this->dbhm);
                                    $chatid = $r->createUser2Mod($uid, $gid);
                                    if ($this->log) { error_log("Chatid is $chatid"); }

                                    # Now add this message into the chat.  Don't strip quoted as it might be useful -
                                    # one example is twitter email confirmations, where the URL is quoted (weirdly).
                                    $textbody = $this->msg->getTextbody();

                                    $m = new ChatMessage($this->dbhr, $this->dbhm);
                                    $mid = $m->create($chatid, $uid, $textbody, ChatMessage::TYPE_DEFAULT, NULL, FALSE);
                                    if ($this->log) { error_log("Created message $mid"); }

                                    $m->chatByEmail($mid, $this->msg->getID());

                                    # The user sending this is up to date with this conversation.  This prevents us
                                    # notifying her about other messages
                                    $r->mailedLastForUser($uid);

                                    $ret = MailRouter::TO_VOLUNTEERS;
                                }
                            }
                        }
                    }
                }
            }
        } else if (preg_match('/(.*)-subscribe@' . GROUP_DOMAIN . '/', $to, $matches)) {
            $ret = MailRouter::FAILURE;

            # Find the group
            $g = new Group($this->dbhr, $this->dbhm);
            $gid = $g->findByShortName($matches[1]);

            if ($gid && !$g->getPrivate('onyahoo')) {
                # It's one of our groups.  Find the user this is from.
                $envfrom = $this->msg->getEnvelopeFrom();
                $u = new User($this->dbhr, $this->dbhm);
                $uid = $u->findByEmail($envfrom);

                if (!$uid) {
                    # We don't know them yet.
                    $uid = $u->create(NULL, NULL, $this->msg->getFromname(), "Email subscription from $envfrom to " . $g->getPrivate('nameshort'));
                    $u->addEmail($envfrom, 0);
                    $pw = $u->inventPassword();
                    $u->addLogin(User::LOGIN_NATIVE, $uid, $pw);
                    $u->welcome($envfrom, $pw);
                }

                $u = new User($this->dbhr, $this->dbhm, $uid);

                # We should always find them as Message::parse should create them
                if ($u->getId()) {
                    $u->addMembership($gid, User::ROLE_MEMBER, NULL, MembershipCollection::APPROVED, NULL, $envfrom);
                    $ret = MailRouter::TO_SYSTEM;
                }
            }
        } else if (preg_match('/(.*)-unsubscribe@' . GROUP_DOMAIN . '/', $to, $matches)) {
            $ret = MailRouter::FAILURE;

            # Find the group
            $g = new Group($this->dbhr, $this->dbhm);
            $gid = $g->findByShortName($matches[1]);

            if ($gid && !$g->getPrivate('onyahoo')) {
                # It's one of our groups.  Find the user this is from.
                $envfrom = $this->msg->getEnvelopeFrom();
                $u = new User($this->dbhr, $this->dbhm);
                $uid = $u->findByEmail($envfrom);

                if ($uid) {
                    $u = new User($this->dbhr, $this->dbhm, $uid);
                    $u->removeMembership($gid, FALSE, FALSE, $envfrom);
                    $ret = MailRouter::TO_SYSTEM;
                }
            }
        } else {
            # We use SpamAssassin to weed out obvious spam.  We only do a content check if the message subject line is
            # not in the standard format.  Most generic spam isn't in that format, and some of our messages
            # would otherwise get flagged - so this improves overall reliability.
            $contentcheck = !$notspam && !preg_match('/.*?\:(.*)\(.*\)/', $this->msg->getSubject());
            $spamscore = NULL;

            $groups = $this->msg->getGroups(FALSE, FALSE);
            error_log("Got groups " . var_export($groups, TRUE));

            # Check if the group wants us to check for spam.
            # TODO Multiple groups?
            foreach ($groups as $group) {
                $g = Group::get($this->dbhr, $this->dbhm, $group['groupid']);
                $defs = $g->getDefaults();
                $spammers = $g->getSetting('spammers', $defs['spammers']);
                $check = array_key_exists('messagereview', $spammers) ? $spammers['messagereview'] : $defs['spammers']['messagereview'];
                $notspam = $check ? $notspam : TRUE;
                #error_log("Consider spam review $notspam from $check, " . var_export($spammers, TRUE));
            }

            if (!$notspam) {
                # First check if this message is spam based on our own checks.
                $rc = $this->spam->checkMessage($this->msg);
                if ($rc) {
                    if (count($groups) > 0) {
                        foreach ($groups as $group) {
                            $this->log->log([
                                'type' => Log::TYPE_MESSAGE,
                                'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                                'msgid' => $this->msg->getID(),
                                'text' => "{$rc[2]}",
                                'groupid' => $group['groupid']
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
                } else if ($contentcheck) {
                    # Now check if we think this is spam according to SpamAssassin.
                    $this->spamc->command = 'CHECK';

                    if ($this->spamc->filter($this->msg->getMessage())) {
                        $spamscore = $this->spamc->result['SCORE'];

                        if ($spamscore >= MailRouter::ASSASSIN_THRESHOLD && ($this->msg->getEnvelopefrom() != 'from@test.com')) {
                            # This might be spam.  We'll mark it as such, then it will get reviewed.
                            #
                            # Hacky if test to stop our UT messages getting flagged as spam unless we want them to be.
                            $groups = $this->msg->getGroups(FALSE, FALSE);

                            if (count($groups) > 0) {
                                foreach ($groups as $group) {
                                    $this->log->log([
                                        'type' => Log::TYPE_MESSAGE,
                                        'subtype' => Log::SUBTYPE_CLASSIFIED_SPAM,
                                        'msgid' => $this->msg->getID(),
                                        'text' => "SpamAssassin score $spamscore",
                                        'groupid' => $group['groupid']
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
                        # We have failed to check that this is spam.  Record the failure but carry on.
                        error_log("Failed to check spam " . $this->spamc->err);
                        $this->msg->recordFailure('Spam Assassin check failed ' . $this->spamc->err);
                    }
                }
            }

            if (!$ret) {
                # Not obviously spam.
                if ($log) { error_log("Not obviously spam, groups " . var_export($groups, TRUE)); }

                if (count($groups) > 0) {
                    # We're expecting to do something with this.
                    $envto = $this->msg->getEnvelopeto();
                    if ($log) { error_log("To a group; to user $envto source " . $this->msg->getSource()); }
                    $ret = MailRouter::FAILURE;
                    $source = $this->msg->getSource();

                    if ($source == Message::YAHOO_PENDING || ($notspam && $source == Message::PLATFORM)) {
                        if ($log) { error_log("Source header " . $this->msg->getSourceheader());}
                        $handled = FALSE;

                        if ($this->msg->getSourceheader() == Message::PLATFORM) {
                            # Platform messages might already have been approved on here before we received them back.  In
                            # that case we need to approve them on Yahoo too.
                            foreach ($groups as $group) {
                                if ($this->log) { error_log("{$group['groupid']} collection {$group['collection']}");}

                                if ($group['collection'] == MessageCollection::APPROVED) {
                                    # We've approved it on here.  Let Yahoo know to approve it too.
                                    if ($log) { error_log("Already approved - do so on Yahoo"); }
                                    $this->msg->approve($group['groupid'], NULL, NULL, NULL, TRUE);
                                    $handled = TRUE;
                                    $ret = MailRouter::APPROVED;
                                }
                            }
                        } else {
                            # This is a notification of a message on Yahoo pending.  It's possible that the message
                            # has been synchronised via the plugin and already approved before we receive this -
                            # Yahoo is slow.  In that case we just want to ignore this notification.  Otherwise
                            # this goes into pending if it's not spam.
                            if ($log) { error_log("From Yahoo pending"); }

                            foreach ($groups as $group) {
                                if ($this->log) { error_log("{$group['groupid']} collection {$group['collection']}");}

                                if ($group['collection'] == MessageCollection::APPROVED) {
                                    # We've approved it on here.
                                    if ($log) { error_log("Already approved"); }
                                    $handled = TRUE;
                                    $ret = MailRouter::APPROVED;
                                }
                            }
                        }

                        if (!$handled) {
                            # It's not already been approved to it should go into pending on here to match where
                            # it is on Yahoo.
                            if ($log) {
                                error_log("Mark as pending");
                            }

                            if ($this->markPending($notspam, TRUE)) {
                                $ret = MailRouter::PENDING;
                            }
                        }
                    } else if ($this->msg->getSource() == Message::YAHOO_APPROVED) {
                        if ($log) { error_log("Mark as approved"); }
                        if ($this->markApproved()) {
                            $ret = MailRouter::APPROVED;
                        }
                    } else if ($this->msg->getSource() == Message::EMAIL) {
                        $uid = $this->msg->getFromuser();
                        if ($log) { error_log("Email source, user $uid"); }

                        if ($uid) {
                            $u = User::get($this->dbhr, $this->dbhm, $uid);

                            # Drop unless the email comes from a group member.
                            $ret = MailRouter::DROPPED;

                            foreach ($groups as $group) {
                                $appmemb = $u->isApprovedMember($group['groupid']);
                                if ($log) { error_log("Approved member? $appmemb"); }
                                if ($appmemb) {
                                    # Whether we post to pending or approved depends on the group setting,
                                    # and if that is set not to moderate, the user setting.  Similar code for
                                    # this setting in message API call.
                                    $g = Group::get($this->dbhr, $this->dbhm, $group['groupid']);
                                    $ps = $g->getSetting('moderated', 0) ? Group::POSTING_MODERATED : $u->getMembershipAtt($group['groupid'], 'ourPostingStatus') ;
                                    $ps = $ps ? $ps : Group::POSTING_MODERATED;
                                    if ($log) { error_log("Member, Our PS is $ps"); }

                                    if ($ps == Group::POSTING_MODERATED) {
                                        if ($log) { error_log("Mark as pending"); }
                                        if ($this->markPending($notspam, FALSE)) {
                                            $ret = MailRouter::PENDING;
                                        }
                                    } else {
                                        if ($log) { error_log("Mark as approved"); }
                                        if ($this->markApproved()) {
                                            $ret = MailRouter::APPROVED;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    # Check for getting group mails to our individual users, which we want to turn off because
                    # otherwise we'd get swamped.  We get group mails via the modtools@ and republisher@ users.
                    if (strpos($envto, '@' . USER_DOMAIN) !== FALSE || (ourDomain($envto) && stripos($envto, 'fbuser') === 0)) {
                        foreach ($groups as $group) {
                            $g = Group::get($this->dbhr, $this->dbhm, $group['groupid']);
                            if ($log) { error_log("Turn off mails for $envto via " . $g->getGroupNoEmail()); }
                            $this->mail($g->getGroupNoEmail(), $envto, "Turning off mails", "I don't want these");
                        }
                    }
                } else {
                    # It's not to one of our groups - but it could be a reply to one of our users, in several ways:
                    # - to the reply address we put in our What's New mails
                    # - directly to their USER_DOMAIN address, which happens after their message has been posted
                    #   on a Yahoo group and we get a reply through that route
                    # - in response to an email chat notification, which happens as a result of subsequent
                    #   communications after the previous two
                    $u = User::get($this->dbhr, $this->dbhm);
                    $to = $this->msg->getEnvelopeto();
                    $to = $to ? $to : $this->msg->getHeader('to');
                    if ($log) { error_log("Look for reply $to"); }
                    $uid = NULL;
                    $ret = MailRouter::DROPPED;

                    if (preg_match('/replyto-(.*)-(.*)' . USER_DOMAIN . '/', $to, $matches)) {
                        if (!$this->msg->isBounce() && !$this->msg->isAutoreply()) {
                            $msgid = intval($matches[1]);
                            $fromid = intval($matches[2]);

                            $m = new Message($this->dbhr, $this->dbhm, $msgid);
                            $u = User::get($this->dbhr, $this->dbhm, $fromid);

                            if ($m->getID() && $u->getId() && $m->getFromuser()) {
                                # The email address that we replied from might not currently be attached to the
                                # other user, for example if someone has email forwarding set up.  So make sure we
                                # have it.
                                $u->addEmail($this->msg->getEnvelopefrom(), 0, FALSE);

                                $fromu = User::get($this->dbhr, $this->dbhm, $m->getFromuser());

                                # The sender of this reply will always be on our platform, because otherwise we
                                # wouldn't have generated a What's New mail to them.  So we want to set up a chat
                                # between them and the sender of the message (who might or might not be on our
                                # platform).
                                $r = new ChatRoom($this->dbhr, $this->dbhm);
                                $chatid = $r->createConversation($fromid, $m->getFromuser());

                                # Now add this into the conversation as a message.  This will notify them.
                                $textbody = $this->msg->stripQuoted();

                                $cm = new ChatMessage($this->dbhr, $this->dbhm);
                                $mid = $cm->create($chatid, $fromid, $textbody, ChatMessage::TYPE_INTERESTED, $msgid, FALSE);

                                $cm->chatByEmail($mid, $this->msg->getID());

                                # The user sending this is up to date with this conversation.  This prevents us
                                # notifying her about other messages.
                                $r->mailedLastForUser($fromid);

                                $promisedto = $m->promisedTo();

                                if ($m->hasOutcome() || ($promisedto && $promisedto != $this->msg->getFromuser())) {
                                    # We don't want to email the recipient either - no point pestering them with more
                                    # emails for items which are completed or promised.  They can see them on the
                                    # site if they want.
                                    $r->mailedLastForUser($m->getFromuser());
                                }

                                $ret = MailRouter::TO_USER;
                            }
                        }
                    } else if (preg_match('/notify-(.*)-(.*)' . USER_DOMAIN . '/', $to, $matches)) {
                        # It's a reply to an email notification.
                        if (!$this->msg->isBounce()) {
                            $chatid = intval($matches[1]);
                            $userid = intval($matches[2]);
                            $r = new ChatRoom($this->dbhr, $this->dbhm, $chatid);
                            $u = User::get($this->dbhr, $this->dbhm, $userid);

                            if ($r->getId()) {
                                # It's a valid chat.
                                if ($r->getPrivate('user1') == $userid || $r->getPrivate('user2') == $userid || $u->isModerator()) {
                                    # ...and the user we're replying to is part of it or a mod.
                                    #
                                    # The email address that we replied from might not currently be attached to the
                                    # other user, for example if someone has email forwarding set up.  So make sure we
                                    # have it.
                                    $u->addEmail($this->msg->getEnvelopefrom(), 0, FALSE);

                                    # Now add this into the conversation as a message.  This will notify them.
                                    $textbody = $this->msg->stripQuoted();

                                    $cm = new ChatMessage($this->dbhr, $this->dbhm);
                                    $mid = $cm->create($chatid, $userid, $textbody, ChatMessage::TYPE_DEFAULT, $this->msg->getID(), FALSE);

                                    $cm->chatByEmail($mid, $this->msg->getID());

                                    # The user sending this is up to date with this conversation.  This prevents us
                                    # notifying her about other messages
                                    $r->mailedLastForUser($userid);

                                    # It might be nice to suppress email notifications if the message has already
                                    # been promised or is complete, but we don't really know which message this
                                    # reply is for.

                                    $ret = MailRouter::TO_USER;
                                }
                            }
                        }
                    } else if (preg_match('/notify@yahoogroups.co.*/', $from)) {
                        # This is a Yahoo message which shouldn't get passed on to a non-Yahoo user.
                        if ($log) { error_log("Yahoo Notify - drop"); }
                        $ret = MailRouter::DROPPED;
                    } else if (!$this->msg->isAutoreply()) {
                        # See if it's a direct reply.  Auto-replies (that we can identify) we just drop.
                        $uid = $u->findByEmail($to);
                        if ($log) { error_log("Find reply $to = $uid"); }

                        if ($uid && $this->msg->getFromuser() && strtolower($to) != strtolower(MODERATOR_EMAIL)) {
                            # This is to one of our users.  We try to pair it as best we can with one of the posts.
                            #
                            # We don't want to process replies to ModTools user.  This can happen if MT is a member
                            # rather than a mod on a group.
                            $original = $this->msg->findFromReply($uid);
                            if ($log) { error_log("Paired with $original"); }

                            $ret = MailRouter::TO_USER;

                            $textbody = $this->msg->stripQuoted();

                            # If we found a message to pair it with, then we will pass that as a referenced
                            # message.  If not then add in the subject line as that might shed some light on it.
                            $textbody = $original ? $textbody : ($this->msg->getSubject() . "\r\n\r\n$textbody");

                            # Get/create the chat room between the two users.
                            if ($log) { error_log("Create chat between " . $this->msg->getFromuser() . " (" . $this->msg->getFromaddr() . ") and " . $uid); }
                            $r = new ChatRoom($this->dbhr, $this->dbhm);
                            $rid = $r->createConversation($this->msg->getFromuser(), $uid);
                            if ($log) { error_log("Got chat id $rid"); }

                            if ($rid) {
                                # Add in a spam score for the message.
                                if (!$spamscore) {
                                    $this->spamc->command = 'CHECK';
                                    if ($this->spamc->filter($this->msg->getMessage())) {
                                        $spamscore = $this->spamc->result['SCORE'];
                                        if ($log) { error_log("Spam score $spamscore"); }
                                    }
                                }

                                # And now add our text into the chat room as a message.  This will notify them.
                                $m = new ChatMessage($this->dbhr, $this->dbhm);
                                $mid = $m->create($rid,
                                    $this->msg->getFromuser(),
                                    $textbody,
                                    $this->msg->getModmail() ? ChatMessage::TYPE_MODMAIL : ChatMessage::TYPE_INTERESTED,
                                    $original,
                                    FALSE,
                                    $spamscore);
                                if ($log) { error_log("Created chat message $mid"); }

                                $m->chatByEmail($mid, $this->msg->getID());

                                # The user sending this is up to date with this conversation.  This prevents us
                                # notifying her about other messages
                                $r->mailedLastForUser($this->msg->getFromuser());

                                if ($original) {
                                    $m = new Message($this->dbhr, $this->dbhm, $original);
                                    $promisedto = $m->promisedTo();

                                    if ($m->hasOutcome() || ($promisedto && $promisedto != $this->msg->getFromuser())) {
                                        # We don't want to email the recipient either - no point pestering them with more
                                        # emails for items which are completed or promised.  They can see them on the
                                        # site if they want.
                                        $r->mailedLastForUser($m->getFromuser());
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($ret != MailRouter::FAILURE) {
            # Ensure no message is stuck in incoming.
            $this->dbhm->preExec("DELETE FROM messages_groups WHERE msgid = ? AND collection = ?;", [
                $this->msg->getID(),
                MessageCollection::INCOMING
            ]);
        }

        # Dropped messages will get tidied up by cron; we leave them around in case we need to
        # look at them for PD.
        error_log("Routed #" . $this->msg->getID(). " " . $this->msg->getMessageID() . " " . $this->msg->getEnvelopefrom() . " -> " . $this->msg->getEnvelopeto() . " " . $this->msg->getSubject() . " " . $ret);

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

    public function mail($to, $from, $subject, $body) {
        list ($transport, $mailer) = getMailer();

        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body);
        $mailer->send($message);
    }
}