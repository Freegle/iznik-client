<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

foreach (['yahooapprovedid', 'yahoopendingid'] as $attr) {
    $sql = "select * FROM messages_groups where $attr is not null and $attr != 0 group by groupid, $attr having count(*) > 1;";

    do {
        $count = 0;
        $msgs = $dbhr->preQuery($sql);
        foreach ($msgs as $msg) {
            error_log("{$msg['msgid']}, {$msg['groupid']}, {$msg[$attr]} of " . count($msgs));
            $msgs2 = $dbhr->preQuery("SELECT * from messages_groups WHERE groupid = ? AND $attr = ?;", [ $msg['groupid'], $msg[$attr]]);
            foreach ($msgs2 as $msg2) {
                error_log("Delete {$msg2['msgid']} for {$msg['groupid']}, {$msg[$attr]}");
                $dbhm->preExec("DELETE FROM messages WHERE id = ?;", [ $msg2['msgid'] ]);
                $count++;
            }
        }

    } while ($count > 0);

    $dbhm->preExec("DELETE FROM messages_groups WHERE $attr = 0");    
}
