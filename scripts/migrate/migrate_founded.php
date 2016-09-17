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

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhd = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = Group::get($dbhr, $dbhm);

$groups = $dbhf->query("SELECT * FROM perch_groups WHERE groupPublished = 1;");
foreach ($groups as $group) {
    $nameshort = substr($group['groupURL'], strrpos($group['groupURL'], '/') + 1);
    $gid = $g->findByShortName($nameshort);

    if ($gid) {
        $g = Group::get($dbhr, $dbhm, $gid);
        $g->setPrivate('founded', $group['groupPreferredStartDate'] ? $group['groupPreferredStartDate'] : $group['groupStartDate']);
    }
}
