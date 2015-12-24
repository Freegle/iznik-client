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

$users = $dbhr->preQuery("SELECT DISTINCT users.id, users_emails.email FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE gotrealemail = 0 AND email LIKE 'FBUser%';");
foreach ($users as $user) {
    $sql = "SELECT useremail FROM users WHERE groupsemail LIKE " . $dbhold->quote($user['email']) . ";";
    $fdusers = $dbhold->query($sql);
    foreach ($fdusers as $fduser) {
        # We have found the real email corresponding to this membership.  It might be that this real email is already
        # attached to an existing user.
        $id = $u->findByEmail($fduser['useremail']);

        if (!$id) {
            # But it doesn't.  Add it as the primary, and make sure the groups email is secondary.
            error_log("Add {$fduser['useremail']} to {$user['id']} {$user['email']}");
            $u = new User($dbhr, $dbhm, $user['id']);
            $u->addEmail($fduser['useremail'], 1);
            $u->removeEmail($user['email']);
            $u->addEmail($user['email'], 0);
            $u->setPrivate('gotrealemail', 1);
        } else {
            # It does, so we have to do some merging.  Then make sure the useremail is the prerred
            error_log("Merge of {$user['id']} {$user['email']} and $id {$fduser['useremail']} required");
            $u = new User($dbhr, $dbhm, $user['id']);
            $u->merge($user['id'], $id);
            $dbhm->preQuery("UPDATE users_emails SET preferred = 0 WHERE userid = ?;", [ $user['id'] ]);
            $dbhm->preQuery("UPDATE users_emails SET preferred = 1 WHERE email = ?;", [ $fduser['useremail'] ]);
            $u->setPrivate('gotrealemail', 1);
        }
    }
}

$users = $dbhr->preQuery("SELECT DISTINCT users.id, users_emails.email FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE gotrealemail = 0 AND (email LIKE 'FBUser%' OR email LIKE '%trashnothing.com');");
