<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$membs = $dbhr->preQuery("SELECT groups.nameshort, groups.lastyahoomembersync, users.fullname, memberships.* FROM memberships LEFT OUTER JOIN memberships_yahoo ON memberships_yahoo.membershipid = memberships.id INNER JOIN users ON users.id = memberships.userid inner join groups on groups.id = memberships.groupid WHERE memberships_yahoo.membershipid IS NULL and memberships.role = 'Moderator' ORDER BY `groups`.`lastyahoomembersync` DESC");

foreach ($membs as $memb) {
    $dbhm->preExec("DELETE FROM memberships WHERE id = ?;", [ $memb['id'] ]);
}