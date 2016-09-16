<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";
$dbh = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

# We can't make up something similar to their existing email address.
$lengths  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/distinct_word_lengths.json'), true);
$bigrams  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/word_start_bigrams.json'), true);
$trigrams = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/trigrams.json'), true);

$users = $dbh->query("SELECT * FROM facebook WHERE hidename = 1;");
$u = User::get($dbhr, $dbhm);
$at = 0;

foreach ($users as $user) {
    $uid = $u->findByEmail($user['email']);

    if ($uid) {
        $u = User::get($dbhr, $dbhm, $uid);

        if (!$u->isModerator()) {
            $length = \Wordle\array_weighted_rand($lengths);
            $start  = \Wordle\array_weighted_rand($bigrams);
            $name = strtolower(\Wordle\fill_word($start, $length, $trigrams));
            error_log("$uid " . $u->getName() . " => $name");
            $u->setPrivate('fullname', $name);
            $u->setPrivate('firstname', NULL);
            $u->setPrivate('lastname', NULL);
        }
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}

