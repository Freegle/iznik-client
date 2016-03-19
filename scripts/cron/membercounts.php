<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$date = date('Y-m-d', strtotime("yesterday"));
$groups = $dbhr->preQuery("SELECT * FROM groups;");
foreach ($groups as $group) {
    $sql = "SELECT COUNT(*) AS count FROM memberships WHERE groupid = ?;";
    $counts = $dbhr->preQuery($sql, [ $group['id'] ]);
    foreach ($counts as $count) {
        error_log("...{$group['nameshort']} = {$count['count']}");
        $sql = "UPDATE groups SET membercount = ? WHERE id = ?;";
        $counts = $dbhr->preExec($sql, [
            $count['count'],
            $group['id']
        ]);
    }
}
