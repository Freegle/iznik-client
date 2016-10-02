<?php

define(SQLLOG, FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$l = new Location($dbhr, $dbhm);

$locs = $dbhm->preQuery("SELECT id, name, gridid FROM locations WHERE type = 'Postcode' AND LOCATE(' ', name) > 0;");

$count = 0;

foreach ($locs as $loc) {
    #echo "{$loc['id']} - {$loc['name']} => ";
    $l->setParents($loc['id'], $loc['gridid']);
    $count++;

    error_log("$count..." . count($locs));
}
