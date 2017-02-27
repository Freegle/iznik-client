<?php
function address() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

    if ($myid) {
        $id = intval(presdef('id', $_REQUEST, NULL));
        $postcodeid = intval(presdef('postcodeid', $_REQUEST, NULL));
        $a = new Address($dbhr, $dbhm, $id);
        $p = new PAF($dbhr, $dbhm);
        $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

        switch ($_REQUEST['type']) {
            case 'GET': {
                if ($id) {
                    $ret = ['ret' => 3, 'status' => 'Access denied'];
                    if ($a->getPrivate('userid') == $myid) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'address' => $a->getPublic()
                        ];
                    }
                } else if ($postcodeid) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'addresses' => []
                    ];

                    $addresses = $p->listForPostcodeId($postcodeid);

                    foreach ($addresses as $address) {
                        $ret['addresses'][] = [
                            'id' => $address,
                            'singleline' => $p->getSingleLine($address)
                        ];
                    }
                } else {
                    # List all for this user.
                    $ret = [
                        'status' => 'Success',
                        'ret' => 0,
                        'addresses' => $a->listForUser($me->getId())
                    ];
                }
                break;
            }

            case 'PUT':
                $id = $a->create($me->getId(),
                    presdef('line1', $_REQUEST, NULL),
                    presdef('line2', $_REQUEST, NULL),
                    presdef('line3', $_REQUEST, NULL),
                    presdef('line4', $_REQUEST, NULL),
                    presdef('town', $_REQUEST, NULL),
                    presdef('county', $_REQUEST, NULL),
                    presdef('postcodeid', $_REQUEST, NULL),
                    presdef('instructions', $_REQUEST, NULL));

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'id' => $id
                ];
                break;

            case 'PATCH': {
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($a->getPrivate('userid') == $myid) {
                    $a->setAttributes($_REQUEST);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;
            }

            case 'DELETE': {
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($a->getPrivate('userid') == $myid) {
                    $a->delete();

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
