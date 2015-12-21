<?php
# Prune message content

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";

$sql = "SELECT id, LENGTH(message) AS len FROM messages WHERE length(message) > 10000;";
#$sql = "SELECT id, LENGTH(message) AS len FROM messages WHERE id = 866660";
$msgs = $dbhr->query($sql);

$count = 0;
$total = 0;

foreach ($msgs as $msg) {
    $m = new Message($dbhr, $dbhm, $msg['id']);
    $pruned = $m->pruneMessage();
    $prunelen = strlen($pruned);

    if ($prunelen < $msg['len']) {
        #error_log("#{$msg['id']} len {$msg['len']} => " . strlen($pruned));
        $total += $msg['len'] - $prunelen;
        $dbhm->preExec("UPDATE messages SET message = ? WHERE id = ?;", [ $pruned, $msg['id'] ]);
    }

    $count++;
    if ($count % 1000 == 0) {
        echo "...$count ($total)\n";
    }
}
