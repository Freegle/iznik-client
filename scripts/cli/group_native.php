<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('n:');

if (count($opts) < 1) {
    echo "Usage: hhvm group_native.php -n <shortname of group>\n";
} else {
    $name = $opts['n'];
    $g = Group::get($dbhr, $dbhm);
    $id = $g->findByShortName($name);

    if ($id) {
        $g = Group::get($dbhr, $dbhm, $id);
        $g->setNativeRoles();
        $g->setNativeRoles();

        #  Notify TrashNothing so that it can also do that, and talk to us rather than Yahoo.
        $url = "https://trashnothing.com/modtools/api/switch-to-freegle-direct?key=" . TNKEY . "&group_id=" . $g->getPrivate('nameshort') . "&moderator_email=" . $me->getEmailPreferred();
        $rsp = file_get_contents($url);
        error_log("Move to FD on TN " . var_export($rsp, TRUE));
    }
}
