<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$userids = $dbhr->preQuery("SELECT userid, email FROM users_emails WHERE email LIKE '%@ehibbert.org.uk' AND email NOT LIKE 'edward@ehibbert.org.uk' AND email NOT LIKE 'test@ehibbert.org.uk';");
foreach ($userids as $userid) {
    error_log("...{$userid['email']}");
    $u = new User($dbhr, $dbhm, $userid['userid']);
    $u->delete();
}
