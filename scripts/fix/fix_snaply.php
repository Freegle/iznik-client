<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$users = $dbhr->preQuery("SELECT userid FROM users_emails WHERE email like '%snaply.co.uk';");

foreach ($users as $user) {
    $u = User::get($dbhr, $dbhm, $user['userid']);
    $emails = $u->getEmails();

    # Only want to remove if emails are either ours or snaply
    $cando = TRUE;
    foreach ($emails as $email) {
        if (!ourDomain($email['email']) && strpos($email['email'], 'snaply.c') === FALSE) {
            $cando = FALSE;
        }
    }

    if ($cando) {
        $membs = $u->getMemberships();
        foreach ($membs as $memb) {
            #error_log($u->getEmailPreferred() . " on {$memb['id']}");
            $u->removeMembership($memb['id']);
        }
    } else {
        error_log("Can't remove "  . var_export($emails, TRUE));
    }
}
