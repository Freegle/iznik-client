<?php
function donations() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $d = new Donations($dbhr, $dbhm);
            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'donations' => $d->get()
            ];

            break;
        }
    }

    return($ret);
}
