<?php
#
# Purge logs. We do this in a script rather than an event because we want to chunk it, otherwise we can hang the
# cluster with an op that's too big.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

$emails = $dbhr->query("SELECT email, nameshort FROM yahoo_joining INNER JOIN users_emails ON users_emails.id = yahoo_joining.emailid INNER JOIN groups ON groups.id = yahoo_joining.groupid;");
foreach ($emails as $email) {
    $headers = "From: {$email['email']}>\r\n";
    mail($email['nameshort'] . "-subscribe@yahoogroups.com", "Please let me join", "Pretty please", $headers, "-f{$email['email']}");
}