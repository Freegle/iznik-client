<?php
function dashboard() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $systemwide = array_key_exists('systemwide', $_REQUEST) ? filter_var($_REQUEST['systemwide'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $allgroups = array_key_exists('allgroups', $_REQUEST) ? filter_var($_REQUEST['allgroups'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $groupid = presdef('group', $_REQUEST, NULL);
    $type = presdef('grouptype', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET': {
            # Check if we're logged in
            if ($me) {
                $ret = array('ret' => 0, 'status' => 'Success');
                $d = new Dashboard($dbhr, $dbhm, $me);
                $ret['dashboard'] = $d->get($systemwide, $allgroups, $groupid, $type);
            } else {
                $ret = array('ret' => 1, 'status' => 'Not logged in');
            }

            break;
        }
    }

    return($ret);
}
