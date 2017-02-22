<?php
#
# Purge logs. We do this in a script rather than an event because we want to chunk it, otherwise we can hang the
# cluster with an op that's too big.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');

# Bypass our usual DB class as we don't want the overhead nor to log.
$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$dbhm = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

# Purge messages which have been stuck waiting for Yahoo users for ages.
$start = date('Y-m-d', strtotime("midnight 31 days ago"));
error_log("Purge waiting for Yahoo before $start");

$total = 0;
do {
    $sql = "SELECT msgid FROM messages_groups WHERE collection = '" . MessageCollection::QUEUED_YAHOO_USER . "' AND arrival < '$start' LIMIT 1000;";
    $msgs = $dbhm->query($sql)->fetchAll();
    foreach ($msgs as $msg) {
        $dbhm->exec("DELETE FROM messages WHERE id = {$msg['msgid']};");
        $total++;

        if ($total % 1000 == 0) {
            error_log("...$total");
        }
    }
} while (count($msgs) > 0);

error_log("Deleted $total");

# Purge old drafts.
$start = date('Y-m-d', strtotime("midnight 31 days ago"));
error_log("Purge old drafts before $start");

$total = 0;
do {
    $sql = "SELECT msgid FROM messages_drafts WHERE timestamp < '$start' LIMIT 1000;";
    $msgs = $dbhm->query($sql)->fetchAll();
    foreach ($msgs as $msg) {
        $dbhm->exec("DELETE FROM messages WHERE id = {$msg['msgid']};");
        $total++;

        if ($total % 1000 == 0) {
            error_log("...$total");
        }
    }
} while (count($msgs) > 0);

error_log("Deleted $total");

# Purge old non-Freegle messages
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

# Now purge messages which have been deleted - we keep them for a while for PD purposes.
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

# Now purge messages which are stranded, not on any groups and not referenced from any chats or drafts.
$start = date('Y-m-d', strtotime("midnight 2 days ago"));
error_log("Purge stranded messages before $start");
$total = 0;

do {
    $sql = "SELECT messages.id FROM messages WHERE arrival <= '$start' AND id NOT IN (SELECT DISTINCT msgid FROM messages_groups) AND id NOT IN (SELECT DISTINCT refmsgid FROM chat_messages) AND id NOT IN (SELECT DISTINCT msgid FROM messages_drafts) LIMIT 1000;";
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

# We don't need the HTML content or full message for old messages - we're primarily interested in the text body, and
# these are large attributes.
$start = date('Y-m-d', strtotime("midnight 2 days ago"));
error_log("Purge HTML body for messages before $start");
$total = 0;
$id = NULL;

do {
    $idq = $id ? " id < $id AND " : "";
    $sql = "SELECT id FROM messages WHERE $idq arrival <= '$start' AND htmlbody IS NOT NULL ORDER BY id DESC LIMIT 1;";
    error_log($sql);
    $msgs = $dbhr->preQuery($sql);
    foreach ($msgs as $msg) {
        $id = !$id ? $msg['id'] : min($id, $msg['id']);
        $sql = "UPDATE messages SET htmlbody = NULL WHERE id <= {$msg['id']} AND htmlbody IS NOT NULL LIMIT 1000;";
        $count = $dbhm->exec($sql);
        $total += $count;
        error_log("...$total");
    }
} while ($count > 0);

error_log("Purge message for messages before $start");
$start = date('Y-m-d', strtotime("midnight 30 days ago"));
$total = 0;
$id = NULL;

do {
    $idq = $id ? " id < $id AND " : "";
    $sql = "SELECT id FROM messages WHERE $idq arrival <= '$start' AND message IS NOT NULL ORDER BY id DESC LIMIT 1;";
    error_log($sql);
    $msgs = $dbhr->preQuery($sql);
    foreach ($msgs as $msg) {
        $id = !$id ? $msg['id'] : min($id, $msg['id']);
        $sql = "UPDATE messages SET message = NULL WHERE id <= {$msg['id']} AND message IS NOT NULL LIMIT 1000;";
        $count = $dbhm->exec($sql);
        $total += $count;
        error_log("...$total");
    }
} while ($count > 0);

