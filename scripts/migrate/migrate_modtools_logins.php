<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$u = User::get($dbhr, $dbhm);

$mods = $dbhold->query("SELECT * FROM moderators;");
foreach ($mods as $mod) {
    $id = $u->findByEmail($mod['email']);

    if (!$id) {
        $id = $u->create(NULL, NULL, $mod['name'], "Migrated from ModTools Logins");
        $u->addEmail($mod['email'], 1);
        $u->addLogin(User::LOGIN_YAHOO, $mod['yahooid']);
    }
}

