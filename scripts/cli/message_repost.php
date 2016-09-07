<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$opts = getopt('i:');

if (count($opts) < 1) {
    echo "Usage: hhvm message_repost.php -i <id of message>\n";
} else {
    $id = $opts['i'];
    $m = new Message($dbhr, $dbhm, $id);

    $groupid = $m->getPublic()['groups'][0]['groupid'];

    if ($groupid) {
        $u = new User($dbhr, $dbhm, $m->getFromuser());
        $m->setPrivate('textbody', $m->stripGumf());
        $m->submit($u, $m->getFromaddr(), $groupid);
        error_log("Reposted");
    }
}
