<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

require_once(IZNIK_BASE . '/lib/wordle/functions.php');

$users = $dbhr->preQuery("SELECT id, yahooid FROM users WHERE LENGTH( yahooid ) = 32 AND fullname = yahooid;");

$lengths  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/distinct_word_lengths.json'), true);
$bigrams  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/word_start_bigrams.json'), true);
$trigrams = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/trigrams.json'), true);

foreach ($users as $user) {
    if (ctype_xdigit($user['yahooid'])) {
        $u = new User($dbhr, $dbhm, $user['id']);
        $length = \Wordle\array_weighted_rand($lengths);
        $start  = \Wordle\array_weighted_rand($bigrams);
        $name = strtolower(\Wordle\fill_word($start, $length, $trigrams));
        $u->setPrivate('fullname', $name);
    }
}