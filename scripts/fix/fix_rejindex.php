<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$msgs = $dbhr->preQuery("select messages_groups.msgid from messages_index inner join messages_groups on messages_groups.msgid = messages_index.msgid where messages_groups.collection = 'Rejected';");

foreach ($msgs as $msg) {
    $dbhm->preExec("DELETE FROM messages_index WHERE msgid = ?;", [ $msg['msgid'] ]);
}