<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$msgs = $dbhr->preQuery("SELECT id FROM messages WHERE subject like '[%[%';");

$total = count($msgs);
$count = 0;

foreach ($msgs as $msg) {
    $m = new Message($dbhr, $dbhm, $msg['id']);
    $m->recordRelated();
    $count++;

    if ($count % 1000 == 0) {
        error_log("...$count / $total");
    }
}
