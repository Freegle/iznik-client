<?php
function dashboard() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        # Check if we're logged in
        if ($me) {
            $ret = array('ret' => 0, 'status' => 'Success');
            $d = new Dashboard($dbhr, $dbhm, $me);
            $ret['dashboard'] = $d->get();
        } else {
            $ret = array('ret' => 1, 'status' => 'Not logged in');
        }
    }

    return($ret);
}
