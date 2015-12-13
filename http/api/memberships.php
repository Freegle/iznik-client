<?php
function memberships() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $userid = intval(presdef('userid', $_REQUEST, NULL));
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $role = presdef('role', $_REQUEST, NULL);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'PUT': {
            $u = new User($dbhr, $dbhm, $userid);

            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($u && $me && $me->isModOrOwner($groupid)) {
                $u->setRole($role, $groupid);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
            } else {
                $ret['me'] = $me->getPublic();
                $ret['mod'] = $me->isModOrOwner($groupid);
            }
        }
    }

    return($ret);
}
