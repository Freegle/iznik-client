<?php
# Prune message content

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";

$sql = "select messageid from messages where source = 'Yahoo Approved';";
$msgs = $dbhr->query($sql);

$count = 0;
$total = 0;

foreach ($msgs as $msg) {
    $dbhm->preExec("DELETE FROM messages WHERE messageid = ? AND source = 'Yahoo Pending';", [ $msg['messageid'] ]);

    $count++;
    if ($count % 100 == 0) {
        echo "...$count ($total)\n";
    }
}
