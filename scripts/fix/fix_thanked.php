<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$thanks = $dbhr->preQuery("SELECT DISTINCT userid FROM users_donations WHERE thanked = 1 AND userid IS NOT NULL;");

foreach ($thanks as $thank) {
    $dbhm->preExec("INSERT INTO users_thanks (userid) VALUES (?);", [
        $thank['userid']
    ]);
}