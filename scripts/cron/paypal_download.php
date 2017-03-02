<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$lockh = lockScript(basename(__FILE__));

$u = new User($dbhr, $dbhm);

use PayPal\PayPalAPI\TransactionSearchReq;
use PayPal\PayPalAPI\TransactionSearchRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

$config = array(
    "mode" => "live",
    'log.LogEnabled' => true,
    'log.FileName' => '/tmp/PayPal.log',
    'log.LogLevel' => 'FINE',
    "acct1.UserName" => PAYPAL_USERNAME,
    "acct1.Password" => PAYPAL_PASSWORD,
    "acct1.Signature" => PAYPAL_SIGNATURE
);

$paypalService = new PayPalAPIInterfaceServiceService($config);
$start = strtotime("00:00 today");
$end = strtotime("00:00 tomorrow");
$limit = strtotime('00:00 30 days ago');

do {
    error_log("..." . date("Y-m-d H:i:s", $start));

    $found = FALSE;

    try {
        $transactionSearchRequest = new TransactionSearchRequestType();
        $transactionSearchRequest->StartDate = ISODate(date("Y-m-d H:i:s", $start));
        $transactionSearchRequest->EndDate = ISODate(date("Y-m-d H:i:s", $end));

        $tranSearchReq = new TransactionSearchReq();
        $tranSearchReq->TransactionSearchRequest = $transactionSearchRequest;
        $transactionSearchResponse = $paypalService->TransactionSearch($tranSearchReq);
        $transactions = json_decode(json_encode($transactionSearchResponse->PaymentTransactions), true);

        if (gettype($transactions) == 'array') {
            foreach ($transactions as $transaction) {
                if ($transaction['GrossAmount']['value'] > 0) {
                    $eid = $u->findByEmail($transaction['Payer']);

                    error_log("{$transaction['Payer']} => $eid");
                    $dbhm->preExec("INSERT INTO users_donations (userid, Payer, PayerDisplayName, timestamp, TransactionID, GrossAmount) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE userid = ?;", [
                        $eid,
                        $transaction['Payer'],
                        $transaction['PayerDisplayName'],
                        date("Y-m-d H:i:s", strtotime($transaction['Timestamp'])),
                        $transaction['TransactionID'],
                        $transaction['GrossAmount']['value'],
                        $eid
                    ]);
                }
            }
        }
    } catch (Exception $ex) {
        error_log("Failed " . $ex->getMessage());
    }

    $start -= 24 * 60 * 60;
    $end -= 24 * 60 * 60;
} while ($start > $limit);

unlockScript($lockh);