<?php
function supporters() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $s = new Supporters($dbhr, $dbhm);

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'supporters' => $s->get()
            ];

            break;
        }
    }

    return($ret);
}
