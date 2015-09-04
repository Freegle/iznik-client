<?php
function session_get() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    if ($me) {
        $ret = array('ret' => 0, 'status' => 'Success', 'me' => array());
    } else {
        $ret = array('ret' => 1, 'status' => 'Not logged in');
    }

    return($ret);
}
