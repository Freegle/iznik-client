<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$threshold = 31;
$abandoned = '';

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND publish = 1 AND nameshort NOT LIKE '%playground%' ORDER BY LOWER(nameshort);");
foreach ($groups as $group) {
    # See when they last had a message.
    $messages = $dbhr->preQuery("SELECT MAX(arrival) AS max FROM messages_groups WHERE groupid = ?;", [
        $group['id']
    ]);

    if (strtotime($messages[0]['max']) >  time() - $threshold * 24 * 60 * 60) {
        # We have had a recent message. See when they were last moderated.
        $sql = "SELECT MAX(arrival) AS max FROM  `messages_groups` WHERE groupid = ? AND approvedby IS NOT NULL;";
        $info = $dbhr->preQuery($sql, [ $group['id'] ]);
        $info = $info[0];

        # Now see if we've autoapproved any messages since then.
        $logs = $dbhr->preQuery("SELECT MAX(timestamp) AS max FROM logs INNER JOIN messages_groups ON logs.msgid = messages_groups.msgid WHERE messages_groups.groupid = ? AND logs.type = 'Message' AND logs.subtype = 'Autoapproved' AND timestamp > ?;", [
            $group['id'],
            $info['max']
        ]);

        if ($logs[0]['max'] > 0) {
            $abandoned .= $group['nameshort'] . " last message auto-approved at {$logs[0]['max']}\r\n";
        }
    }
}

if ($abandoned != '') {
    list ($transport, $mailer) = getMailer();
    $message = Swift_Message::newInstance()
        ->setSubject('Summary of possibly abandoned groups')
        ->setFrom(GEEKS_ADDR)
#    ->setTo([ MENTORS_ADDR, SUPPORT_ADDR ])
        ->setTo('log@ehibbert.org.uk')
        #->setCc(GEEKS_ADDR)
        ->setDate(time())
        ->setBody(
            "The following groups have had messages within the last $threshold days, and have auto-approved a message since the last moderated message.  This means that they are being moderated irregularly or have been abandoned:\r\n\r\n$abandoned"
        );
    $mailer->send($message);
}
