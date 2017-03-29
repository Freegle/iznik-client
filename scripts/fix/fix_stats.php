<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$groups = $dbhr->preQuery("SELECT * FROM groups ORDER BY nameshort ASC;");
foreach ($groups as $group) {
    error_log("...{$group['nameshort']}");
    for ($i = 0; $i < 12; $i++) {
        $date = date('Y-m-d', $i == 0 ? time() : strtotime("$i days ago"));
        $s = new Stats($dbhr, $dbhm, $group['id']);
        $s->generate($date);
    }
}
