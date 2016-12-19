<?php

define(SQLLOG, FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$searches = $dbhr->preQuery("SELECT * FROM search_history WHERE locationid IS NOT NULL AND groups IS NULL;");

foreach ($searches as $search) {
    $l = new Location($dbhr, $dbhm, $search['locationid']);
    $nears = $l->groupsNear(200);
    $dbhm->preExec("UPDATE search_history SET groups = ? WHERE id = ?;", [
        implode(',', $nears),
        $search['id']
    ]);
}