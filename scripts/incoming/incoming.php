<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/mail/MailRouter.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$tusage = NULL;
$rusage = NULL;

# Get envelope sender and recipient
$envfrom = getenv('SENDER');
$envto = getenv('RECIPIENT');

# Get incoming mail
$msg = '';
while (!feof(STDIN)) {
    $msg .= fread(STDIN, 1024);
}

$log = "/tmp/iznik_incoming.log";
$logh = fopen($log, 'a');

fwrite($logh, "-----\nFrom $envfrom to $envto Message\n$msg\n-----\n");

# Use master to avoid replication delays where we create a message when receiving, but it's not replicated when
# we route it.
$r = new MailRouter($dbhm, $dbhm);

error_log("\n----------------------\n$envfrom => $envto");

$rc = MailRouter::DROPPED;

$cont = TRUE;
$groupname = NULL;

if (preg_match('/List-Unsubscribe: <mailto:(.*)-unsubscribe@yahoogroups.co/', $msg, $matches)) {
    $groupname = $matches[1];

    if ($groupname && strpos($envto, '@' . USER_DOMAIN) !== FALSE || (ourDomain($envto) && stripos($envto, 'fbuser') === 0)) {
        # Check for getting group mails to our individual users, which we want to turn off because
        # otherwise we'd get swamped.  We get group mails via the modtools@ and republisher@ users.
        #
        # We do this here rather than in the router because we don't want to create a message in the DB for it.
        $g = Group::get($dbhr, $dbhm);
        $gid = $g->findByShortName($groupname);

        if ($gid) {
            $g = Group::get($dbhr, $dbhm, $gid);

            list ($transport, $mailer) = getMailer();

            $message = Swift_Message::newInstance()
                ->setSubject("Turning off mails")
                ->setFrom($envto)
                ->setTo($g->getGroupNoEmail())
                ->setBody("I don't want these");
            $mailer->send($message);

        }

        error_log("Turn off incoming mails for $envto on $groupname => #$gid " . $g->getPrivate('nameshort'));

        if (ourDomain($envto)) {
            # If we got such a mail, it means that we were an approved member at the time it was sent.  If we have a
            # message queued for a Yahoo membership, then this is a chance to send it.  It might be that we aren't
            # finding out about when a membership is approved because the group doesn't send files we recognise or
            # use ModTools to do a sync.
            #
            # It is conceivable that someone joined, posted, and left, and that this will then add them again, or
            # bounce as not a member, but that's not the end of the world, and we can't expect perfect integration
            # by email with Yahoo.
            $u = new User($dbhr, $dbhm);
            $uid = $u->findByEmail($envto);
            $eid = $u->getIdForEmail($envto)['id'];
            error_log("$envto is #$uid");

            if ($uid) {
                $u = User::get($dbhr, $dbhm, $uid);

                $cont = TRUE;

                if (!$u->isPendingMember($gid) && !$u->isApprovedMember($gid)) {
                    # We've somehow lost the Yahoo membership.
                    if ($log) { error_log("Readd membership for $envto on $gid using $eid"); }
                    $cont = $u->addMembership($gid, User::ROLE_MEMBER, $eid, MembershipCollection::APPROVED);
                }

                if ($cont) {
                    # Submit queued messages which haven't already had an outcome.
                    $msgs = $dbhr->preQuery("SELECT msgid FROM messages_groups LEFT JOIN messages_outcomes ON messages_outcomes.msgid = messages_groups.msgid INNER JOIN messages ON messages.id = messages_groups.msgid WHERE fromuser = ? AND groupid = ? AND collection = ? AND messages_outcomes.msgid IS NULL;", [
                        $uid,
                        $gid,
                        MessageCollection::QUEUED_YAHOO_USER
                    ]);

                    foreach ($msgs as $msg) {
                        error_log("Submit queued message {$msg['msgid']} from $envto for $uid found as " . $u->getId());
                        $m = new Message($dbhr, $dbhm, $msg['msgid']);
                        $m->submit($u, $envto, $gid);
                    }
                }
            }
        }

        $cont = FALSE;
    }
}

if ($cont) {
    if (preg_match('/^Subject: MODERATE -- (.*) posted to (.*)/m', $msg, $matches)) {
        # This is a moderation notification for a pending message.
        error_log("MODERATE");
        $r->received(Message::YAHOO_PENDING, NULL, $envto, $msg);
        $rc = $r->route();
    } else if (stripos($envfrom, "@returns.groups.yahoo.com") !== FALSE && (stripos($envfrom, "sentto-") !== FALSE)) {
        # This is a message sent out to us as a user on the group, so it's an approved message.
        error_log("Approved message to $envto");
        $r->received(Message::YAHOO_APPROVED, NULL, $envto, $msg);
        $rc = $r->route();
    } else if (stripos($envfrom, "@returns.groups.yahoo.com") !== FALSE ||
        stripos($envfrom, "notify@yahoogroups.com") !== FALSE ||
        stripos($envfrom, "confirm-unsub") !== FALSE ||
        (($envto == MODERATOR_EMAIL || ourDomain($envto)) && stripos($msg, 'Reply-To: confirm-invite') !== FALSE) ||
        stripos($envto, "modconfirm-") !== FALSE) {
        # This is a system message.
        error_log("From Yahoo System");
        $id = $r->received(Message::YAHOO_SYSTEM, $envfrom, $envto, $msg);
        $rc = $r->route();
    } else {
        error_log("Email");
        $id = $r->received(Message::EMAIL, $envfrom, $envto, $msg);
        $rc = $r->route();
    }
}


error_log("CPU cost " . getCpuUsage() . " rc $rc");
fwrite($logh, "Route returned $rc\n");
exit(0);