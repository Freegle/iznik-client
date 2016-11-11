<?php

define(SQLLOG, FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

# Use dbhm to bypass cache.
$l = new Location($dbhm, $dbhm);

# Find excessively large areas.
$locs = $dbhm->query("SELECT DISTINCT areaid FROM locations WHERE maxdimension > " . Location::TOO_LARGE . ";");

$count = 0;

foreach ($locs as $loc) {
    try {
        $usings = $dbhm->preQuery("SELECT id, gridid FROM locations WHERE areaid = ?;", [ $loc['areaid'] ]);
        foreach ($usings as $using) {
            #error_log("Set parents for {$using['id']} {$using['name']}");
            $l->setParents($using['id'], $using['gridid']);
        }
    } catch (Exception $e) {

    }
}
