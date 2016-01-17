<?php
function locations() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $messageid = intval(presdef('messageid', $_REQUEST, NULL));
    $action = presdef('action', $_REQUEST, NULL);

    $l = new Location($dbhr, $dbhm, $id);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'POST': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            $role = $me ? $me->getRole($groupid) : User::ROLE_NONMEMBER;

            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                $ret = [ 'ret' => 0, 'status' => 'Success' ];

                switch ($action) {
                    case 'Exclude':
                        $l->exclude($groupid, $me->getId());

                        if ($messageid) {
                            # Suggest a new subject for this message.
                            $m = new Message($dbhr, $dbhm, $messageid);
                            $ret['message'] = $m->getPublic(FALSE, FALSE, TRUE);
                            error_log("Set new location {$ret['message']['location']['id']} for {$messageid}");
                            $m->setPrivate('locationid', $ret['message']['location']['id']);
                        }

                        break;
                }
            }
        }
    }

    return($ret);
}
