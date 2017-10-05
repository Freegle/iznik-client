<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$at = 0;

$logs = $dbhr->preQuery("SELECT id, msgid FROM logs WHERE type = 'Message' AND subtype = 'Outcome' AND groupid IS NULL");
$total = count($logs);

foreach ($logs as $log) {
    $groups = $dbhr->preQuery("SELECT groupid FROM messages_groups WHERE msgid = ?;", [ $log['msgid'] ]);
    foreach ($groups as $group) {
        $dbhm->preExec("UPDATE logs SET groupid = ? WHERE id = ?;", [ $group['groupid'], $log['id'] ], FALSE);
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at / $total");
    }
}