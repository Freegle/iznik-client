<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/mail/MailRouter.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

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

$r = new MailRouter($dbhr, $dbhm);

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
        $g = new Group($dbhr, $dbhm);
        $gid = $g->findByShortName($groupname);

        if ($gid) {
            $g = new Group($dbhr, $dbhm, $gid);

            list ($transport, $mailer) = getMailer();

            $message = Swift_Message::newInstance()
                ->setSubject("Turning off mails")
                ->setFrom($envto)
                ->setTo($g->getGroupNoEmail())
                ->setBody("I don't want these");
            $mailer->send($message);

        }

        error_log("Turn off incoming mails for $envto on $groupname => #$gid " . $g->getPrivate('nameshort'));

        $cont = FALSE;
    }
}

if ($cont) {
    if (preg_match('/MODERATE -- (.*) posted to (.*)/', $msg, $matches)) {
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
        (($envto == MODERATOR_EMAIL || stripos($envto, USER_DOMAIN) !== FALSE) && stripos($msg, 'Reply-To: confirm-invite') !== FALSE) ||
        stripos($envto, "modconfirm-") !== FALSE) {
        # This is a system message.
        error_log("From Yahoo System");
        $id = $r->received(Message::YAHOO_SYSTEM, $envfrom, $envto, $msg);
        $rc = $r->route();
    } else {
        # Probably a reply to a member.
        error_log("Email");
        $id = $r->received(Message::EMAIL, $envfrom, $envto, $msg);
        $rc = $r->route();
    }
}


error_log("CPU cost " . getCpuUsage());
fwrite($logh, "Route returned $rc");