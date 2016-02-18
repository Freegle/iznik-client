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

$sql = "SELECT * FROM moderators WHERE licensesfree > 0;";
$mods = $dbhold->query($sql);

$g = new Group($dbhr, $dbhm);

foreach ($mods as $mod) {
    $vouchers = [];
    for ($i = 0; $i < $mod['licensesfree']; $i++) {
        $vouchers[] = $g->createVoucher();
    }

    $vouchers = implode("\n", $vouchers);

    mail(
        $mod['email'] . ",log@ehibbert.org.uk",
        "Voucher Codes for new ModTools",
        "You have unused licenses in your ModTools account.  When I switch over to the new version of ModTools, you will be able to use these by using the following voucher codes:\n\n$vouchers\n\nEach voucher can only be used once.\n\nRemember that Freegle groups will be free, so you won't need them for those groups, but you can use them on other Yahoo Groups.\n\nPlease keep this email safe.\n\nRegards,\n\nEdward\nModTools",
        "From: ModTools <edward@ehibbert.org.uk>\r\n",
        "-fedward@ehibbert.org.uk"
    );
}
