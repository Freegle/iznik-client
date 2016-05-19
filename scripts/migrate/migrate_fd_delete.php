<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$msgs = $dbhr->preQuery("SELECT messageid, nameshort, message FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.deleted = 1 AND messages_groups.collection = 'Approved' INNER JOIN groups ON groups.id = messages_groups.groupid;");
error_log("Check " . count($msgs));

foreach ($msgs as $msg) {
    # Get message ID - it will have been cleared in the deleted message.
    if (preg_match('/^Message-ID\:(.*?)$/mi', $msg['message'], $matches)) {
        $msgid = $matches[1];
        $msgid = str_replace('<', '', $msgid);
        $msgid = str_replace('>', '', $msgid);
        $msgid = trim($msgid);
    }
    $sql = "SELECT uniquemsg, id, groupid, subject FROM messages WHERE messageid = " . $dbhold->quote("<$msgid>") . " OR messageid = " . $dbhold->quote($msgid) . ";";
    $msgs2 = $dbhold->query($sql);
    foreach ($msgs2 as $msg2) {
        error_log("#{$msg2['uniquemsg']} {$msg2['id']},{$msg2['groupid']} {$msg2['subject']}");
        $dbhold->exec("DELETE FROM messages WHERE uniquemsg = {$msg2['uniquemsg']}");
    }
}