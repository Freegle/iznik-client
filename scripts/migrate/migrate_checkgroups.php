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

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND publish = 1 and listable = 1;");
foreach ($groups as $group) {
    $pgroups = $dbhf->query("SELECT * FROM perch_groups WHERE groupURL like '%/{$group['nameshort']}';'")->fetchAll();
    if (count($pgroups) == 0) {
        error_log($group['nameshort'] . " not on old system");
    }
}

$pgroups = $dbhf->query("SELECT * FROM perch_groups WHERE groupPublished = 1;");
foreach ($pgroups as $pgroup) {
    $nameshort = substr($pgroup['groupURL'], strrpos($pgroup['groupURL'], '/') + 1);
    $groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND publish = 1 AND nameshort LIKE ?;", [ $nameshort ]);
    if (count($groups) == 0) {
        error_log("{$pgroup['groupURL']} not on new system");
    }
}
