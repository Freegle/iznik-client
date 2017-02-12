<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/user/User.php');

list ($transport, $mailer) = getMailer();
$lines = explode("\n", file_get_contents('/tmp/a.c'));

$lost = [];
$u = new User($dbhr, $dbhm);

foreach ($lines as $line) {
    if (preg_match('/.* (.*?) \<\= (.*?) .*/', $line, $matches)) {
        error_log("Msg {$matches[1]} from {$matches[2]}");
        $lost[$matches[1]] = $matches[2];
    } else if (preg_match('/.* (.*?) \=\> .*replyto-(.*?)-{{userid}}@/', $line, $matches)) {
        error_log("Msg {$matches[1]} for msg {$matches[2]}");
        $from = $lost[$matches[1]];
        $uid = $u->findByEmail($from);

        if ($uid) {
            $msgid = $matches[2];
            $m = new Message($dbhr, $dbhm, $msgid);
            $u = new User($dbhr, $dbhm, $uid);

            if ($u->getId() == $uid) {
                $replyto = "replyto-$msgid-$uid@" . USER_DOMAIN;
                $text = "I'm afraid we couldn't deliver your mail.  If you're still interested, please reply to this mail, and we'll do better this time!\n\nOur apologies,\n\nFreegle";
                $message = Swift_Message::newInstance()
                    ->setSubject('Re: ' . $m->getSubject())
                    ->setFrom([NOREPLY_ADDR => 'Freegle'])
                    ->setReturnPath(NOREPLY_ADDR)
                    ->setReplyTo($replyto)
                    ->setTo($u->getEmailPreferred())
                    ->setBody($text);

                error_log($u->getEmailPreferred() . " $replyto");
            }

            $transport->send($message);
        }
    }
}

