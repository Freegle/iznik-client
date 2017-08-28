<?php
#
# Purge logs. We do this in a script rather than an event because we want to chunk it, otherwise we can hang the
# cluster with an op that's too big.
#
define(SQLLOG, FALSE);
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');

$start = date('Y-m-d', strtotime("midnight 31 days ago"));

error_log("Query");
$msgs = $dbhr->preQuery("SELECT * FROM messages_groups WHERE arrival >= ?;", [
    $start
]);
$count = count($msgs);
error_log("Got $count");

$at = 0;

foreach ($msgs as $msg) {
    $hists = $dbhr->preQuery("SELECT * FROM messages_history WHERE msgid = ? AND groupid = ?;", [
        $msg['msgid'],
        $msg['groupid']
    ], FALSE);

    if (count($hists) == 0) {
        #error_log("#{$msg['msgid']} missing from history");
        $m = new Message($dbhr, $dbhm, $msg['msgid']);
        $m->addToMessageHistory();
    }

    $at++;

    if ($at % 1000 === 0) {
        error_log("...$at / $count");
    }
}
