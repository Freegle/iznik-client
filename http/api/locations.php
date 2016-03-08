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
        case 'GET': {
            $lat = presdef('lat', $_REQUEST, NULL);
            $lng = presdef('lng', $_REQUEST, NULL);

            if ($lat && $lng) {
                $ret = [ 'ret' => 0, 'status' => 'Success', 'location' => $l->closestPostcode($lat, $lng) ];
            }
            break;
        }

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
                            $m->setPrivate('suggestedsubject', $m->suggestSubject($groupid, $m->getSubject()));
                            $ret['message'] = $m->getPublic(FALSE, FALSE);
                            $m->setPrivate('locationid', $ret['message']['location']['id']);
                        }

                        break;
                }
            }

            break;
        }
    }

    return($ret);
}
