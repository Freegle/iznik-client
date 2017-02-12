<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$users = $dbhr->preQuery("SELECT id FROM users");;
foreach ($users as $user) {
    $oldest = $dbhr->preQuery("SELECT MIN(added) AS minadded FROM memberships WHERE userid = ?;", [ $user['id'] ]);
    if (count($oldest) > 0 && $oldest[0]['minadded']) {
        $dbhr->preExec("UPDATE users SET added = ? WHERE id = ?;", [
            $oldest[0]['minadded'],
            $user['id']
        ], FALSE);
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}