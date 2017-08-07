<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type != 'Freegle' AND publish = 1 AND DATEDIFF(now(), lastyahoomembersync) < 31 AND licenserequired = 1 AND NOW() > licenseduntil ORDER BY nameshort ASC ;");
list ($transport, $mailer) = getMailer();

foreach ($groups as $group) {
    $body = "It looks like you're using ModTools on {$group['nameshort']}, and your license expired on {$group['licenseduntil']}.  ModTools costs $5 per year per group.\r\n\r\nPlease can you buy a new license?  You can do this using the Add License button at the top of Settings->Global Settings.\r\n\r\nIf you no longer wish to use ModTools on this group, please reply and let me know.\r\n\r\nThanks,\r\nEdward";
    error_log($body);
    $message = Swift_Message::newInstance()
        ->setSubject("ModTools - it's probably time for you to get a new license")
        ->setFrom(MODERATOR_EMAIL)
        ->setTo($group['nameshort'] . '-owner@yahoogroups.com')
        ->setReplyTo('edward@ehibbert.org.uk')
        ->setDate(time())
        ->setBody($body);
    $mailer->send($message);
    sleep(300);
}
