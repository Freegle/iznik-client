<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND onhere = 0;");
foreach ($groups as $group) {
    $groups2 = $dbhold->query("SELECT * FROM groups WHERE groupname LIKE '{$group['nameshort']}';");
    foreach ($groups2 as $group2) {
        error_log("{$group['nameshort']} now on FD");
        $dbhm->preExec("UPDATE groups SET onhere = 1 WHERE id = ?;", [ $group['id']] );
    }
}
