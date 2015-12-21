<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$l = new Location($dbhr, $dbhm);

error_log("Reset");
$dbhm->preExec("UPDATE locations SET postcodeid = NULL, areaid = NULL WHERE postcodeid IS NOT NULL OR areaid IS NOT NULL;");

#$locs = $dbhm->query("SELECT id, name, gridid FROM locations WHERE postcodeid IS NULL;");
error_log("Find locs to correct");
$locs = $dbhm->query("SELECT id, name, gridid FROM locations WHERE postcodeid IS NULL AND id in (SELECT DISTINCT locationid FROM messages INNER JOIN messages_groups ON messages_groups.groupid = 21354 AND messages_groups.msgid = messages.id) LIMIT 20;");

$count = 0;

foreach ($locs as $loc) {
    echo "{$loc['id']} - {$loc['name']} => ";
    $l->setParents($loc['id'], $loc['gridid']);
    $count++;

    if ($count % 1000 == 0) {
        error_log("$count...");
    }
}
