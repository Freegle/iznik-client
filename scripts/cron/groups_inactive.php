<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$noton = '';
$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND onhere = 0 AND onyahoo = 1 AND nameshort NOT LIKE '%playground%' ORDER BY LOWER(nameshort);");
foreach ($groups as $group) {
    $noton .= $group['nameshort'] . "\r\n";
}

$notactive = '';
$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND onhere = 1 AND DATEDIFF(NOW(), lastyahoomembersync) > 7 AND nameshort NOT LIKE '%playground%' ORDER BY LOWER(nameshort);");
foreach ($groups as $group) {
    $last = $group['lastyahoomembersync'] ? date("Y-m-d", strtotime($group['lastyahoomembersync'])) : 'never';
    $notactive .= $group['nameshort'] . " last syncd $last\r\n";
}

list ($transport, $mailer) = getMailer();
$message = Swift_Message::newInstance()
    ->setSubject('Summary of groups not active on ModTools')
    ->setFrom(GEEKS_ADDR)
    ->setTo(MENTORS_ADDR)
    ->setCc(GEEKS_ADDR)
    ->setDate(time())
    ->setBody(
        "The following groups are using Freegle Direct but have not been moderated using the plugin in the last 7 days:\r\n\r\n$notactive\r\n\r\nThe following groups are not on Freegle Direct yet:\r\n\r\n$noton"
    );
$mailer->send($message);
