<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$sql = "SELECT logs.* FROM logs INNER JOIN users ON logs.user = users.id AND users.systemrole IN ('Moderator', 'Support', 'Admin') WHERE type = 'User' and subtype = 'Split' ;";
$logs = $dbhr->preQuery($sql);

$u = User::get($dbhr, $dbhm);

$count = 0;

foreach ($logs as $log) {
    if (preg_match('/Split (.*), YID (.*), YUID (.*)/', $log['text'], $match)) {
        $email = $match[1];
        $email = $email && strlen($email) > 0 ? $email : NULL;
        $yaid = $match[2];
        $yaid = $yaid && strlen($yaid) > 0 ? $yaid : NULL;
        $yuid = $match[3];
        $yuid = $yuid && strlen($yuid) > 0 ? $yuid : NULL;

        $eid = $u->findByEmail($email);
        $yid = $u->findByYahooId($yaid);
        $yuidi = $u->findByYahooUserId($yuid);

        error_log("{$log['user']} $email => $eid, $yaid => $yid, $yuid => $yuidi");

        $id = $eid ? $eid : ($yid ? $yid : ($yuidi ? $yuidi : NULL));

        if ($eid && $eid != $id) {
            error_log("Merge $eid => $id");
            $u->merge($id, $eid, "Repair erroneous splits");
            $count++;
        }
        if ($yid && $yid != $id) {
            error_log("Merge $yid => $id");
            $u->merge($id, $yid, "Repair erroneous splits");
            $count++;
        }
        if ($yuidi && $yuidi != $id) {
            error_log("Merge $yuidi => $id");
            $u->merge($id, $yuidi, "Repair erroneous splits");
            $count++;
        }

    }
}


error_log("Merged $count");