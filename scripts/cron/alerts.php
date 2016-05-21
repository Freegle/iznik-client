<?php
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Alerts.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$a = new Alert($dbhr, $dbhm);
$a->process();

unlockScript($lockh);