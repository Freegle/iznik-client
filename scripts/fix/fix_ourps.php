<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$g = new Group($dbhr, $dbhm);

$memberships = $dbhr->preQuery("SELECT * FROM memberships WHERE ourPostingStatus IS NOT NULL AND ourPostingStatus IN ('PROHIBITED', 'UNMODERATED');");

foreach ($memberships as $membership) {
    $dbhm->preExec("UPDATE memberships SET ourPostingStatus = ? WHERE id = ?;", [
        $g->ourPS($membership['ourPostingStatus']),
        $membership['id']
    ], FALSE);
}