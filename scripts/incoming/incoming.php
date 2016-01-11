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

fwrite($logh, "-----\n$msg\n-----\n");

error_log(var_export($_ENV, true));

$r = new MailRouter($dbhr, $dbhm);

if ((preg_match('/.*\-owner\@yahoogroups.com$/', $envfrom) !== FALSE) ||
    (preg_match('/confirm-s2-(.*)-(.*)=(.*)@yahoogroups.com/', $envfrom) !== FALSE)) {
    $id = $r->received(Message::YAHOO_SYSTEM, $envfrom, $envto, $msg);
    $rc = $r->route();
}

