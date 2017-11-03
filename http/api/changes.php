<?php
function changes() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $since = presdef('since', $_REQUEST, date("Y-m-d H:i:s", strtotime("1 hour ago")));
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = [
                'ret' => 2,
                'status' => 'Invalid parameters'
            ];

            if ($since) {
                $m = new MessageCollection($dbhr, $dbhm);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'changes' => [
                        'messages' => $m->getChanges($since)
                    ]
                ];
            }
            break;
        }
    }

    return($ret);
}
