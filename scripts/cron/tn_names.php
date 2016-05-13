<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

# TN users don't come with names, and our usual trick of taking the LHS of the email address doesn't work well because
# they have a groupid in there.  So this is a special case bit of function.
$tns = $dbhr->preQuery("SELECT DISTINCT users_emails.email, users.fullname, users.id FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE backwards LIKE 'moc.gnihtonhsart%' AND firstname IS NULL AND lastname IS NULL AND (fullname IS NULL OR (fullname IS NOT NULL AND LOCATE('-', users.fullname) > 0));");
foreach ($tns as $tn) {
    if (preg_match('/(.*)\-(.*)\@/', $tn['email'], $matches)) {
        error_log("...{$tn['email']}, {$tn['fullname']} => {$matches[1]}");
        $dbhm->preExec("UPDATE users SET fullname = ? WHERE id = ?", [ $matches[1], $tn['id'] ]);
    }
}