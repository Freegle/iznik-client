<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$messages = $dbhr->preQuery("SELECT subject FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid INNER JOIN groups ON messages_groups.groupid = groups.id AND groups.type IN ('Freegle', 'Reuse');");
$count = 0;

foreach ($messages as $message) {
    if (preg_match('/.*(OFFER|WANTED|TAKEN|RECEIVED) *[\\:-](.*)\\(.*\\)/', $message['subject'], $matches)) {
        error_log("Item {$matches[2]}");
        $item = trim($matches[2]);

        $dbhm->preExec("INSERT INTO items (name) VALUES (?) ON DUPLICATE KEY UPDATE popularity = popularity + 1;", [ $item ]);
    }

    $count++;

    if ($count % 100 == 0) {
        error_log("...$count");
    }
}
