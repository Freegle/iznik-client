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
        ->setBody("Dear ex-Swindon Freecyclers,\n\nWe moderators have looked at the various alternatives and have decided for us that the best way forward is to recommend you join forces with the existing Swindon group run by the Freegle organisation.\n\nFreegle operate in almost the same way we used to, using e-Mail and web, because they used to be Freecyclers and basically just renamed themselves in order to get back control from the American run Freecycle organisation.  Apart from a different logo and web and e-mail addresses you shouldn't notice any difference.\n\nWe moderators are also moving across and will become Freegle moderators so we will be there to help you and address all your concerns - \"same old same old\" as they say.\n\nWe hope that you can join us all and continue with your wonderful re-use and up-cycling and free-recycling efforts!\n\nLinks:\nTo join Freegle: https://ilovefreegle.org/explore/Swindon-Freegle\nTo use the Trash Nothing system (very good!):\n https://trashnothing.com/swindon-freegle\nSpaceBook (be careful of the fake news!): https://facebook.com/SwindonFreegle\nChitter-chatter: https://twitter.com/SwindonFreegle\n\n(TL:DR Join Freegle)\n\nRegards and hopefully see you soon!\n\nNick, Fiona and Sue");
    $mailer->send($msg);
}