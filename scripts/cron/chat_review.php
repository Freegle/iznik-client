<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$u = new User($dbhr, $dbhm);
$uid = $u->findByEmail(MODERATOR_EMAIL);

# Look first for chat which has been pending review for so long that they are no longer worth passing on.
$mysqltime = date("Y-m-d", strtotime("Midnight 7 days ago"));
$msgs = $dbhm->preExec("UPDATE chat_messages SET reviewedby = ?, reviewrejected = 1 WHERE date < ? AND reviewrequired = 1 AND reviewedby IS NULL;", [
    $uid,
    $mysqltime
]);

error_log($dbhm->rowsAffected() . " messages stuck in review");

# Now look for chat which has been pending review for 48 hours.  Randomise an order so that if only the first few
# get through then different groups will get notified each time.
$mysqltime = date("Y-m-d", strtotime("48 hours ago"));
$sql = "SELECT DISTINCT(memberships.groupid), COUNT(*) AS count FROM chat_messages INNER JOIN chat_rooms ON chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_rooms.user1 = chat_messages.userid THEN chat_rooms.user2 ELSE chat_rooms.user1 END) WHERE chat_messages.date < ? AND reviewrequired = 1 AND reviewedby IS NULL AND reviewrejected = 0 GROUP BY groupid ORDER BY RAND();";
#error_log("SELECT DISTINCT(memberships.groupid), COUNT(*) AS count FROM chat_messages INNER JOIN chat_rooms ON chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_rooms.user1 = chat_messages.userid THEN chat_rooms.user2 ELSE chat_rooms.user1 END) WHERE chat_messages.date < '$mysqltime' AND reviewrequired = 1 AND reviewedby IS NULL AND reviewrejected = 0 GROUP BY groupid ORDER BY RAND();");
$groups = $dbhr->preQuery($sql, [
    $mysqltime
]);

$mentors = '';

$count = 0;
foreach ($groups as $group) {
    $msgs = $dbhr->preQuery("SELECT chat_messages.id FROM chat_messages INNER JOIN chat_rooms ON chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_rooms.user1 = chat_messages.userid THEN chat_rooms.user2 ELSE chat_rooms.user1 END) WHERE chat_messages.date < '$mysqltime' AND reviewrequired = 1 AND reviewedby IS NULL AND reviewrejected = 0 AND memberships.groupid = ?;", [
        $group['groupid']
    ]);

    $str = '';
    foreach ($msgs as $msg) {
        $str .= "{$msg['id']} ";
    }

    $g = new Group($dbhr, $dbhm, $group['groupid']);
    error_log($g->getPrivate('nameshort') . " messages $str");

    if ($g->getPrivate('type') == Group::GROUP_FREEGLE &&
        $g->getPrivate('publish') == 1) {
        $count += $group['count'];
        error_log($g->getPrivate('nameshort') . " count " . $group['count']);
        $mentors .= $g->getPrivate('nameshort') . " count " . $group['count'] . "\r\n";

        list ($transport, $mailer) = getMailer();
        $message = Swift_Message::newInstance()
            ->setSubject($group['count'] . " message" . ($group['count'] == 1 ? '' : 's') . " between members waiting for your review on " . $g->getPrivate('nameshort'))
            ->setFrom([SUPPORT_ADDR => 'Freegle'])
            ->setTo($g->getModsEmail())
            ->setDate(time())
            ->setBody(
                "Dear " . $g->getPrivate('nameshort') . " Volunteers,\r\n\r\nMessages between members are scanned to spot spam.  For some of these, we need you to review them to check whether they are really spam or not.\r\n\r\nYou currently have some messages which have been waiting for 48 hours for review.  Some of these may be real messages which members won't have received yet, so they may be wondering what's going on.\r\n\r\nPlease can you review these at https://" . MOD_SITE . "/modtools/conversations/spam ?  They will automatically be deleted after 7 days.\r\n\r\nThanks.\r\n\r\nP.S. This is an automated mail sent once a day.  If you need help using ModTools, please ask the Mentor folk at " . MENTORS_ADDR . "."
            );
        $mailer->send($message);

        # Delay to reduce chance of Yahoo blocking.
        sleep(600);
    }
}

if ($count > 0) {
    list ($transport, $mailer) = getMailer();
    $message = Swift_Message::newInstance()
        ->setSubject("Summary of chat messages waiting for review")
        ->setFrom([SUPPORT_ADDR => 'Freegle'])
        ->setTo(MENTORS_ADDR)
        ->setCc('log@ehibbert.org.uk')
        ->setDate(time())
        ->setBody(
            "Here's a list of groups which have chat messages pending review for more than 48 hours:\r\n\r\n$mentors"
        );
    $mailer->send($message);
}

error_log("\r\n\r\nTotal $count");