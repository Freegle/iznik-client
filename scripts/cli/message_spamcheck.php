<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$opts = getopt('i:');

if (count($opts) < 1) {
    echo "Usage: hhvm message_spamcheck.php -i <id of message>\n";
} else {
    $id = $opts['i'];
    $m = new Message($dbhr, $dbhm, $id);
    $s = new Spam($dbhr, $dbhm);
    $ret = $s->checkMessage($m);
    var_dump($ret);
}
