<?php
function group() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);

    # The id parameter can be an ID or a nameshort.
    $id = presdef('id', $_REQUEST, NULL);
    $nameshort = NULL;

    if (is_numeric($id)) {
        $id = intval($id);
    } else {
        $nameshort = $id;
    }

    $action = presdef('action', $_REQUEST, NULL);

    if ($nameshort) {
        $g = new Group($dbhr, $dbhm);
        $id = $g->findByShortName($nameshort);
    }

    if ($id) {
        $g = new Group($dbhr, $dbhm, $id);

        switch ($_REQUEST['type']) {
            case 'GET': {
                $members = array_key_exists('members', $_REQUEST) ? filter_var($_REQUEST['members'], FILTER_VALIDATE_BOOLEAN) : FALSE;
                $c = new ModConfig($dbhr, $dbhm);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'group' => $g->getPublic()
                ];

                $ret['group']['myrole'] = $me ? $me->getRole($id) : User::ROLE_NONMEMBER;
                $ret['group']['mysettings'] = $me ? $me->getGroupSettings($id) : NULL;
                $ret['group']['mysettings']['configid'] = $me ? $c->getForGroup($me->getId(), $id) : NULL;

                if ($members && $me && $me->isModOrOwner($id)) {
                    $ret['group']['members'] = $g->getMembers();
                }

                break;
            }

            case 'PATCH': {
                $members = presdef('members', $_REQUEST, NULL);
                $mysettings = presdef('mysettings', $_REQUEST, NULL);
                $settings = presdef('settings', $_REQUEST, NULL);
                #error_log("mysettings " . var_export($mysettings, true));

                $ret = [
                    'ret' => 1,
                    'status' => 'Not logged in',
                ];

                if ($me) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'groupid' => $id
                    ];

                    if (!$me->isModOrOwner($id) &&
                        ($members)) {
                        $ret = [
                            'ret' => 1,
                            'status' => 'Failed or permission denied'
                        ];
                    }

                    if ($ret['ret'] == 0) {
                        $ret = $g->setMembers($members);
                    }

                    if ($ret['ret'] == 0) {
                        if (pres('configid', $mysettings)) {
                            # We want to change the config that we use to mod this group.  Check that the config id
                            # passed is one to which we have access.
                            $configs = $me->getConfigs();

                            foreach ($configs as $config) {
                                if ($config['id'] == $mysettings['configid']) {
                                    $c = new ModConfig($dbhr, $dbhm, $config['id']);
                                    $c->useOnGroup($me->getId(), $id);
                                }
                            }

                            unset($mysettings['configid']);
                        }

                        $ret = $me->setGroupSettings($id, $mysettings);
                    }

                    if ($ret['ret'] == 0 && $settings) {
                        $g->setSettings($settings);
                    }
                }
            }

            case 'POST': {
                switch ($action) {
                    case 'ConfirmKey': {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'key' => $g->getConfirmKey()
                        ];

                        break;
                    }
                }

                break;
            }
        }
    } else {
        $ret = [
            'ret' => 2,
            'status' => 'We don\'t host that group'
        ];
    }

    return($ret);
}
