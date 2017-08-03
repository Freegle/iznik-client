<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host=localhost;port=3309;dbname=iznik;charset=utf8";
$dbhback = new LoggedPDO($dsn, SQLUSER, SQLPASSWORD, array(
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_EMULATE_PREPARES => FALSE
));

$dbhm->preExec("DELETE FROM users_donations WHERE source = 'PayPalGivingFund';", [
]);

$donations = $dbhback->preQuery("SELECT * FROM users_donations  WHERE source = 'PayPalGivingFund';");
foreach ($donations as $donation) {
    try {
        $dbhm->preExec("INSERT INTO users_donations (`type`, userid, Payer, PayerDisplayName, timestamp, TransactionID, GrossAmount, Source) VALUES (?,?,?,?,?,?,?,?);", [
            $donation['type'],
            $donation['userid'],
            $donation['Payer'],
            $donation['PayerDisplayName'],
            $donation['timestamp'],
            $donation['TransactionID'],
            $donation['GrossAmount'],
            'PayPalGivingFund'
        ]);
    } catch (Exception $e) {}
}
