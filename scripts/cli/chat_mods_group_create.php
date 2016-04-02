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

if (count($opts) < 1) {
    echo "Usage: hhvm " .basename(__FILE__) . " -g <id>\n";
} else {
    $gid = $opts['g'];
    $g = new Group($dbhr, $dbhm, $gid);
    echo("Group " . $g->getPrivate('nameshort') . "\n");

    # Create a chat room if one doesn't exist.  The names have to be lower case otherwise subsequent commands
    # fail.
    $gname = strtolower($g->getPrivate('nameshort')) . "_mods";
    myExec("/opt/ejabberd/bin/ejabberdctl create_room $gname conference.iznik localhost");
    myExec("/opt/ejabberd/bin/ejabberdctl change_room_option $gname conference.iznik members_only true");

    if ($g && $g->getId()) {
        $mods = $g->getMods();

        foreach ($mods as $mod) {
            echo "...mod #$mod ";
            $u = new User($dbhr, $dbhm, $mod);
            $atts = $u->getPublic();
            $jid = $u->getJid();
            echo $atts['displayname'] . "\n";
            myExec("/opt/ejabberd/bin/ejabberdctl set_room_affiliation $gname conference.iznik $jid member");
            $dname = str_replace(' ', '\\20', $u->getName());
            myExec("/opt/ejabberd/bin/ejabberdctl set_vcard $jid localhost FN $dname");
        }
    }
} 
