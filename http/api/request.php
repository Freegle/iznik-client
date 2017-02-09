<?php
function request() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

    if ($myid) {
        $id = intval(presdef('id', $_REQUEST, NULL));
        $r = new Request($dbhr, $dbhm, $id);
        $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

        switch ($_REQUEST['type']) {
            case 'GET': {
                if ($id) {
                    $ret = [ 'ret' => 3, 'status' => 'Access denied' ];

                    # We can see those if they are our own, or we're a mod for that user.
                    if ($r->getPrivate('userid') == $myid || $me->moderatorForUser($r->getPrivate('userid')) || $me->isAdmin()) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'request' => $r->getPublic()
                        ];
                    }
                } else {
                    # List all for this user.
                    $ret = [
                        'status' => 'Success',
                        'ret' => 0,
                        'requests' => $r->listForUser($me->getId())
                    ];
                }
                break;
            }

            case 'PUT':
                $id = $r->create($me->getId(),
                    presdef('reqtype', $_REQUEST, NULL),
                    presdef('addressid', $_REQUEST, NULL));

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'id' => $id
                ];
                break;

            case 'PATCH': {
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($r->getPrivate('userid') == $myid || $me->moderatorForUser($r->getPrivate('userid')) || $me->isAdmin()) {
                    $r->setAttributes($_REQUEST);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;
            }

            case 'DELETE': {
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($r->getPrivate('userid') == $myid) {
                    $r->delete();

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;
            }
        }
    }

    return($ret);
}
