<?php
#
# Purge logs. We do this in a script rather than an event because we want to chunk it, otherwise we can hang the
# cluster with an op that's too big.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

# Bypass our usual DB class as we don't want the overhead nor to log.
$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$dbhm = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

# Delete old non-Freegle messages
$start = date('Y-m-d', strtotime("midnight 31 days ago"));
error_log("Purge non-Freegle before $start");

$total = 0;
do {
    $sql = "SELECT messages.id FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid INNER JOIN groups ON messages_groups.groupid = groups.id WHERE `date` <= '$start' AND groups.type != 'Freegle' LIMIT 1000;";
    $msgs = $dbhm->query($sql)->fetchAll();
    foreach ($msgs as $msg) {
        $dbhm->exec("DELETE FROM messages WHERE id = {$msg['id']};");
        $total++;

        if ($total % 1000 == 0) {
            error_log("...$total");
        }
    }
} while (count($msgs) > 0);

error_log("Deleted $total");

# Now delete messages which have been deleted - we keep them for a while for PD purposes.
$start = date('Y-m-d', strtotime("midnight 2 days ago"));
error_log("Purge deleted messages before $start");
$total = 0;

do {
    $sql = "SELECT messages.id FROM messages WHERE deleted IS NOT NULL AND deleted <= '$start' LIMIT 1000;";
    $msgs = $dbhm->query($sql)->fetchAll();
    foreach ($msgs as $msg) {
        $dbhm->exec("DELETE FROM messages WHERE id = {$msg['id']};");
        $total++;

        if ($total % 1000 == 0) {
            error_log("...$total");
        }
    }
} while (count($msgs) > 0);

error_log("Deleted $total");
