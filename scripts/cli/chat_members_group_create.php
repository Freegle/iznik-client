<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('g:');

function myExec($str) {
    #echo "$str\n";
    exec($str);
}

if (count($opts) < 1) {
    echo "Usage: hhvm " .basename(__FILE__) . " -g <id>\n";
} else {
    $gid = $opts['g'];
    $g = new Group($dbhr, $dbhm, $gid);

    if ($g->getId()) {
        echo("Group " . $g->getPrivate('nameshort') . "\n");
        $gname = strtolower($g->getPrivate('nameshort'));

        myExec("/opt/ejabberd/bin/ejabberdctl create_room $gname conference.iznik localhost");
        myExec("/opt/ejabberd/bin/ejabberdctl change_room_option $gname conference.iznik members_only true");

        $membs = $g->getMembers(50000);
        $count = 0;
        foreach ($membs as $memb) {
            $u = new User($dbhr, $dbhm, $memb['userid']);
            $atts = $u->getPublic();
            $jid = $u->getJid();
            myExec("/opt/ejabberd/bin/ejabberdctl set_room_affiliation $gname conference.iznik $jid member");
            $dname = str_replace(' ', '\\20', $u->getName());
            myExec("/opt/ejabberd/bin/ejabberdctl set_vcard $jid localhost FN $dname");

            $count++;

            if ($count % 100 == 0) {
                error_log("...$count / " . count($membs));
            }
        }
    }
}
