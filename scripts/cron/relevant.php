<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "ilovefreegle.org";

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/mail/Relevant.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$rl = new Relevant($dbhr, $dbhm);
$count = 0;

$users = $dbhr->preQuery("SELECT id FROM users WHERE lastlocation IS NOT NULL AND relevantallowed = 1;");
foreach ($users as $user) {
    $count += $rl->sendMessages($user['id']);
    $rl->recordCheck($user['id']);
}

error_log("Sent $count");

unlockScript($lockh);