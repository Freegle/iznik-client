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

    # TODO jQuery won't send an empty array, so we have a hack to ensure we can empty out the pending members.  What's
    # the right way to do this?
    $members = presdef('members', $_REQUEST, presdef('memberspresentbutempty', $_REQUEST, 0) ? [] : NULL);
    $ban = array_key_exists('ban', $_REQUEST) ? filter_var($_REQUEST['ban'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $logs = array_key_exists('logs', $_REQUEST) ? filter_var($_REQUEST['logs'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $logctx = presdef('logcontext', $_REQUEST, NULL);
    $collection = presdef('collection', $_REQUEST, MembershipCollection::APPROVED);
    $subject = presdef('subject', $_REQUEST, NULL);
    $body = presdef('body', $_REQUEST, NULL);
    $stdmsgid = presdef('stdmsgid', $_REQUEST, NULL);
    $action = presdef('action', $_REQUEST, NULL);
    $yps = presdef('yahooPostingStatus', $_REQUEST, NULL);
    $ydt = presdef('yahooDeliveryType', $_REQUEST, NULL);

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
                    $proceed = FALSE;

                    if ($me->isAdminOrSupport() && $search) {
                        # Admin or support can search all groups.
                        $groupids = $groupid ? [ $groupid ] : NULL;
                        $proceed = TRUE;
                    } else if ($groupid && ($me->isModOrOwner($groupid) || ($userid && $userid == $me->getId()))) {
                        # Get just one.  We can get this if we're a mod or it's our own.
                        $groupids[] = $groupid;
                        $limit = $userid ? 1 : $limit;
                        $proceed = TRUE;
                    } else {
                        # No group was specified - use the current memberships, if we have any, excluding those that our
                        # preferences say shouldn't be in.
                        #
                        # We always show spammers, because we want mods to act on them asap.
                        $mygroups = $me->getMemberships(TRUE);
                        foreach ($mygroups as $group) {
                            $proceed = TRUE;
                            $settings = $me->getGroupSettings($group['id']);
                            if (!array_key_exists('showmembers', $settings) ||
                                $settings['showmembers'] ||
                                $collection == MembershipCollection::SPAM) {
                                $groupids[] = $group['id'];
                            }
                        }
                    }

                    if ($proceed) {
                        if (count($groupids) == 1 && $action == 'exportyahoo') {
                            $ret = [
                                'members' => $g->exportYahoo($groupids[0]),
                                'ret' => 0,
                                'status' => 'Success'
                            ];
                        } else {
                            $members = $g->getMembers($limit, $search, $ctx, $userid, $collection, $groupids, $yps, $ydt);

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
                                    $ret['member']['merges'] = $atts['merges'];
                                    $ret['logcontext'] = $logctx;
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
                            # The reject call will handle any rejection on Yahoo if required.
                            $u->reject($groupid, NULL, NULL, NULL);
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
                        case 'Leave Member':
                        case 'Leave Approved Member':
                            $u->mail($groupid, $subject, $body, $stdmsgid, $action);
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

                        if ($members !== NULL) {
                            # Check when the last member sync was.  If it's within the last few minutes, don't
                            # bother resyncing.  This helps with the case where the client times out waiting, and
                            # then retries forever but the sync has actually happened.
                            $last = $g->getPrivate('lastyahoomembersync');
                            $time = strtotime('now') - strtotime($last);
                            $synctime = presdef('synctime', $_REQUEST, ISODate("@" . time()));
                            error_log("Member sync for " . $g->getPrivate('nameshort') . " $last, $time ago");

                            if (($time > 600 && $collection == MessageCollection::APPROVED) ||
                                ($collection != MessageCollection::APPROVED)) {
                                $ret = $g->setMembers($members, $collection, $synctime);
                            } else {
                                $ret = [ 'ret' => 0, 'status' => 'Ignore member sync as happened recently'];
                                error_log("Ignore member sync for " . $g->getPrivate('nameshort') . " as last sync at $last");
                            }
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
