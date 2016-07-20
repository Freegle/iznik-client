<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$lockh = lockScript(basename(__FILE__));

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE `type` = 'Freegle';");
foreach ($groups as $group) {
    $sql = "SELECT messages.*, messages_groups.yahooapprovedid FROM messages INNER JOIN messages_groups ON messages_groups.msgid = messages.id AND messages_groups.groupid = {$group['id']} AND messages_groups.deleted = 0 AND collection = 'Approved' AND DATEDIFF(NOW(), date) < 7 ORDER BY date DESC;";
    #error_log($sql);
    $msgs = $dbhr->preQuery($sql);
    foreach ($msgs as $msg) {
        $fdmsgs = $dbhold->query("SELECT * FROM messages WHERE messageid = " . $dbhold->quote($msg['messageid']) . " OR messageid = " . $dbhold->quote("<{$msg['messageid']}>") . ";");
        $got = FALSE;
        foreach ($fdmsgs as $fdmsg) {
            $got = TRUE;
        }

        if (!$got) {
            error_log("{$group['nameshort']} {$msg['id']} {$msg['subject']} {$msg['messageid']}");
            file_put_contents("/tmp/msg", "List-ID: <{$group['nameshort']}>\nX-Yahoo-Newman-Id: x-m{$msg['yahooapprovedid']}\n" . str_replace('Yahoo-Newman', 'Yahoo-Noman', $msg['message']));
            exec("cd /var/www/direct/scripts; cat /tmp/msg | ./incoming");
        }
    }
}

unlockScript($lockh);