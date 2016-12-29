<?php
function poll() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));

    $p = new Polls($dbhr, $dbhm, $id);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            if ($id) {
                # We're not bothered about privacy of polls.
                $ret = [
                    'ret' => 2,
                    'status' => 'Invalid id'
                ];

                if ($p->getId() == $id) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'poll' => $p->getPublic()
                    ];
                }
            } else {
                # Get one for this user.
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($me) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success - no polls'
                    ];

                    $id = $p->getForUser($me->getId());

                    if ($id) {
                        $p = new Polls($dbhr, $dbhm, $id);
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'poll' => $p->getPublic()
                        ];
                    }
                }
            }
            break;
        }

        case 'POST': {
            $response = presdef('response', $_REQUEST, NULL);
            $shown = presdef('shown', $_REQUEST, NULL);

            if ($shown) {
                $p->shown($me->getId());
            } else {
                $p->response($me->getId(), json_encode($response));
            }
            $ret = [
                'ret' => 0,
                'status' => 'Success'
            ];
            break;
        }
    }

    return($ret);
}
