<?php
function group() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $nameshort = presdef('nameshort', $_REQUEST, NULL);
    $action = presdef('action', $_REQUEST, 'UpdateMembers');

    if ($nameshort) {
        $g = new Group($dbhr, $dbhm);
        $groupid = $g->findByShortName($nameshort);
    }

    if ($groupid) {
        $g = new Group($dbhr, $dbhm, $groupid);

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET': {
                $members = array_key_exists('members', $_REQUEST) ? filter_var($_REQUEST['members'], FILTER_VALIDATE_BOOLEAN) : FALSE;

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'group' => $g->getPublic(),
                    'myrole' => $me ? $me->getRole($groupid) : User::ROLE_NONMEMBER
                ];

                if ($members && $me && $me->isModOrOwner($groupid)) {
                    $ret['members'] = $g->getMembers();
                }

                break;
            }

            case 'POST': {
                switch ($action) {
                    case 'UpdateMembers': {
                        $members = presdef('members', $_REQUEST, NULL);

                        $ret = [
                            'ret' => 1,
                            'status' => 'Failed or permission denied',
                        ];

                        if ($members && $me && $me->isModOrOwner($groupid)) {
                            if ($g->setMembers($members)) {
                                $ret = [
                                    'ret' => 0,
                                    'status' => 'Success',
                                ];
                            }
                        }
                        break;
                    }

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
