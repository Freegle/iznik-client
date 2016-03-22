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

$g = new Group($dbhr, $dbhm);

$pgroups = $dbhf->query("SELECT * FROM perch_groups;");
foreach ($pgroups as $pgroup) {
    $p = strrpos($pgroup['groupURL'], '/');
    $name = substr($pgroup['groupURL'], $p + 1);
    $sql = "SELECT * FROM groups WHERE nameshort = ?;";
    $groups = $dbhr->preQuery($sql, [ $name ]);
    $found = FALSE;
    foreach ($groups as $group) {
        $found = TRUE;
        $dbhm->preExec("UPDATE groups SET publish = ? WHERE id = ?;", [ $pgroup['groupPublished'], $group['id'] ]);
    }
}