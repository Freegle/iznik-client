<?php
function groups() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);
    $g = Group::get($dbhr, $dbhm);

    switch ($_REQUEST['type']) {
        case 'GET': {
            $grouptype = presdef('grouptype', $_REQUEST, NULL);
            $support = array_key_exists('support', $_REQUEST) ? filter_var($_REQUEST['support'], FILTER_VALIDATE_BOOLEAN) : FALSE;

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'groups' => $g->listByType($grouptype, $support)
            ];
        }
    }

    return($ret);
}

