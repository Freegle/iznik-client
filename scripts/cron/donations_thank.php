<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$lockh = lockScript(basename(__FILE__));

$users = $dbhr->preQuery("SELECT DISTINCT userid FROM users_donations WHERE thanked = 0 AND userid IS NOT NULL;");
foreach ($users as $user) {
    $u = User::get($dbhr, $dbhm, $user['userid']);
    $u->thankDonation();
    $dbhm->preExec("UPDATE users_donations SET thanked = 1 WHERE userid = ?;", [ $user['userid'] ]);
}

unlockScript($lockh);