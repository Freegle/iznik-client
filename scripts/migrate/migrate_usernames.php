<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";
$moddbh = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";
$fddbh = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$at = 0;$s = new Spam($dbhr, $dbhm);

$sql = "SELECT id FROM users;";
$users = $dbhr->query($sql);

foreach ($users as $user) {
    $u = new User($dbhr, $dbhm, $user['id']);
    $emails = $u->getEmails();

    foreach ($emails as $email) {
        $themail = $email['email'];
        $thename = NULL;

        $fds = $fddbh->query("SELECT * FROM facebook WHERE email = " . $fddbh->quote($themail) . ";");
        foreach ($fds as $fd) {
            $thename = strlen(trim($fd['facebookname'])) > 0 ? trim($fd['facebookname']) : NULL;
        }

        if (!$thename) {
            $mods = $moddbh->query("SELECT * FROM moderators WHERE email = " . $moddbh->quote($themail) . ";");
            foreach ($mods as $mod) {
                $thename = strlen(trim($mod['name'])) > 0 ? trim($mod['name']) : NULL;

                if (!$thename) {
                    $thename = strlen(trim($mod['yahooid'])) > 0 ? trim($mod['yahooid']) : NULL;
                }
            }
        }

        if (!$thename) {
            if (strpos(strtolower($themail), 'fbuser') === FALSE) {
                $p = strpos($themail, '@');
                $thename = substr($themail, 0, $p);
                #error_log("Name from $themail is $thename");
            }
        }

        if ($thename) {
            #error_log("$themail => $thename");
            $u->setPrivate('fullname', $thename);
        }
    }

    $at++;

    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}
