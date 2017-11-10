<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$proms = $dbhr->preQuery("SELECT msgid, COUNT(*) AS count FROM `messages_promises` group by msgid having count > 1;");

foreach ($proms as $prom) {
    $max = $dbhr->preQuery("SELECT id FROM messages_promises WHERE msgid = ? ORDER BY promisedat DESC LIMIT 1;", [
        $prom['msgid']
    ]);

    $maxid = $max[0]['id'];

    $dbhm->preQuery("DELETE FROM messages_promises WHERE msgid = ? AND id != ?;", [
        $prom['msgid'],
        $maxid
    ]);
}