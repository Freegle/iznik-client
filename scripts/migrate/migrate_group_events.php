<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Twitter.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhd = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = new Group($dbhr, $dbhm);

$sql = "SELECT * FROM groups WHERE grouppublish = 1;";
$fgroups = $dbhd->query($sql);

foreach ($fgroups as $fgroup) {
    error_log("FD group {$fgroup['groupname']}");
    $gid = $g->findByShortName($fgroup['groupname']);
    $g = new Group($dbhr, $dbhm, $gid);
    $settings = $g->getPublic()['settings'];
    $settings['communityevents'] = $fgroup['eventsdisabled'] ? 0 : 1;
    $g->setPrivate('lasteventsroundup', $fgroup['lasteventroundup']);
    $g->setSettings($settings);
}

