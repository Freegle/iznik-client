<?php
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/mail/Bounce.php');
global $dbhr, $dbhm;

# Process incoming bounces into bounces_emails
$lockh = lockScript(basename(__FILE__));

$b = new Bounce($dbhr, $dbhm);
$b->process();

unlockScript($lockh);