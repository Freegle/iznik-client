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
        $s = new Stats($dbhr, $dbhm, $group['id']);
        $stats = $s->get($date);
        $s->setCount($date, Stats::ACTIVITY, $stats[Stats::APPROVED_MESSAGE_COUNT] + $stats[Stats::SEARCHES]);
        $epoch -= 24 * 60 * 60;
    }
}
