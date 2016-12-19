<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$groups = $dbhr->preQuery("SELECT * FROM groups  WHERE type = 'Freegle' ORDER BY nameshort ASC;");
foreach ($groups as $group) {
    error_log("...{$group['nameshort']}");
    $epoch = strtotime("17th December 2016");

    for ($i = 0; $i < 400; $i++) {
        $date = date('Y-m-d', $epoch);
        error_log($date);
        $s = new Stats($dbhr, $dbhm, $group['id']);
        $s->generate($date);
        $epoch -= 24 * 60 * 60;
    }
}
