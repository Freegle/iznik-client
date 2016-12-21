<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$outcomes = file_get_contents('/tmp/a.a');
$lines = explode("\n", $outcomes);

foreach ($lines as $line) {
    if (preg_match('/\/mypost\/(.*)\/(.*)\?/', $line, $matches)) {
        $id = $matches[1];
        $outcome = $matches[2];

        $alreadys = $dbhr->preQuery("SELECT * FROM messages_outcomes WHERE msgid = ?;", [ $id ]);
        if (count($alreadys) == 0) {
            error_log("$id = $outcome, no outcomes");
            if ($outcome == 'withdraw') {
                $outcome = 'Withdrawn';
            } else if ($outcome == 'repost') {
                $outcome = 'Repost';
            } else if ($outcome == 'completed') {
                $m = new Message($dbhr, $dbhm, $id);
                $outcome = $m->getType() == 'Offer' ? 'Taken' : 'Received';
            }

            $dbhm->preExec("INSERT IGNORE INTO messages_outcomes_intended (msgid, outcome) VALUES (?, ?);", [
                $id,
                $outcome
            ]);
        } else {
            #error_log("$id = $outcome, already got outcome {$alreadys[0]['outcome']}");
        }
    }
}