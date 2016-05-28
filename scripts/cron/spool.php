<?php
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Alerts.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$spool = new Swift_FileSpool(IZNIK_BASE . "/spool");

# Some messages can fail to send, if exim is playing up.
$spool->recover(60);

$transport = Swift_SpoolTransport::newInstance($spool);
$realTransport = Swift_SmtpTransport::newInstance();

$spool = $transport->getSpool();
$sent = $spool->flushQueue($realTransport);

echo "Sent $sent emails\n";