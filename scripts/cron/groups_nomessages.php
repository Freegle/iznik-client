<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$none = '';
$count = 0;
$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND onhere = 1 AND nameshort NOT LIKE '%playground%' ORDER BY LOWER(nameshort);");
foreach ($groups as $group) {
    $sql = "SELECT DATEDIFF(NOW(), MAX(arrival)) AS latest FROM messages_groups WHERE groupid = ?;";
    $latest = $dbhr->preQuery($sql, [ $group['id'] ]);

    if ($latest[0]['latest'] > 7) {
        $none .= $group['nameshort'] . " last message {$latest[0]['latest']} days ago\r\n";
        $count++;
    }
}

if ($count) {
    list ($transport, $mailer) = getMailer();
    $message = Swift_Message::newInstance()
        ->setSubject("WARNING: $count groups not receiving messages on Iznik")
        ->setFrom(GEEKS_ADDR)
        ->setTo('log@ehibbert.org.uk')
        #->setCc(GEEKS_ADDR)
        ->setDate(time())
        ->setBody(
            "The following groups are on Iznik but haven't received any messages recently.  Either they are inactive or don't have modtools@modtools.org on there on individual emails.\r\n\r\n$none"
        );
    $mailer->send($message);
}
