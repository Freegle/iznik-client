<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$msgs = $dbhr->preQuery("SELECT DISTINCT messages.id, messages.fromuser, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages_groups.msgid = messages.id LEFT OUTER JOIN messages_outcomes ON messages_outcomes.msgid = messages.id WHERE sourceheader = 'Platform' AND messages_outcomes.msgid IS NULL ORDER BY groupid;");

foreach ($msgs as $msg) {
    $m = new Message($dbhr, $dbhm, $msg['id']);
    #error_log($m->getPrivate('date') . " " . $m->getSubject());

    $membs = $dbhr->preQuery("SELECT * FROM memberships WHERE userid = ? AND groupid = ?;", [
        $msg['fromuser'],
        $msg['groupid']
    ]);

    foreach ($membs as $memb) {
        #error_log("Membership {$memb['collection']}");
        $full = $m->getPrivate('message');
        if (substr($m->getSubject(), 0, 1) != '[' && preg_match('/^Date: (.*)$/m', $full, $matches)) {
            $date = $matches[1];
            $submittedat = strtotime($date);

            #error_log("Submitted on $date");

            $logs = $dbhr->preQuery("SELECT * FROM logs WHERE user = ? AND groupid = ? AND type = 'User' AND subtype = 'Approved';", [
                $msg['fromuser'],
                $msg['groupid']
            ]);

            $premature = FALSE;
            foreach ($logs as $log) {
                #error_log("Joined Yahoo at {$log['timestamp']}");
                $joinedat = strtotime($log['timestamp']);
                if ($joinedat > $submittedat) {
                    $premature = TRUE;
                }
            }

            if ($premature) {
                $g = new Group($dbhr, $dbhm, $msg['groupid']);
                error_log($g->getPrivate('nameshort') . " #{$msg['id']} " . $m->getPrivate('date') . " " . $m->getFromaddr() . " " . $m->getSubject());
            }
        }
    }
    #$u = new User($dbhr, $dbhm, $msg['fromuser']);
}
