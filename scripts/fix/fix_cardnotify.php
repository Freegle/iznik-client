<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/Request.php');

$users = $dbhr->preQuery("SELECT * FROM users_requests WHERE completed IS NOT NULL AND notifiedmods IS NULL AND type = 'BusinessCards';");

foreach ($users as $user) {
    $r = new Request($dbhr, $dbhm, $user['id']);
    $r->notifyMods();
}