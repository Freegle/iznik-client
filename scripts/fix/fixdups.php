<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

    $sql = "select messageid from messages group by messageid having count(*) > 1;";
    $msgs = $dbhr->preQuery($sql);
$count =0;
    foreach ($msgs as $msg) {
        $dbhm->preExec("DELETE FROM messages WHERE messageid = ?;", [ $msg['messageid'] ]);
        $count++;
        if ($count % 1000 == 0) {
            error_log($count);
        }
    }

