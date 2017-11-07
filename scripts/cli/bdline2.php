<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$authorities = $dbhr->preQuery("SELECT id FROM authorities;");

$total = count($authorities);
$count = 0;

foreach ($authorities as $authority) {
    error_log("$count");
    $dbhm->preExec("UPDATE authorities SET simplified = ST_Simplify(polygon, 0.001) WHERE id = ?;", [
        $authority['id']
    ]);

    $count++;
}