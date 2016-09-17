<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$l = new Location($dbhr, $dbhm);
$g = Group::get($dbhr, $dbhm);

$oldlocs = $dbhold->query("SELECT locations_approved.*, groups.groupname FROM locations_approved INNER JOIN groups ON locations_approved.groupid = groups.groupid;");
foreach ($oldlocs as $loc) {
    # Check whether the data looks worth migrating.
    $gid = $g->findByShortName($loc['groupname']);

    if ($gid) {
        # Find the top match
        $res = $l->search($loc['location'], $gid, 1);
        if (count($res) > 0 && strtolower($loc['location']) == strtolower($res[0]['name'])) {
            error_log("{$loc['location']} => {$res[0]['name']} {$loc['popularity']}");
            $sql = "UPDATE locations SET popularity = ? WHERE id = ?;";
            $dbhm->preExec($sql, [$loc['popularity'], $res[0]['id']]);
        }
    }
}

