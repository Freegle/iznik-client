<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

list ($transport, $mailer) = getMailer();

$message = Swift_Message::newInstance()
    ->setSubject("Test")
    ->setFrom('test@' . GROUP_DOMAIN)
    ->setTo('ounyAQAN03hm7m@dkimvalidator.com')
    ->setBody("Test");
$mailer->send($message);
