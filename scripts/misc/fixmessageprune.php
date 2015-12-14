<?php
# Prune message content

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";

$sql = "SELECT id, LENGTH(message) AS len FROM messages WHERE LENGTH(message) > 100000;";
$msgs = $dbhr->query($sql);

$count = 0;

foreach ($msgs as $msg) {
    $m = new Message($dbhr, $dbhm, $msg['id']);
    $pruned = $m->pruneMessage();
    $prunelen = strlen($pruned);

    if ($prunelen < $msg['len']) {
        error_log("#{$msg['id']} len {$msg['len']} => " . strlen($pruned));
        $dbhm->preExec("UPDATE messages SET message = ? WHERE id = ?;", [ $pruned, $msg['id'] ]);
    }

    $count++;
    if ($count % 1000 == 0) {
        echo "...$count\n";
    }
}
