<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$outcomes = file_get_contents('/tmp/a.b');
$lines = explode("\n", $outcomes);

foreach ($lines as $line) {
    if (preg_match('/\/mypost\/(.*)\/(.*)\?/', $line, $matches)) {
        $id = $matches[1];
        $outcome = $matches[2];

        error_log("Check $id");
        $posteds = $dbhr->preQuery("SELECT * FROM messages_groups WHERE msgid = ? AND groupid = 21483;", [ $id ]);
        error_log(var_export($posteds, TRUE));

        if (count($posteds) > 0) {
            $arrival = $posteds[0]['arrival'];
            $age = time() - strtotime($arrival);
            $age = $age / 3600;

            error_log("$id age $age");
        }
    }
}