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
    $subject = presdef('subject', $_REQUEST, NULL);
    $body = presdef('body', $_REQUEST, NULL);
    $stdmsgid = presdef('stdmsgid', $_REQUEST, NULL);
    $action = presdef('action', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($collection) {
        case MembershipCollection::APPROVED:
        case MembershipCollection::PENDING:
        case MembershipCollection::BANNED:
        case MembershipCollection::SPAM:
            break;
        default:
            $collection = NULL;
    }

    $u = new User($dbhr, $dbhm, $userid);
    $g = new Group($dbhr, $dbhm, $groupid);

    if ($collection) {
        switch ($_REQUEST['type']) {
            case 'GET': {
                $ret = ['ret' => 2, 'status' => 'Permission denied'];

                if ($me) {
                    $groupids = [];

                    if ($groupid && ($me->isModOrOwner($groupid) || ($userid && $userid == $me->getId()))) {
                        # Get just one.  We can get this if we're a mod or it's our own.
                        $groupids[] = $groupid;
                        $limit = $userid ? 1 : $limit;
                    } else {
                        # No group was specified - use the current memberships, if we have any, excluding those that our
                        # preferences say shouldn't be in.
                        #
                        # We always show spammers, because we want mods to act on them asap.
                        $mygroups = $me->getMemberships(TRUE);
                        foreach ($mygroups as $group) {
                            $settings = $me->getGroupSettings($group['id']);
                            if (!array_key_exists('showmembers', $settings) ||
                                $settings['showmembers'] ||
                                $collection == MembershipCollection::SPAM) {
                                $groupids[] = $group['id'];
                            }
                        }
                    }

                    if (count($groupids) > 0) {
                        $members = $g->getMembers($limit, $search, $ctx, $userid, $collection, $groupids);

                        if ($userid) {
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
                        } else {
                            # Get some/all.
                            $ret = [
                                'members' => $members,
                                'context' => $ctx,
                                'ret' => 0,
                                'status' => 'Success'
                            ];
                        }
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

            case 'POST': {
                $ret = ['ret' => 2, 'status' => 'Permission denied'];
                $role = $me ? $me->getRole($groupid) : User::ROLE_NONMEMBER;

                if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                    $ret = [ 'ret' => 0, 'status' => 'Success' ];

                    switch ($action) {
                        case 'Delete':
                            # The delete call will handle any rejection on Yahoo if required.
                            $u->delete($groupid, $subject, $body, $stdmsgid);
                            break;
                        case 'Reject':
                            if (!$u->isPending($groupid)) {
                                $ret = ['ret' => 3, 'status' => 'Member is not pending'];
                            } else {
                                $u->reject($groupid, $subject, $body, $stdmsgid);
                            }
                            break;
                        case 'Approve':
                            if (!$u->isPending($groupid)) {
                                $ret = ['ret' => 3, 'status' => 'Member is not pending'];
                            } else {
                                $u->approve($groupid, $subject, $body, $stdmsgid);
                            }
                            break;
                        case 'Hold':
                            $u->hold($groupid);
                            break;
                        case 'Release':
                            $u->release($groupid);
                            break;
                    }
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

                    if ($me->isModOrOwner($groupid) || $me->getId() == $userid) {
                        # We can change settings for a user if we're a mod or they are our own
                        $ret = $u->setGroupSettings($groupid, $settings);
                    }
                }

                break;
            }
        }
    } else {
        $ret = [ 'ret' => 3, 'status' => 'Bad collection' ];
    }

    return($ret);
}
