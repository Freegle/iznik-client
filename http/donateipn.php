<?php

# When people donate to us, PayPal will trigger a call to this script.
#
# As a fallback we also have paypal_download on a cron.

require_once dirname(__FILE__) . '/../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$u = new User($dbhr, $dbhm);

use PayPal\IPN\PPIPNMessage;
use PayPal\PayPalAPI\TransactionSearchReq;
use PayPal\PayPalAPI\TransactionSearchRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

$config = array(
    "mode" => "live",
    'log.LogEnabled' => true,
    'log.FileName' => '/tmp/PayPalIPN.log',
    'log.LogLevel' => 'FINE',
    "acct1.UserName" => PAYPAL_USERNAME,
    "acct1.Password" => PAYPAL_PASSWORD,
    "acct1.Signature" => PAYPAL_SIGNATURE
);

$ipnMessage = new PPIPNMessage(null, $config);
$transaction = $ipnMessage->getRawData();

foreach ($transaction as $key => $value) {
    error_log("IPN: $key => $value");
}

if ($transaction['mc_gross'] > 0) {
    $eid = $u->findByEmail($transaction['payer_email']);

    $dbhm->preExec("INSERT INTO users_donations (userid, Payer, PayerDisplayName, timestamp, TransactionID, GrossAmount) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE userid = ?, timestamp = ?;", [
        $eid,
        $transaction['payer_email'],
        "{$transaction['first_name']} {$transaction['last_name']}",
        date("Y-m-d H:i:s", strtotime($transaction['payment_date'])),
        $transaction['txn_id'],
        $transaction['mc_gross'],
        $eid,
        date("Y-m-d H:i:s", strtotime($transaction['payment_date']))
    ]);
}
