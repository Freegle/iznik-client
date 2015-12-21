<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$at = 0;

#$dbhm->preExec("UPDATE messages SET locationid = NULL, lat = NULL, lng = NULL WHERE lat IS NOT NULL;");

do {
    #$sql = "SELECT messages.id, groups.id AS groupid, groups.nameshort FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.deleted = 0 AND messages.locationid IS NULL INNER JOIN groups ON groups.id = messages_groups.groupid AND groups.type = 'Freegle' AND subject IS NOT NULL AND groupid = 21354 ORDER BY id DESC;";
    $sql = "SELECT messages.id, groups.id AS groupid, groups.nameshort FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.deleted = 0 AND messages.locationid IS NULL INNER JOIN groups ON groups.id = messages_groups.groupid AND groups.type = 'Freegle' AND subject IS NOT NULL ORDER BY id DESC;";

    $msgs = $dbhr->query($sql);
    $found = false;

    foreach ($msgs as $msg) {
        $found = true;
        $i = new Message($dbhr, $dbhm, $msg['id']);
        $atts = $i->getPublic();
        if (pres('lat', $atts)) {
            preg_match("/.*\((.*)\)/", $atts['suggestedsubject'], $matches);

            error_log("  {$atts['id']} {$atts['subject']} got {$matches[1]} - {$atts['lat']}, {$atts['lng']} ID {$atts['locationid']} on {$msg['groupid']} {$msg['nameshort']}");
            $sql = "UPDATE messages SET lat = ?, lng = ?, locationid = ? WHERE id = ?;";
            $dbhm->preExec($sql,
                [
                    $atts['lat'],
                    $atts['lng'],
                    $atts['locationid'],
                    $msg['id']
                ]);
        } else {
            error_log("{$atts['id']} {$atts['subject']} not mappable on group {$msg['groupid']} {$msg['nameshort']}");
        }
    }

    $at += 100;

    $found = FALSE;
} while ($found);