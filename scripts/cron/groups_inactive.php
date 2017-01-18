<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$noton = '';
$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND onhere = 0 AND onyahoo = 1 AND publish = 1 AND nameshort NOT LIKE '%playground%' ORDER BY LOWER(nameshort);");
foreach ($groups as $group) {
    $noton .= $group['nameshort'] . "\r\n";
}

# We decide if they're active by whether they've had a Yahoo member sync or approved a message.
$notactive = '';
$groups = $dbhr->preQuery("SELECT DATEDIFF(NOW(), lastyahoomembersync) AS lastsync, id, nameshort FROM groups WHERE type = 'Freegle' AND onhere = 1 AND publish = 1 AND nameshort NOT LIKE '%playground%' ORDER BY LOWER(nameshort);");
foreach ($groups as $group) {
    $acts = $dbhr->preQuery("SELECT DATEDIFF(NOW(), MAX(timestamp)) AS daysago FROM logs WHERE groupid = ? AND logs.type = 'Message' AND subtype = 'Approved';", [ $group['id'] ]);
    foreach ($acts as $act) {
        if ($group['lastsync'] > 7 && ($act['daysago'] === NULL || $act['daysago'] > 7)) {
            $lastact = min($group['lastsync'] != NULL ? $group['lastsync'] : PHP_INT_MAX, $act['daysago'] != NULL ? $act['daysago'] : PHP_INT_MAX);
            $last = $act['daysago'] ? "{$act['daysago']} days ago" : 'never';
            $notactive .= $group['nameshort'] . " last active $last\r\n";
        }
    }
}

list ($transport, $mailer) = getMailer();
$message = Swift_Message::newInstance()
    ->setSubject('Summary of groups not active on ModTools')
    ->setFrom(GEEKS_ADDR)
    ->setTo([ MENTORS_ADDR, SUPPORT_ADDR ])
    ->setCc(GEEKS_ADDR)
    ->setDate(time())
    ->setBody(
        "The following groups are using Freegle Direct but have not been moderated on ModTools in the last 7 days (sync'd memberships with Yahoo or approved a message):\r\n\r\n$notactive\r\n\r\nThe following groups are not on Freegle Direct yet:\r\n\r\n$noton"
    );
$mailer->send($message);
