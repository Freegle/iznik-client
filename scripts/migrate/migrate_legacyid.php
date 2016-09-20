<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhd = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = Group::get($dbhr, $dbhm);

$dbhm->preExec("UPDATE groups SET legacyid = NULL;");
$groups = $dbhd->query("SELECT * FROM groups;");
foreach ($groups as $group) {
    $nameshort = $group['groupname'];
    $gid = $g->findByShortName($nameshort);

    if ($gid) {
        $g = Group::get($dbhr, $dbhm, $gid);
        $g->setPrivate('legacyid', $group['groupid']);
    }
}
