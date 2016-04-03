<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('g:');

function myExec($str) {
    echo "$str\n";
    exec($str);
}

$gid = count($opts) > 0 ? $opts['g'] : NULL;

$sql = "SELECT id FROM groups " . ($gid ? " WHERE id = $gid" : " WHERE nameshort LIKE 'Ribble%' OR nameshort LIKE 'Playgr%' OR nameshort LIKE 'Edinburgh%'") . " ORDER BY nameshort ASC;";
$groups = $dbhr->query($sql);

foreach ($groups as $group) {
    $g = new Group($dbhr, $dbhm, $group['id']);
    echo("Group #{$group['id']} " . $g->getPrivate('nameshort') . "\n");

    # Create a chat room if one doesn't exist.  The names have to be lower case otherwise subsequent commands
    # fail.
    $gname = strtolower($g->getPrivate('nameshort')) . "_mods";
    #myExec("/opt/ejabberd/bin/ejabberdctl destroy_room $gname conference.iznik localhost");
    myExec("/opt/ejabberd/bin/ejabberdctl create_room $gname conference.iznik localhost");
    myExec("/opt/ejabberd/bin/ejabberdctl change_room_option $gname conference.iznik members_only true");

    if ($g && $g->getId()) {
        $mods = $g->getMods();

        foreach ($mods as $mod) {
            $u = new User($dbhr, $dbhm, $mod);
            $mysettings = $u->getGroupSettings($group['id']);
            $emails = $u->getEmails();
            $system = FALSE;
            foreach ($emails as $email) {
                if (strpos($email['email'], 'modtools.org') !== FALSE ||
                    strpos($email['email'], 'ilovefreegle.org') !== FALSE ||
                    strpos($email['email'], 'republisher') !== FALSE) {
                    $system = FALSE;
                }
            }

            echo($u->getName() . " " . var_export($mysettings, TRUE) . "\n");
            if (!$system && (!array_key_exists('showmessages', $mysettings) || $mysettings['showmessages'])) {
                # We add them as a chat member if they are showing messages, which is a proxy for them caring
                # about the group.
                $atts = $u->getPublic();
                $jid = $u->getJid();
                myExec("/opt/ejabberd/bin/ejabberdctl set_room_affiliation $gname conference.iznik $jid member");
                $dname = str_replace(' ', '\\20', $u->getName());
                myExec("/opt/ejabberd/bin/ejabberdctl set_vcard $jid localhost FN $dname");
                echo "...added mod #$mod {$atts['displayname']}\n";
            }
        }
    }
}
