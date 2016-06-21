<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$dsn = "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname=republisher;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle'");

foreach ($groups as $group) {
    $fdgroups = $dbhold->query("SELECT * FROM groups WHERE groupname LIKE '%{$group['nameshort']}%';");
    $onhere = FALSE;
    foreach ($fdgroups as $fdgroup) {
        $onhere = TRUE;
    }
    error_log("{$group['nameshort']} $onhere");
    $dbhm->preExec("UPDATE groups set onhere = ? WHERE id = ?;", [ $onhere, $group['id']]);
}