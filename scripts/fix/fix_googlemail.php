<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$u = new User($dbhr, $dbhm);
$count = 0;

$users = $dbhr->preQuery("SELECT * FROM users_emails WHERE email LIKE '%@googlemail.com' OR email LIKE '%googlemail.co.uk';");

foreach ($users as $user) {
    $gmail = str_replace('@googlemail.com', '@gmail.com', $user['email']);
    $uid = $u->findByEmail($gmail);

    if ($uid && $uid != $user['userid']) {
        $count++;
        error_log("$count {$user['email']} is {$user['userid']} and $uid");

        $u = new User($dbhr, $dbhm, $user['userid']);
        $u->merge($uid, $user['userid'], "Googlemail is Gmail really");
    }
}

error_log("Found $count");