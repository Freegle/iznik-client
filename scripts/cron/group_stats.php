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

# Find what proportion of overall activity an individual group is responsible for.  We will use this when calculating
# a fundraising target.
$date = date('Y-m-d', strtotime("30 days ago"));
$totalact = $dbhr->preQuery("SELECT SUM(count) AS total FROM stats INNER JOIN groups ON stats.groupid = groups.id WHERE stats.type = ? AND groups.type = ? AND publish = 1 AND onhere = 1 AND date >= ?;", [
    Stats::APPROVED_MESSAGE_COUNT,
    Group::GROUP_FREEGLE,
    $date
]);

$target = DONATION_TARGET;
$fundingcalc = 0;

foreach ($totalact as $total) {
    $tot = $total['total'];

    $groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = ? AND publish = 1 AND onhere = 1 ORDER BY LOWER(nameshort) ASC;", [
        Group::GROUP_FREEGLE
    ]);

    foreach ($groups as $group) {
        $acts = $dbhr->preQuery("SELECT SUM(count) AS count FROM stats WHERE stats.type = ? AND groupid = ? AND date >= ?;", [
            Stats::APPROVED_MESSAGE_COUNT,
            $group['id'],
            $date
        ]);

        #error_log("#{$group['id']} {$group['nameshort']} pc = $pc from {$acts[0]['count']} vs $tot");
        $pc = 100 * $acts[0]['count'] / $tot;

        $dbhm->preExec("UPDATE groups SET activitypercent = ? WHERE id = ?;", [
            $pc,
            $group['id']
        ]);

        # Calculate fundraising target.  Round up to £50.
        $portion = ceil($pc * $target / 1000) * 10;
        $portion = max(50, $portion);
        error_log("{$group['nameshort']} target £$portion");
        $fundingcalc += $portion;

        $dbhm->preExec("UPDATE groups SET fundingtarget = ? WHERE id = ?;", [
            $portion,
            $group['id']
        ]);
    }
}

error_log("\n\nTotal target £$fundingcalc");