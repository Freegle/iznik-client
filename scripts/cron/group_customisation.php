<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND publish = 1 AND onhere = 1 ORDER BY RAND();");

foreach ($groups as $group) {
    error_log("Check {$group['id']} {$group['nameshort']}");
    $g = new Group($dbhr, $dbhm, $group['id']);

    $custatts = [
        'profile' => "* Profile picture - this helps people recognise your group.",
        'tagline' => "* Tagline - something short and snappy, ideally with a local reference.",
        'welcomemail' => "* Welcome Mail - this is sent out to people when they join - your chance to welcome them, and let them know anything they need to (e.g. whether pets are allowed).",
        'description' => "* Description - this is longer than the tagline, and is visible on the site - often containing similar information to the welcome mail."
    ];
    $missing = '';
    foreach ($custatts as $att => $desc) {
        if (!pres($att, $group)) {
            error_log("...missing $att");
            $missing .= "$desc\n";
        }
    }

    if (strlen($missing) > 0) {
        list ($transport, $mailer) = getMailer();

        $message = Swift_Message::newInstance()
            ->setSubject("Reminder - ways to make {$group['nameshort']} more welcoming")
            ->setFrom([NOREPLY_ADDR => 'Freegle'])
            ->setReturnPath(NOREPLY_ADDR)
            ->setTo($g->getModsEmail())
            ->setBody("Just to remind you, you can make your Freegle group look more local and friendly for your members by customising how it appears on Freegle Direct.\n\nHere are some things you could add:\n\n$missing\n\nYou can change these from ModTools (https://modtools.org), in Settings->Group Settings->Group Appearance.  ModTools helps you run your group, and is free to use for all Freegle groups.\n\nIf you need help with this, then please contact " . MENTORS_ADDR . "\n\nP.S. This is an automated message sent once a month.  We send it regularly because sometimes the moderators on a group change, and they might not know about this stuff.");

        $mailer->send($message);
        error_log("...mailed");
    }

    # Sleep as Yahoo is picky.
    sleep(300);
}