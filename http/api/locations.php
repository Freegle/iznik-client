<?php
function locations() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $messageid = intval(presdef('messageid', $_REQUEST, NULL));
    $action = presdef('action', $_REQUEST, NULL);
    $byname = array_key_exists('byname', $_REQUEST) ? filter_var($_REQUEST['byname'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $groupsnear = array_key_exists('groupsnear', $_REQUEST) ? filter_var($_REQUEST['groupsnear'], FILTER_VALIDATE_BOOLEAN) : TRUE;

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
            $limit = intval(presdef('limit', $_REQUEST, 10));

            if ($lat && $lng) {
                $ret = [ 'ret' => 0, 'status' => 'Success', 'location' => $l->closestPostcode($lat, $lng) ];
            } else if ($typeahead) {
                $ret = [ 'ret' => 0, 'status' => 'Success', 'locations' => $l->typeahead($typeahead, $limit, $groupsnear) ];
            } else if ($swlat || $swlng || $nelat || $nelng) {
                $ret = [ 'ret' => 0, 'status' => 'Success', 'locations' => $l->withinBox($swlat, $swlng, $nelat, $nelng) ];
            }
            break;
        }

        case 'POST': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            $role = $me ? $me->getRoleForGroup($groupid) : User::ROLE_NONMEMBER;

            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                $ret = [ 'ret' => 0, 'status' => 'Success' ];

                switch ($action) {
                    case 'Exclude':
                        $l->exclude($groupid, $me->getId(), $byname);

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

        case 'PUT': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            $role = $me ? $me->getPrivate('systemrole') : User::ROLE_NONMEMBER;

            if ($role == User::SYSTEMROLE_MODERATOR || $role == User::SYSTEMROLE_SUPPORT || $role == User::SYSTEMROLE_ADMIN) {
                $polygon = presdef('polygon', $_REQUEST, NULL);
                $name = presdef('name', $_REQUEST, NULL);

                # This parameter is used in UT.
                $osmparentsonly = array_key_exists('osmparentsonly', $_REQUEST) ? $_REQUEST['osmparentsonly'] : 1;

                if ($polygon && $name) {
                    # We create this as a place, which can be used as an area - the client wouldn't have created it
                    # if they didn't want that.
                    $id = $l->create(NULL, $name, 'Polygon', $polygon, $osmparentsonly, TRUE);
                    $ret = [ 'ret' => 0, 'status' => 'Success', 'id' => $id ];
                }
            }

            break;
        }
    }

    return($ret);
}
