<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$locs = $dbhr->query("SELECT id, CASE WHEN ourgeometry IS NOT NULL THEN ourgeometry ELSE locations.geometry END AS geometry FROM locations LEFT JOIN locations_spatial ON locations.id = locations_spatial.locationid WHERE locations_spatial.locationid IS NULL HAVING geometry IS NOT NULL;");

$count = 0;
foreach ($locs as $loc) {
    try {
        $dbhm->preExec("INSERT INTO locations_spatial (locationid, geometry) VALUES (?,(SELECT geometry FROM locations WHERE id = ?));", [
            $loc['id'],
            $loc['id']
        ]);
    } catch (Exception $e) {
        error_log("{$loc['id']} failed " . $e->getMessage());
    }

    $count++;

    if ($count % 1000 === 0) {
        error_log("...$count / ");
    }
}


