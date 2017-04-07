<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$membs = $dbhr->preQuery("SELECT userid FROM memberships WHERE groupid = 21639;");

list ($transport, $mailer) = getMailer();

foreach ($membs as $memb) {
    $u = new User($dbhr, $dbhm, $memb['userid']);
    $msg = Swift_Message::newInstance()
        ->setSubject("ADMIN: Swindon Freecycle")
        ->setFrom([NOREPLY_ADDR => "Swindon Freecycle" ])
        ->setTo($u->getEmailPreferred())
        ->setReplyTo('Nick@pettefar.com')
        ->setBody("Dear loyal active Swindon Freecyclers,\r\n\r\nUnfortunately Freecycle have decided that they no longer want us to use their name.\r\n\r\nWe can either create a renamed group or else you can transfer to the Swindon Freegle group at https://www.ilovefreegle.org/explore/Swindon-Freegle?src=swindon\r\n\r\nRegards,\r\n\r\nThe (ex) Swindon Freecycle moderators.");
    $mailer->send($msg);
}