<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$opts = getopt('n:');

if (count($opts) > 1) {
    echo "Usage: hhvm group_hulls.php (-n <groupname>)\n";
} else {
    $groupname = presdef('n', $opts, NULL);
    $g = Group::get($dbhr, $dbhm);
    $gid = $groupname ? $g->findByShortName($groupname) : NULL;

    $sql = "SELECT id, nameshort FROM groups WHERE type = 'Freegle' " . ($gid ? " AND id = $gid" : "") . " ORDER BY nameshort ASC;";
    $groups = $dbhr->preQuery($sql);

    foreach ($groups as $group) {
        $sql = "SELECT DISTINCT lat, lng FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid WHERE messages_groups.groupid = ? AND messages.lat IS NOT NULL AND messages.lng IS NOT NULL;";
        $msgs = $dbhr->preQuery($sql, [ $group['id'] ]);
        echo "...{$group['nameshort']} " . count($msgs) . "\n";

        if (count($msgs) >= 15) {
            $data = [];

            $str = "lat, lng\n";

            # Intentionally wrong way round.
            foreach ($msgs as $msg) {
                $str .= "{$msg['lng']},{$msg['lat']}\n";
            }

            file_put_contents("/tmp/points.csv", $str);
            exec("R --no-save < /var/www/iznik/scripts/cli/hulls.r > /tmp/r.out 2>&1", $op);
            $wkt = file_get_contents("/tmp/poly");
            if ($groupname) {
                echo $wkt;
            }
            if (preg_match('/.*"(.*)"/', $wkt, $matches)) {
                $dbhm->preExec("UPDATE groups SET poly = ? WHERE id = ?;", [ $matches[1], $group['id'] ]);
            }
        }
    }
}
