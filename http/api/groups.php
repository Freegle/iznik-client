<?php
function groups() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);
    $g = new Group($dbhr, $dbhm);

    switch ($_REQUEST['type']) {
        case 'GET': {
            $grouptype = presdef('grouptype', $_REQUEST, NULL);

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'groups' => $g->listByType($grouptype)
            ];
        }
    }

    return($ret);
}

