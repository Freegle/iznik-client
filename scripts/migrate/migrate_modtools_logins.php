<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/Login.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    // PDO::ATTR_PERSISTENT => true, // Persistent connections seem to result in a leak - show status like 'Threads%'; shows an increasing number
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
}

