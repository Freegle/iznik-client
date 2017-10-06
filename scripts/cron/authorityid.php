<?php
# Use geoPHP because we see some errors if we use ST_Intersects.

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');

error_log("Load auths");

$g = new geoPHP();
$auths = $dbhr->preQuery("SELECT id, name, AsText(simplified) as geom FROM authorities");

$authp = [];

foreach ($auths as $auth) {
    $authg = $g->load($auth['geom']);
    $authp[] = [ $auth, $authg ];
}

error_log("Loaded");

$groups = $dbhr->preQuery("SELECT groups.*, CASE WHEN poly IS NULL THEN polyofficial ELSE poly END AS poly FROM groups WHERE type = ? ORDER BY LOWER(nameshort) ASC;", [
    Group::GROUP_FREEGLE
]);

foreach ($groups as $group) {
    $maxarea = 0;
    $maxid = NULL;

    $garea = $g->load($group['poly']);

    if ($garea) {

        $garea = $garea->convexHull();

        foreach ($authp as $ent) {
            $auth = $ent[0];
            $authg = $ent[1];

            try {
                if ($garea->contains($authg)) {
                    error_log("Contains area");
                    # An authority within a group area must be the right one.
                    $maxarea = PHP_INT_MAX;
                    $maxid = $auth['id'];
                    $maxname = $auth['name'];
                } else {
                    # Otherwise we want the biggest intersect.
                    $i = $authg->intersection($garea);

                    if ($i) {
                        $area = $i->area();

                        if ($area > $maxarea) {
                            $maxarea = $area;
                            $maxid = $auth['id'];
                            $maxname = $auth['name'];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Exception on {$auth['name']}" . $e->getMessage());
            }
        }

        if ($maxid) {
            error_log("{$group['nameshort']} => $maxname");
            $dbhr->preExec("UPDATE groups SET authorityid = ? WHERE id = ?;", [
                $maxid,
                $group['id']
            ]);
        } else {
            error_log("{$group['nameshort']} => NONE FOUND");
        }
    } else {
        error_log("{$group['nameshort']} => INVALID POLY");
    }
}
