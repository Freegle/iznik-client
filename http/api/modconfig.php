<?php
function modconfig() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);

    # The id parameter can be an ID or a nameshort.
    $id = presdef('id', $_REQUEST, NULL);
    $c = new ModConfig($dbhr, $dbhm, $id);

    if ($id && $c->getId() || $_REQUEST['type'] == 'POST') {
        switch ($_REQUEST['type']) {
            case 'GET': {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'config' => $c->getPublic()
                ];

                break;
            }

            case 'POST': {
                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else {
                    $name = presdef('name', $_REQUEST, NULL);
                    $systemrole = $me->getPublic()['systemrole'];

                    if (!$name) {
                        $ret = [
                            'ret' => 3,
                            'status' => 'Must supply name'
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
                            'id' => $c->create($name, $me->getId())
                        ];
                    }
                }

                break;
            }

            case 'PATCH': {
                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else {
                    $systemrole = $me->getPublic()['systemrole'];
                    error_log("Systemrole is $systemrole");

                    if ($systemrole != User::SYSTEMROLE_MODERATOR &&
                        $systemrole != User::SYSTEMROLE_SUPPORT &&
                        $systemrole != User::SYSTEMROLE_ADMIN) {
                        $ret = [
                            'ret' => 4,
                            'status' => 'Don\t have rights to modify configs'
                        ];
                    } else {
                        $myconfigs = $me->getConfigs();
                        $found = FALSE;
                        foreach ($myconfigs as $config) {
                            if ($config['id'] == $id) {
                                $found = TRUE;
                            }
                        }

                        if ($found) {
                            $c->setAttributes($_REQUEST);
                            $ret = [
                                'ret' => 0,
                                'status' => 'Success',
                            ];
                        } else {
                            $c->setAttributes($_REQUEST);
                            $ret = [
                                'ret' => 5,
                                'status' => 'You don\'t have rights to edit this config',
                            ];
                        }
                    }
                }
            }
        }
    } else {
        $ret = [
            'ret' => 2,
            'status' => 'Invalid config id'
        ];
    }

    return($ret);
}
