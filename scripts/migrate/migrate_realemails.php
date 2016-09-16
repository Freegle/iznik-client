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

$u = User::get($dbhr, $dbhm);

function handle($dbhr, $dbhm, $u, $realmail, $user) {
    # We have found the real email corresponding to this membership.  It might be that this real email is already
    # attached to an existing other user.
    $id = $u->findByEmail($realmail);

    if (!$id || $id == $user['id']) {
        # But it doesn't.
        error_log("Add $realmail} to {$user['id']} {$user['email']}");
        $u = User::get($dbhr, $dbhm, $user['id']);
        $rc = $u->addEmail($realmail, 0, FALSE);

        if ($rc) {
            $u->setPrivate('gotrealemail', 1);
        }
    } else {
        # It does, so we have to do some merging.  Then make sure the useremail is the prerred
        error_log("Merge of {$user['id']} {$user['email']} and $id $realmail required");
        $u = User::get($dbhr, $dbhm, $user['id']);
        $rc = $u->merge($user['id'], $id, "RealEmails - $realmail = $id, {$user['email']} = {$user['id']}");

        if ($rc) {
            $dbhm->preQuery("UPDATE users_emails SET preferred = 0 WHERE userid = ?;", [$user['id']]);
            $dbhm->preQuery("UPDATE users_emails SET preferred = 1 WHERE email = ?;", [$realmail]);
            $u->setPrivate('gotrealemail', 1);
        }
    }
}

$block = 0;

$lock = "/tmp/iznik_migraterealemails.lock";
$lockh = fopen($lock, 'a');

if (flock($lockh, LOCK_EX | LOCK_NB, $block)) {
    $users = $dbhr->preQuery("SELECT DISTINCT users.id, users_emails.email FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE gotrealemail = 0 AND email LIKE 'FBUser%';");
    foreach ($users as $user) {
        $sql = "SELECT useremail FROM users WHERE groupsemail = " . $dbhold->quote($user['email']) . ";";
        $fdusers = $dbhold->query($sql);
        foreach ($fdusers as $fduser) {
            try {
                handle($dbhr, $dbhm, $u, $fduser['useremail'], $user);
            } catch (Exception $e) {
                error_log("Skip {$fduser['useremail']} " . $e->getMessage());
            }
        }
    }

    //$users = $dbhr->preQuery("SELECT DISTINCT users.id, users_emails.email FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE gotrealemail = 0 AND email LIKE '%trashnothing.com';");
    //foreach ($users as $user) {
    //    $url = "https://trashnothing.com/modtools/api/subscriptions?key=" . TNKEY . "&email=" . urlencode($user['email']);
    //    error_log($url);
    //    $rsp = file_get_contents($url);
    //    error_log(var_export($rsp, true));
    //    exit(0);
    //    #handle($dbhr, $dbhm, $u, $realmail, $user);
    //}
}

flock($lockh, LOCK_UN);
fclose($lockh);
