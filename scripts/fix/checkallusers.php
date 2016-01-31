<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$s = new Spam($dbhr, $dbhm);

$sql = "SELECT id FROM users;";
$users = $dbhr->query($sql);

foreach ($users as $user) {
    $s->checkUser($user['id']);

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}
