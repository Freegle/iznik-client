<?php
function authority() {
    global $dbhr, $dbhm;

    $id = intval(presdef('id', $_REQUEST, NULL));
    $search = presdef('search', $_REQUEST, NULL);
    $a = new Authority($dbhr, $dbhm, $id);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            if ($id) {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'authority' => $a->getPublic()
                ];
            } else if ($search) {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'authorities' => $a->search($search)
                ];
            }
            break;
        }
    }

    return($ret);
}
