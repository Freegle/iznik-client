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
            $swlat = presdef('swlat', $_REQUEST, NULL);
            $swlng = presdef('swlng', $_REQUEST, NULL);
            $nelat = presdef('nelat', $_REQUEST, NULL);
            $nelng = presdef('nelng', $_REQUEST, NULL);
            $typeahead = presdef('typeahead', $_REQUEST, NULL);

            if ($lat && $lng) {
                $ret = [ 'ret' => 0, 'status' => 'Success', 'location' => $l->closestPostcode($lat, $lng) ];
            } else if ($typeahead) {
                $ret = [ 'ret' => 0, 'status' => 'Success', 'locations' => $l->typeahead($typeahead) ];
            } else if ($swlat || $swlng || $nelat || $nelng) {
                $ret = [ 'ret' => 0, 'status' => 'Success', 'locations' => $l->withinBox($swlat, $swlng, $nelat, $nelng) ];
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

        case 'PATCH': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            $role = $me ? $me->getPrivate('systemrole') : User::ROLE_NONMEMBER;
            error_log("System role $role");

            if ($role == User::SYSTEMROLE_MODERATOR || $role == User::SYSTEMROLE_SUPPORT || $role == User::SYSTEMROLE_ADMIN) {
                $polygon = presdef('polygon', $_REQUEST, NULL);
                if ($polygon) {
                    $worked = FALSE;
                    if ($l->setGeometry($polygon)) {
                        $worked = TRUE;
                    }
                }

                if ($worked) {
                    $ret = [ 'ret' => 0, 'status' => 'Success' ];
                }
            }

            break;
        }
    }

    return($ret);
}
