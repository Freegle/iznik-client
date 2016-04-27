<?php

require_once dirname(__FILE__) . '/../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$sql = "SELECT subject, groupid FROM messages_approved WHERE groupid IN (SELECT id FROM groups WHERE type IN ('Reuse', 'Freegle')) AND message LIKE '%X-eGroups-Approved-By:%';";
$msgs = $dbhr->preQuery($sql);

foreach ($msgs as $msg) {
    if (preg_match('/.*\((.*)\)/', $msg['subject'],  $matches)) {
        $loc = $matches[1];
        error_log("$loc from {$msg['subject']}");
        $sql = "INSERT INTO locations_approved (location, groupid) VALUES (?,?) ON DUPLICATE KEY UPDATE popularity = popularity + 1;";
        $dbhm->preExec($sql, [
            $loc,
            $msg['groupid']
        ]);
    }
}