<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$u = new User($dbhr, $dbhm);
$count = 0;

$users = $dbhr->preQuery("SELECT * FROM users_emails WHERE email LIKE '%@googlemail.com' OR email LIKE '%googlemail.co.uk';");

foreach ($users as $user) {
    $canon = User::canonMail($user['email']);
    $dbhm->preExec("UPDATE users_emails SET canon = ? WHERE  id = ?;", [
        $canon,
        $user['id']
    ]);
}

error_log("Found $count");