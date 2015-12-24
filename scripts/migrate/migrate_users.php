<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$u = new User($dbhr, $dbhm);
$g = new Group($dbhr, $dbhm);

$oldusers = $dbhold->query("SELECT * FROM users WHERE deletedfromyahoo = 0 LIMIT 10;");
foreach ($oldusers as $user) {
    # See if we know this user.
    $id1 = $u->findByEmail($user['useremail']);
    $id2 = $user['groupsemail'] != $user['useremail'] ? $u->findByEmail($user['groupsemail']) : NULL;
    error_log("{$user['useremail']} = $id1 / {$user['groupsemail']} = $id2");

    if (!$id1 && !$id2) {
        error_log("Unknown, skip");
    } else if ($id1 && $id2) {
        # We already have two separate entries for this user.  We will need to merge.
        error_log("Merge required");
        exit(1);
    } else if ($id1 && !$id2) {
        # Add the groupsemail as an email address for this user, and ensure the useremail is a primary.
        $u = new User($dbhr, $dbhm, $id1);
        error_log("Add {$user['groupsemail']} to {$user['useremail']}");
        $u->removeEmail($user['useremail']);
        $u->addEmail($user['useremail'], 1);
        $u->addEmail($user['groupsemail'], 0);
    } else if (!$id1 && $id2) {
        # Add the useremail as the primary email address for this user, and ensure the groupsemail is not primary.
        $u = new User($dbhr, $dbhm, $id2);
        error_log("Add {$user['useremail']} to {$user['groupsemail']}");
        $u->removeEmail($user['groupsemail']);
        $u->addEmail($user['useremail'], 1);
        $u->addEmail($user['groupsemail'], 0);
    }
}

