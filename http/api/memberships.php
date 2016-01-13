<?php
function memberships() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $userid = intval(presdef('userid', $_REQUEST, NULL));
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $role = presdef('role', $_REQUEST, NULL);
    $email = presdef('email', $_REQUEST, NULL);
    $limit = presdef('limit', $_REQUEST, 5);
    $search = presdef('search', $_REQUEST, NULL);
    $ctx = presdef('context', $_REQUEST, NULL);
    $settings = presdef('settings', $_REQUEST, NULL);
    $members = presdef('members', $_REQUEST, NULL);
    $ban = array_key_exists('ban', $_REQUEST) ? filter_var($_REQUEST['ban'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $logs = array_key_exists('logs', $_REQUEST) ? filter_var($_REQUEST['logs'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $logctx = presdef('logcontext', $_REQUEST, NULL);
    $collection = presdef('collection', $_REQUEST, MembershipCollection::APPROVED);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $u = new User($dbhr, $dbhm, $userid);
    $g = new Group($dbhr, $dbhm, $groupid);

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($me) {
                if ($userid && ($me->isModOrOwner($groupid) || $userid == $me->getId())) {
                    # Get just one.  We can get this if we're a mod or it's our own.
                    $members = $g->getMembers(1, NULL, $ctx, $userid);

                    $ret = [
                        'member' => count($members) == 1 ? $members[0] : NULL,
                        'context' => $ctx,
                        'ret' => 0,
                        'status' => 'Success'
                    ];

                    if ($logs) {
                        $u = new User($dbhr, $dbhm, $userid);
                        $atts = $u->getPublic(NULL, TRUE, $logs, $logctx);
                        $ret['member']['logs'] = $atts['logs'];
                        $ret['logcontext'] = $ctx;
                    }
                } else if ($me->isModOrOwner($groupid)) {
                    # Get some/all.
                    $ret = [
                        'members' => $g->getMembers($limit, $search, $ctx),
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                    $ret['context'] = $ctx;
                }
            }

            break;
        }

        case 'PUT': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            if ($u && $me && $me->isModOrOwner($groupid) && $email) {
                # We can add them, but not as someone higher than us.
                $role = $u->roleMin($role, $me->getRole($groupid));

                # Get the emailid.  This will add it if absent.
                $emailid = $u->addEmail($email);

                $u->addMembership($groupid, $role, $emailid);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
            }

            break;
        }

        case 'DELETE': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            if ($u && $me && $me->isModOrOwner($groupid)) {
                # We can remove them, but not if they are someone higher than us.
                $myrole = $me->getRole($groupid);
                if ($myrole == $u->roleMax($myrole, $u->getRole($groupid))) {
                    $u->removeMembership($groupid, $ban);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
            }

            break;
        }

        case 'PATCH': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($me) {
                if ($me->isModOrOwner($groupid)) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];

                    if ($role) {
                        # We can set the role, but not to something higher than our own.
                        $role = $u->roleMin($role, $me->getRole($groupid));
                        $u->setRole($role, $groupid);
                    }

                    if (pres('configid', $settings)) {
                        # We want to change the config that we use to mod this group.  Check that the config id
                        # passed is one to which we have access.
                        $configs = $me->getConfigs();

                        foreach ($configs as $config) {
                            if ($config['id'] == $settings['configid']) {
                                $c = new ModConfig($dbhr, $dbhm, $config['id']);
                                $c->useOnGroup($me->getId(), $groupid);
                            }
                        }

                        unset($settings['configid']);
                    }

                    if ($members) {
                        $ret = $g->setMembers($members, $collection);
                    }
                }

                if ($me->isModOrOwner($groupid)|| $me->getId() == $userid) {
                    # We can change settings for a user if we're a mod or they are our own
                    $ret = $u->setGroupSettings($groupid, $settings);
                }
            }

            break;
        }
    }

    return($ret);
}
