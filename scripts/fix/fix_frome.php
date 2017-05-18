<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$users = $dbhr->preQuery("select email from memberships inner join memberships_yahoo on memberships_yahoo.membershipid = memberships.id inner join users_emails on users_emails.id = memberships_yahoo.emailid where groupid = 353440;");

foreach ($users as $user) {
    if (ourDomain($user['email'])) {
        error_log("...subscribe {$user['email']}");
        list ($transport, $mailer) = getMailer();
        $message = Swift_Message::newInstance()
            ->setSubject('Please let me join')
            ->setFrom([$user['email']])
            ->setTo('freecyclesfrome-subscribe@yahoogroups.com')
            ->setDate(time())
            ->setBody('Pretty please');
        $mailer->send($message);
    }
}
