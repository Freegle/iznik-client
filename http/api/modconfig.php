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
                        $c = new ModConfig($dbhr, $dbhm);

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
//                $members = presdef('members', $_REQUEST, NULL);
//                $mysettings = presdef('mysettings', $_REQUEST, NULL);
//                $settings = presdef('settings', $_REQUEST, NULL);
//                #error_log("mysettings " . var_export($mysettings, true));
//
//                $ret = [
//                    'ret' => 1,
//                    'status' => 'Not logged in',
//                ];
//
//                if ($me) {
//                    $ret = [
//                        'ret' => 0,
//                        'status' => 'Success',
//                        'groupid' => $id
//                    ];
//
//                    if (!$me->isModOrOwner($id) &&
//                        ($members)) {
//                        $ret = [
//                            'ret' => 1,
//                            'status' => 'Failed or permission denied'
//                        ];
//                    }
//
//                    if ($ret['ret'] == 0) {
//                        $ret = $g->setMembers($members);
//                    }
//
//                    if ($ret['ret'] == 0) {
//                        if (pres('configid', $mysettings)) {
//                            # We want to change the config that we use to mod this group.  Check that the config id
//                            # passed is one to which we have access.
//                            $configs = $me->getConfigs();
//
//                            foreach ($configs as $config) {
//                                if ($config['id'] == $mysettings['configid']) {
//                                    $c = new ModConfig($dbhr, $dbhm, $config['id']);
//                                    $c->useOnGroup($me->getId(), $id);
//                                }
//                            }
//
//                            unset($mysettings['configid']);
//                        }
//
//                        $ret = $me->setGroupSettings($id, $mysettings);
//                    }
//
//                    if ($ret['ret'] == 0 && $settings) {
//                        $g->setSettings($settings);
//                    }
//                }
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
