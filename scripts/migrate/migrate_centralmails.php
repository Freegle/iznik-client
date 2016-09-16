<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";
$dbh = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$u = User::get($dbhm, $dbhm);

$users = $dbh->query("SELECT useremail FROM users WHERE centralmailsdisabled = 1;");
$at = 0;
foreach ($users as $user) {
    $eid = $u->findByEmail($user['useremail']);

    if ($eid) {
        $u = User::get($dbhm, $dbhm, $eid);
        $u->setPrivate('newslettersallowed', 0);
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}
