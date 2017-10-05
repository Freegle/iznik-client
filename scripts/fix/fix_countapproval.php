<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$groups = $dbhr->preQuery("SELECT groups.id, groups.nameshort FROM groups WHERE groups.type = 'Freegle' AND publish = 1 AND onyahoo = 1 ORDER BY LOWER(nameshort) ASC;");
$count = 0;
$auto = 0;
$total = 0;
foreach ($groups as $group) {
    $g = new Group($dbhr, $dbhm, $group['id']);
    $total++;

    if (!$g->getSetting('autoapprove', ['members' => 0])['members']) {
        $html = file_get_contents("https://groups.yahoo.com/neo/groups/{$group['nameshort']}/info");
        $p = strpos($html, "Membership requires approval");
        if ($p !== FALSE) {
            $count++;
            error_log("{$group['nameshort']}...approval");
        } else {
            error_log("{$group['nameshort']}...not approved");
        }
    } else {
        error_log("{$group['nameshort']}...autoapprove");
        $auto++;
    }
}

error_log("Total $total approval $count autoapproal $auto");