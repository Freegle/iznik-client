<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$sql = "SELECT users_emails.userid, groupid FROM users_emails INNER JOIN memberships ON users_emails.userid = memberships.userid AND users_emails.email LIKE 'confirmmod%';";
$membs = $dbhr->preQuery($sql);

foreach ($membs as $memb) {
    $u = new User($dbhr, $dbhm, $memb['userid']);
    $u->removeMembership($memb['groupid']);
}