<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');
require_once(IZNIK_BASE . '/mailtemplates/newsfeed/try.php');

$opts = getopt('i:g:');

function sendOne($dbhr, $dbhm, $uid, $groupname) {
    try {
        $u = new User($dbhr, $dbhm, $uid);
        $email = $u->getEmailPreferred();
        error_log($email);
        list ($transport, $mailer) = getMailer();

        $html = newsfeed_try('https://' . USER_SITE, USERLOGO, $groupname, $email);

        $message = Swift_Message::newInstance()
            ->setSubject('Now you can chat to nearby freeglers!')
            ->setFrom([NOREPLY_ADDR => 'Freegle'])
            ->setReturnPath(NOREPLY_ADDR)
            ->setReplyTo(NOREPLY_ADDR)
            ->setTo($u->getEmailPreferred())
            ->setBody("As well as using Freegle for OFFERs/WANTEDs, now you can chat to nearby freeglers.  It's a great way to ask for advice, recommendations, post lost+founds, or just have a natter.  Try it out at https://" . USER_SITE . "/newsfeed")
            ->addPart($html, 'text/html');

        $transport->send($message);
    } catch (Exception $e) {}
}

if (count($opts) < 1) {
    echo "Usage: hhvm user_try_newsfeed.php (-i <user ID> or -g <group ID>)\n";
} else {
    $id = presdef('i', $opts, NULL);
    $gid = presdef('g', $opts, NULL);

    $idq = $id ? " AND userid = $id " : "";
    $membs = $dbhr->preQuery("SELECT DISTINCT userid FROM memberships WHERE groupid = ? AND collection = ? $idq;", [
        $gid,
        MembershipCollection::APPROVED
    ]);

    $g = new Group($dbhr, $dbhm, $gid);
    $groupname = $g->getPublic()['namedisplay'];

    foreach ($membs as $memb) {
        $u = new User($dbhr, $dbhm, $memb['userid']);

        if ($u->sendOurMails($g, TRUE, TRUE)) {
            sendOne($dbhr, $dbhm, $memb['userid'], $groupname);
        } else {
            error_log($u->getEmailPreferred() . "...skip");
        }
    }
}
