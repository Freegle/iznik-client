<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsnfd = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = new Group($dbhr, $dbhm);
$u = User::get($dbhr, $dbhm);
$count = 0;

$fdusers = $dbhfd->query("SELECT * FROM facebook;");

foreach ($fdusers as $fduser) {
    $uid = $u->findByEmail($fduser['email']);
    
    if ($uid) {
        if ($fduser['password'] != '') {
            # Delete old one in case they've changed their password.
            $dbhm->preExec("DELETE FROM users_logins WHERE userid = ? AND type = 'Native';", [ $uid ]);
            $dbhm->preExec("INSERT INTO users_logins (`userid`, `type`, `credentials`) VALUES (?, ?, ?);", [
                $uid,
                'Native',
                $u->hashPassword($fduser['password'])
            ]);
        }
    }
    
    $count++;
    
    if ($count % 1000 == 0) {
        error_log("...$count");
    }
}