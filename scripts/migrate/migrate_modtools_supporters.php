<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$sql = "SELECT * FROM supporters;";
$supps = $dbhold->query($sql);
foreach ($supps as $supp) {
    $sql = "REPLACE INTO supporters (name, type, email, display, voucher, vouchercount, voucheryears, anonymous) VALUES (?,?,?,?,?,?,?,?);";
    $dbhm->preExec($sql, [
        $supp['name'],
        $supp['type'],
        $supp['email'],
        $supp['display'],
        $supp['voucher'],
        $supp['vouchercount'],
        $supp['voucheryears'],
        $supp['anonymous'],
    ]);
}

$dbhm->preExec("UPDATE supporters SET display = NULL WHERE display = '';");
$dbhm->preExec("UPDATE supporters SET name = NULL WHERE name = '';");
