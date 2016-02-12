<?php
function bulkop() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);

    $id = presdef('id', $_REQUEST, NULL);
    $configid = presdef('configid', $_REQUEST, NULL);
    $b = new BulkOp($dbhr, $dbhm, $id);

    if ($id && $b->getId() || $_REQUEST['type'] == 'POST') {
        switch ($_REQUEST['type']) {
            case 'GET': {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'bulkop' => $b->getPublic()
                ];

                break;
            }

            case 'POST': {
                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else {
                    $name = presdef('title', $_REQUEST, NULL);
                    $systemrole = $me->getPublic()['systemrole'];

                    if (!$name) {
                        $ret = [
                            'ret' => 3,
                            'status' => 'Must supply title'
                        ];
                    } else if (!$configid) {
                        $ret = [
                            'ret' => 3,
                            'status' => 'Must supply configid'
                        ];
                    } else if ($systemrole != User::SYSTEMROLE_MODERATOR &&
                        $systemrole != User::SYSTEMROLE_SUPPORT &&
                        $systemrole != User::SYSTEMROLE_ADMIN) {
                        $ret = [
                            'ret' => 4,
                            'status' => 'Don\t have rights to create configs'
                        ];
                    } else {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'id' => $b->create($name, $configid)
                        ];
                    }
                }

                break;
            }

            case 'PUT':
            case 'PATCH': {
                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else if (!$b->canSee()) {
                    $ret = [
                        'ret' => 4,
                        'status' => 'Don\t have rights to see config'
                    ];
                } else {
                    $b->setAttributes($_REQUEST);

                    $groupid = presdef('groupid', $_REQUEST, NULL);

                    foreach (['runstarted', 'runfinished'] as $att) {
                        $val = presdef($att, $_REQUEST, NULL);
                        $mysqltime = date("Y-m-d H:i:s", strtotime($val));

                        if ($val) {
                            $b->setRunAtt($groupid, 'runstarted', $mysqltime);
                        }
                    }

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;
            }

            case 'DELETE': {
                # We can only delete this standard message if we have access to the modconfig which owns it.
                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else if (!$b->canModify()) {
                    $ret = [
                        'ret' => 4,
                        'status' => 'Don\t have rights to modify config'
                    ];
                } else {
                    $b->delete();
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
            }
        }
    } else {
        $ret = [
            'ret' => 2,
            'status' => 'Invalid bulkop id'
        ];
    }

    return($ret);
}
