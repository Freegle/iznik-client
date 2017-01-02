<?php
function stdmsg() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);

    $id = presdef('id', $_REQUEST, NULL);
    $configid = presdef('configid', $_REQUEST, NULL);
    $s = new StdMessage($dbhr, $dbhm, $id);

    if ($id && $s->getId() || $_REQUEST['type'] == 'POST') {
        switch ($_REQUEST['type']) {
            case 'GET': {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'stdmsg' => $s->getPublic()
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
                            'id' => $s->create($name, $configid)
                        ];

                        # Update the other attributes.
                        $s->setAttributes($_REQUEST);
                    }

                    # Clear cache.
                    $_SESSION['configs'] = NULL;
                }

                break;
            }

            case 'PUT':
            case 'PATCH': {
                $_SESSION['configs'] = NULL;

                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else if (!$s->canModify()) {
                    $ret = [
                        'ret' => 4,
                        'status' => 'Don\t have rights to modify config'
                    ];
                } else {
                    $s->setAttributes($_REQUEST);
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];

                    # Clear cache.
                    $_SESSION['configs'] = NULL;
                }
                break;
            }

            case 'DELETE': {
                # We can only delete this standard message if we have access to the modconfig which owns it.
                $_SESSION['configs'] = NULL;

                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else if (!$s->canModify()) {
                    $ret = [
                        'ret' => 4,
                        'status' => 'Don\t have rights to modify config'
                    ];
                } else {
                    $s->delete();
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];

                    # Clear cache.
                    $_SESSION['configs'] = NULL;
                }
            }
        }
    } else {
        $ret = [
            'ret' => 2,
            'status' => 'Invalid stdmsg id'
        ];
    }

    return($ret);
}
