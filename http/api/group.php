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

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'group' => $g->getPublic()
                ];

                $ret['group']['myrole'] = $me ? $me->getRole($id) : User::ROLE_NONMEMBER;
                $ret['group']['mysettings'] = $me ? $me->getGroupSettings($id) : NULL;

                if ($members && $me && $me->isModOrOwner($id)) {
                    $ret['group']['members'] = $g->getMembers();
                }

                break;
            }

            case 'PATCH': {
                $members = presdef('members', $_REQUEST, NULL);
                $mysettings = presdef('mysettings', $_REQUEST, NULL);
                error_log("PATCH " . var_export($_REQUEST, true));
                error_log("POST " . var_export($_POST, true));

                $ret = [
                    'ret' => 1,
                    'status' => 'Not logged in',
                ];

                if ($me) {
                    error_log("Logged in");
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

                    error_log("After perms {$ret['ret']}");
                    if ($ret['ret'] == 0) {
                        if ($members && !$g->setMembers($members)) {
                            $ret = [ 'ret' => 2, 'status' => 'Set members failed' ];
                        }

                        if ($mysettings && !$me->setGroupSettings($id, $mysettings)) {
                            $ret = [ 'ret' => 2, 'status' => 'mysettings failed' ];
                        }
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
