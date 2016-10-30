<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/session/Facebook.php');

$opts = getopt('i:m:u:');

if (count($opts) < 3) {
    echo "Usage: hhvm user_facebook_notify.php (-i <id of user to notify>) -m <Message to send> -u <URL to link to>\n";
} else {
    $id = $opts['i'];
    $userq = $id ? (" AND userid = " . intval($id)) : '';
    $message = $opts['m'];
    $url = $opts['u'];

    $f = new Facebook($dbhr, $dbhm);

    $users = $dbhr->preQuery("SELECT * FROM users_logins WHERE type = 'Facebook' $userq;");
    foreach ($users as $user) {
        $f->notify($user['uid'], $message, $url);
    }
}
