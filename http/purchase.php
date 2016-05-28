<?php
$scriptstart = microtime(false);
date_default_timezone_set('UTC');
session_start();
$_SESSION['writable'] = TRUE;
define( 'BASE_DIR', dirname(__FILE__) . '/..' );
require_once(BASE_DIR . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

global $dbhr, $dbhm;

$g = new Group($dbhr, $dbhm);
$l = new Log($dbhr, $dbhm);

try {
    # Log the request details
    $l->log([
        'type' => Log::TYPE_GROUP,
        'subtype' => Log::SUBTYPE_LICENSE_PURCHASE,
        'text' => var_export($_POST, TRUE)
    ]);

    # Get the request from PayPal
    $req = 'cmd=_notify-validate';

    foreach ($_POST as $key => $value) {
        $value = urlencode(stripslashes($value));
        $req .= "&$key=$value";
    }

    # Post back to PayPal to validate.
    $header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
    $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
    $fp = fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);

    # Get the parameters
    $item_name = array_key_exists('item_name1', $_POST) ? $_POST['item_name1'] : '';
    $item_number = array_key_exists('item_number1', $_POST) ? $_POST['item_number1'] : '';
    $payment_status = $_POST['payment_status'];
    $payment_amount = $_POST['mc_gross'];
    $payment_currency = $_POST['mc_currency'];
    $txn_id = $_POST['txn_id'];
    $receiver_email = $_POST['receiver_email'];
    $payer_email = $_POST['payer_email'];

    if (!$fp) {
        error_log("Failed to post request back to paypal");
        mail("log@ehibbert.org.uk", "ModTools Error: Payment failed to contact PayPal", $sql . "\r\n\r\n" . var_export($_REQUEST, true) . "\r\n\r\n$res", NULL, '-fnoreply@modtools.org');
    } else {
        fputs($fp, $header . $req);
        while (!feof($fp)) {
            $res = fgets($fp, 1024);

            if (strcmp($res, "VERIFIED") == 0) {
                # Valid payment
                if (($payment_currency == 'USD') && ($payment_amount == '5.00')) {
                    # For the correct amount - issue a voucher.
                    $voucher = $g->createVoucher();

                    $headers = "From: ModTools <edward@ehibbert.org.uk>\r\nTo: $payer_email\r\nCC: log@ehibbert.org.uk";
                    mail(NULL, "ModTools - Thanks for your payment", "Thanks for buying a license for ModTools.  Your voucher code is\n\n" . $supp['voucher'] .
                        "\n\nTo use this voucher, sign in at http://modtools.org, go to Settings->Personal Settings and click the 'Use Voucher' button.  Please use copy and paste for the voucher code to reduce typos.\n\n" .
                        "The license will then be credited to your account.  Once you have done this, go to Settings->Groups and add the license to your group.\n\n" .
                        "You can find more information at http://wiki.modtools.org/index.php?title=Main_Page .  The Getting Started section will help you get going.\n\n" .
                        "Regards,\n\n" .
                        "Edward",
                        $headers, '-t -f edward@ehibbert.org.uk');

                    mail("log@ehibbert.org.uk", "ModTools: Payment succeeded", var_export($_POST, TRUE), NULL, '-fnoreply@modtools.org');
                } else {
                    mail("log@ehibbert.org.uk", "ModTools: Payment action required", var_export($_POST, TRUE), NULL, '-fnoreply@modtools.org');
                }
            } else if (strcmp($res, "INVALID") == 0) {
                // log for manual investigation
                mail("log@ehibbert.org.uk", "ModTools Error: Payment failed validation", var_export($_REQUEST, true), NULL, '-fnoreply@modtools.org');
            }
        }

        fclose($fp);
    }
} catch (Exception $e) {
    mail("log@ehibbert.org.uk", "ModTools Error: Payment exception", var_export($e, TRUE) . "\n\n" . var_export($_REQUEST, true), NULL, '-fnoreply@modtools.org');
    error_log("Exception during purchase " . var_export($e, true));
}
?>
