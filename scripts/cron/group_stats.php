<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$date = date('Y-m-d', strtotime("yesterday"));
$groups = $dbhr->preQuery("SELECT * FROM groups;");
foreach ($groups as $group) {
    error_log($group['nameshort']);
    $s = new Stats($dbhr, $dbhm, $group['id']);
    $s->generate($date);
}
