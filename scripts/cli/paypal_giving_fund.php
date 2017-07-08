<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('f:');

if (count($opts) != 1) {
    echo "Usage: hhvm paypal_giving_fund.php -f <CSV file>)\n";
} else {
    $fn = presdef('f', $opts, NULL);
    $fh = fopen($fn, 'r');
    $u = new User($dbhr, $dbhm);

    if ($fh) {
        while (!feof($fh)) {
            # Format is:
            #
            # date	donorName	donorEmail	program	currencyCode	amount
            $fields = fgetcsv($fh);

            $date = $fields[0];
            $name = $fields[1];
            $email = $fields[2];
            $amount = $fields[5];
            
            # Invent a unique transaction ID because we might rerun on the same data.
            $txid = $date . $email;

            error_log("Email $email amount $amount");

            if ($email) {
                # Not anonymous
                $eid = $u->findByEmail($email);

                if ($eid) {
                    # Known user
                    error_log("User $eid");


                } else {
                    error_log("...not known");
                }

                $rc = $dbhm->preExec("INSERT INTO users_donations (userid, Payer, PayerDisplayName, timestamp, TransactionID, GrossAmount) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE userid = ?, timestamp = ?;", [
                    $eid,
                    $email,
                    $name,
                    $date,
                    $txid,
                    $amount,
                    $eid,
                    $date
                ]);

                if ($dbhm->rowsAffected() > 0 && $amount >= 20) {
                    $text = "$name ($email) donated £{$amount}.  Please can you thank them?";
                    $message = Swift_Message::newInstance()
                        ->setSubject("$name ({$email}) donated £{$amount} - please send thanks")
                        ->setFrom(NOREPLY_ADDR)
                        ->setTo(INFO_ADDR)
                        ->setCc('log@ehibbert.org.uk')
                        ->setBody($text);

                    list ($transport, $mailer) = getMailer();
                    $mailer->send($message);
                }
            } else {
                error_log("...anonymous");
            }
        }
    }
}
