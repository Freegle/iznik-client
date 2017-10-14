<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

$lockh = lockScript(basename(__FILE__));

$sql = "SELECT id, nameshort FROM groups WHERE `type` = 'Freegle' AND onhere = 1 AND publish = 1 ORDER BY nameshort ASC;";

$groups = $dbhr->preQuery($sql);

foreach ($groups as $group) {
    error_log($group['nameshort']);

    $facebooks = $dbhr->preQuery("SELECT * FROM groups_facebook WHERE groupid = ? AND type = ?;", [
        $group['id'],
        GroupFacebook::TYPE_GROUP
    ]);

    foreach ($facebooks as $facebook) {
        $f = new GroupFacebook($dbhr, $dbhm, $facebook['uid']);
        $f->updatePostableCount();
    }
}

unlockScript($lockh);