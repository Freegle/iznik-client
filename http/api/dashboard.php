<?php
function dashboard() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $systemwide = array_key_exists('systemwide', $_REQUEST) ? filter_var($_REQUEST['systemwide'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $allgroups = array_key_exists('allgroups', $_REQUEST) ? filter_var($_REQUEST['allgroups'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $groupid = presdef('group', $_REQUEST, NULL);
    $groupid = $groupid ? intval($groupid) : NULL;
    $type = presdef('grouptype', $_REQUEST, NULL);
    $start = presdef('start', $_REQUEST, '30 days ago');

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            # Check if we're logged in
            if ($me || $groupid) {
                $ret = array('ret' => 0, 'status' => 'Success');
                $d = new Dashboard($dbhr, $dbhm, $me);
                $ret['dashboard'] = $d->get($systemwide, $allgroups, $groupid, $type, $start);
            } else {
                $ret = array('ret' => 1, 'status' => 'Not logged in');
            }

            break;
        }
    }

    return($ret);
}
