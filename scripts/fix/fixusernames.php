<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$s = new Spam($dbhr, $dbhm);

$sql = "SELECT id FROM users WHERE fullname LIKE 'FBUser%';";
$users = $dbhr->query($sql);

foreach ($users as $user) {
    $u = new User($dbhr, $dbhm, $user['id']);
    $emails = $u->getEmails();

    foreach ($emails as $email) {
        $themail = $email['email'];

        if (strpos(strtolower($themail), 'fbuser') === FALSE) {
            $p = strpos($themail, '@');
            $thename = substr($themail, 0, $p - 1);
            error_log("Name from $themail is $thename");
            $u->setPrivate('fullname', $themail);
        }
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}
