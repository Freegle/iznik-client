<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$dsn = "mysql:host={$dbconfig['host']};dbname=ilovefreegle;charset=utf8";

$dbhf = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = new Group($dbhr, $dbhm);

$oldgroups = $dbhold->query("SELECT * FROM groups WHERE groupname != '';");
foreach ($oldgroups as $group) {
    $type = Group::GROUP_OTHER;

    if (intval($group['freeglegroupid'])) {
        $type = Group::GROUP_FREEGLE;
    } else if (intval($group['reusegroup'])) {
        $type = Group::GROUP_REUSE;
    }

    $g->create(
        $group['groupname'],
        $type
    );

    $id = $g->findByShortName($group['groupname']);
    $g = new Group($dbhr, $dbhm, $id);

    if ($group['freeglegroupid']) {
        $sql = "SELECT * FROM perch_groups WHERE groupURL LIKE '%{$group['groupname']}';";
        $fgroups = $dbhf->preQuery($sql, []);
        foreach ($fgroups as $fgroup) {
            $g->setPrivate('lat', $fgroup['groupLatitude']);
            $g->setPrivate('lng', $fgroup['groupLongitude']);
            $g->setPrivate('type', 'Freegle');
        }
    }
}

