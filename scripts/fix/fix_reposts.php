<?php

define(SQLLOG, FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Search.php');

$sql = "SELECT * FROM messages_groups WHERE messages_groups.deleted = 0;";
$msgs = $dbhr->preQuery($sql);

$start = strtotime("90 days ago");
$s = new Search($dbhr, $dbhm, 'messages_index', 'msgid', 'arrival', 'words', 'groupid', $start);
$count = 0;
$total = count($msgs);

foreach ($msgs as $msg) {
    $s->bump($msg['msgid'], strtotime($msg['arrival']));

    $count++;

    if ($count % 1000 == 0) {
        error_log("...$count / $total");
    }
}
