<?php
function abtest() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));

    $p = new Polls($dbhr, $dbhm, $id);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'POST': {
            $uid = presdef('uid', $_REQUEST, NULL);
            $variant = presdef('variant', $_REQUEST, NULL);
            $shown = array_key_exists('shown', $_REQUEST) ? filter_var($_REQUEST['shown'], FILTER_VALIDATE_BOOLEAN) : NULL;
            $action = array_key_exists('action', $_REQUEST) ? filter_var($_REQUEST['action'], FILTER_VALIDATE_BOOLEAN) : NULL;

            if ($uid && $variant) {
                if ($shown !== NULL) {
                    $sql = "INSERT INTO abtest (uid, variant, shown) VALUES (" . $dbhm->quote($uid) . ", " . $dbhm->quote($variant) . ", 1) ON DUPLICATE KEY UPDATE shown = shown + 1;";
                    $dbhm->background($sql);
                }

                if ($action !== NULL) {
                    $sql = "INSERT INTO abtest (uid, variant, action) VALUES (" . $dbhm->quote($uid) . ", " . $dbhm->quote($variant) . ", 1) ON DUPLICATE KEY UPDATE action = action + 1;";
                    $dbhm->background($sql);
                }
            }

            $ret = [
                'ret' => 0,
                'status' => 'Success'
            ];
            break;
        }
    }

    return($ret);
}
