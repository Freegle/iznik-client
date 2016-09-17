<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Message.php');



$groups = [];

$handle = fopen("/tmp/a.b", "r");
while (($line = fgets($handle)) !== false) {
    if (preg_match('/\[(.*?)\].*/', $line, $matches)) {
        error_log("Time {$matches[1]}");
        $time = strtotime($matches[1]);
        $mysqltime1 = date("Y-m-d H:i:s", $time - 300);
        $mysqltime2 = date("Y-m-d H:i:s", $time + 300);
        $sql = "SELECT * FROM logs WHERE timestamp > '$mysqltime1' AND timestamp < '$mysqltime2' AND `type` = 'Message' AND subtype IN ('Rejected');";
        $logs = $dbhr->preQuery($sql);
        foreach ($logs as $log) {
            if (!array_key_exists($log['groupid'], $groups)) {
                $groups[$log['groupid']] = [];
            }

            $groups[$log['groupid']][] = $log['msgid'];
        }
    }
}

foreach ($groups as $groupid => $msglist) {
    $g = Group::get($dbhr, $dbhm, $groupid);

    $list = "";
    foreach ($msglist as $msgid) {
        $m = new Message($dbhr, $dbhm, $msgid);
        $list .= $g->getPrivate('nameshort') . " " . $m->getFromaddr() . " " . $m->getSubject() . "\r\n";
    }

    $headers = "From: ModTools <edward@ehibbert.org.uk>\r\n";
    mail($g->getModsEmail() . ", log@ehibbert.org.uk", "Possible missing rejections from ModTools", "Your ModConfig is set to BCC to a format ModTools doesn't support.  Because of this in combination with a recent code change, it's possible that when you rejected the following messages, the rejections didn't reach the people who sent them.  You might want to check with them to see if they did, if that's appropriate.\r\n\r\n$list\r\n\r\nPlease also check your ModConfig and ensure that the BCC is a single, valid, email address.\r\n\r\nSorry about the problem,\r\n\r\nEdward", $headers);
    sleep(60);
}

