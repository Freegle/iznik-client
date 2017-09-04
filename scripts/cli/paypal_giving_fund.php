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
        # These donations don't have a transaction ID.  That makes it tricky since we want to rerun this job
        # repeatedly without double-counting.  So we delete donations within the date range of the CSV file
        # before readding them.  That means we need to know what the date range is.
        $donations = [];
        $mindate = NULL;
        $minepoch = PHP_INT_MAX;
            
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
            $txid = $date . $email . count($donations);

            error_log("$date email $email amount $amount");

            if ($email) {
                # Not anonymous
                $eid = $u->findByEmail($email);
            }

            $donations[] = [
                'eid' => $eid,
                'email' => $email,
                'name' => $name,
                'date' => $date,
                'txid' => $txid,
                'amount' => $amount
            ];
            
            $epoch = strtotime($date);

            if ($amount > 0) {
                # Ignore debits, otherwise we'll delete old donations.  This will mean that cancelled donations
                # still get counted, but that isn't a significant amount.
                $mindate = (!$minepoch || $epoch < $minepoch) ? $date : $mindate;
                $minepoch = (!$minepoch || $epoch < $minepoch) ? $epoch : $minepoch;
            }
        }

        error_log("CSV covers $mindate");

        # Save off the thanks
        $dbhm->preExec("DELETE FROM users_donations WHERE timestamp >= ? AND source = 'PayPalGivingFund';", [
            $mindate
        ]);
        error_log("Deleted " . $dbhm->rowsAffected());
        
        foreach ($donations as $donation) {
            error_log("Record {$donation['date']} {$donation['email']} {$donation['amount']}");
            $rc = $dbhm->preExec("INSERT INTO users_donations (userid, Payer, PayerDisplayName, timestamp, TransactionID, GrossAmount, source) VALUES (?,?,?,?,?,?,'PayPalGivingFund') ON DUPLICATE KEY UPDATE userid = ?, timestamp = ?, source = 'PayPalGivingFund';", [
                $donation['eid'],
                $donation['email'],
                $donation['name'],
                $donation['date'],
                $donation['txid'],
                $donation['amount'],
                $donation['eid'],
                $donation['date']
            ]);

//            if ($dbhm->rowsAffected() > 0 && $amount >= 20) {
//                $text = "$name ($email) donated £{$amount}.  Please can you thank them?";
//                $message = Swift_Message::newInstance()
//                    ->setSubject("$name ({$email}) donated £{$amount} - please send thanks")
//                    ->setFrom(NOREPLY_ADDR)
//                    ->setTo(INFO_ADDR)
//                    ->setCc('log@ehibbert.org.uk')
//                    ->setBody($text);
//
//                list ($transport, $mailer) = getMailer();
//                $mailer->send($message);
//            }
        }

        $mysqltime = date ("Y-m-d", strtotime("Midnight yesterday"));
        $dons = $dbhr->preQuery("SELECT SUM(GrossAmount) AS total FROM users_donations WHERE DATE(timestamp) = ?;", [
            $mysqltime
        ]);

        error_log("\n\nYesterday $mysqltime: £{$dons[0]['total']}");
    }
}
