<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsnfd = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$u = new User($dbhr, $dbhm);

# Fix users wrongly subscribed to Minehead from Strood/Medway.
$users = $dbhfd->query("SELECT * FROM users WHERE usergroup LIKE '%strood%' or usergroup LIKE '%medway%';");
foreach ($users as $user) {
    #error_log("Look for {$user['useremail']}");
    $uid = $u->findByEmail($user['useremail']);
    if ($uid) {
        #error_log("Found $uid");
        $u = new User($dbhr, $dbhm, $uid);
        if ($u->isMember(21531) && !$u->isModOrOwner(21531)) {
            list($eid, $membemail) = $u->getEmailForYahooGroup(21531);
            error_log("{$user['useremail']} removed with $membemail");
            $u->removeMembership(21531);
        }
    }
}
