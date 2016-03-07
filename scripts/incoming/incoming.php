<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/mail/MailRouter.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

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

if (preg_match('/MODERATE -- (.*) posted to (.*)/', $msg, $matches)) {
    # This is a moderation notification for a pending message.
    error_log("MODERATE");
    $r->received(Message::YAHOO_PENDING, NULL, $envto, $msg);
    $rc = $r->route();
} else if (stripos($envfrom, "@returns.groups.yahoo.com") !== FALSE && (stripos($envfrom, "sentto-") !== FALSE)) {
    # This is a message sent out to us as a user on the group, so it's an approved message.
    error_log("Approved message");
    $r->received(Message::YAHOO_APPROVED, NULL, $envto, $msg);
    $rc = $r->route();
} else if (stripos($envfrom, "@returns.groups.yahoo.com") !== FALSE) {
    # This is a system message.
    error_log("From Yahoo System");
    $id = $r->received(Message::YAHOO_SYSTEM, $envfrom, $envto, $msg);
    $rc = $r->route();
}

fwrite($logh, "Route returned $rc");