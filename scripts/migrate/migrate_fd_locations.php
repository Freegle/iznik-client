<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

# Zap any existing configs.  The old DB is the master until we migrate.
$dbhm->preExec("DELETE FROM locations;");

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$c = new Location($dbhr, $dbhm);
$g = new Group($dbhr, $dbhm);

$oldlocs = $dbhold->query("SELECT locations.*, groups.groupname FROM locations INNER JOIN groups ON locations.groupid = groups.groupid AND groups.groupname = 'EdinburghFreegle';");
foreach ($oldlocs as $loc) {
    # Check whether the data looks worth migrating.
    $gid = $g->findByShortName($loc['groupname']);
    error_log("Found $gid for {$loc['groupname']}");

    if ($gid) {

    }
}

