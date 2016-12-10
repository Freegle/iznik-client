<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=ilovefreegle;charset=utf8";

$dbhf = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle';");

foreach ($groups as $group) {
    $fgroups = $dbhf->preQuery("SELECT regionTitle FROM perch_groups INNER JOIN perch_regions ON perch_regions.regionID = perch_groups.regionID WHERE groupURL = ?;", [
        'http://groups.yahoo.com/group/' . $group['nameshort']
    ]);
    $found = FALSE;
    foreach ($fgroups as $fgroup) {
        $dbhm->preExec("UPDATE groups SET region = ? WHERE id = ?;", [
            $fgroup['regionTitle'],
            $group['id']
        ]);
        error_log("Set region {$group['id']} {$group['nameshort']} {$fgroup['regionTitle']}");
        $found = TRUE;
    }

    if (!$found) {
        error_log("Couldn't set {$group['id']} {$group['nameshort']}");
    }
}
