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

$users = $dbhr->preQuery("SELECT id, fullname FROM users WHERE fullname LIKE 'FBUser%';");
$at = 0;
foreach ($users as $user) {
    $u = new User($dbhm, $dbhm, $user['id']);

    if (!$u->isModerator()) {
        $emails = $u->getEmails();

        foreach ($emails as $email) {
            $fbusers = $dbh->query("SELECT facebookname FROM facebook WHERE email LIKE " . $dbh->quote($email['email']) . " AND hidename = 0;");
            foreach ($fbusers as $fbuser) {
                error_log("#{$user['id']} {$user['fullname']} => {$fbuser['facebookname']}");
                $u->setPrivate('fullname', $fbuser['facebookname']);
                $u->setPrivate('firstname', NULL);
                $u->setPrivate('lastname', NULL);
            }
        }
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}

$lengths  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/distinct_word_lengths.json'), true);
$bigrams  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/word_start_bigrams.json'), true);
$trigrams = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/trigrams.json'), true);

$users = $dbhr->preQuery("SELECT id, fullname FROM users WHERE fullname LIKE 'A freegler';");
$at = 0;
foreach ($users as $user) {
    $u = new User($dbhm, $dbhm, $user['id']);

    if (!$u->isModerator()) {
            $length = \Wordle\array_weighted_rand($lengths);
            $start  = \Wordle\array_weighted_rand($bigrams);
            $name = strtolower(\Wordle\fill_word($start, $length, $trigrams));
            error_log("#{$user['id']} {$user['fullname']} => $name");
            $u->setPrivate('fullname', $name);
            $u->setPrivate('firstname', NULL);
            $u->setPrivate('lastname', NULL);
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}

exit(0);

$fbusers = $dbh->query("SELECT * FROM facebook;");
$at = 0;
$u = new User($dbhr, $dbhm);

foreach ($fbusers as $fbuser) {
    $uid = $u->findByEmail($fbuser['email']);
    if ($uid) {
        $u = new User($dbhm, $dbhm, $uid);

        if (!$u->isModerator()) {
            $gotpreferred = FALSE;

            if ($fbuser['lastemail'] != $fbuser['email'] && $fbuser['lastemail'] != '') {
                # No longer preferred
                error_log("$uid Set {$fbuser['lastemail']} not preferred");
                $dbhm->preExec("UPDATE users_emails SET preferred = 0 WHERE email LIKE ?;", [ $fbuser['lastemail'] ]);
            }
            $emails = $u->getEmails();
            foreach ($emails as $email) {
                if ($email['preferred']) {
                    $gotpreferred = TRUE;
                }
            }

            if (!$gotpreferred) {
                error_log("$uid Set {$fbuser['email']} preferred");
                $dbhm->preExec("UPDATE users_emails SET preferred = 1 WHERE email LIKE ?;", [ $fbuser['email'] ]);
            }
        }
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}
