<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$sql = "SELECT locations.id, locations.name FROM locations INNER JOIN messages ON locations.id = messages.locationid INNER JOIN messages_groups ON messages_groups.msgid = messages.id INNER JOIN groups ON groups.id = messages_groups.groupid WHERE groups.type = 'Freegle' AND locations.type = 'Postcode' AND LOCATE(' ', locations.name) > 0;";
$locs = $dbhr->preQuery($sql);

foreach ($locs as $loc) {
    $dbhm->preExec("UPDATE locations SET popularity = popularity + 1 WHERE id = ?;", [ $loc['id'] ]);
    error_log($loc['name']);
}