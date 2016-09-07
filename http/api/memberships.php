<?php
function memberships() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $userid = intval(presdef('userid', $_REQUEST, NULL));

    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $role = presdef('role', $_REQUEST, User::ROLE_MEMBER);
    $email = presdef('email', $_REQUEST, NULL);
    $limit = presdef('limit', $_REQUEST, 5);
    $search = presdef('search', $_REQUEST, NULL);
    $ctx = presdef('context', $_REQUEST, NULL);
    $settings = presdef('settings', $_REQUEST, NULL);
    $emailfrequency = presdef('emailfrequency', $_REQUEST, NULL);
    $filter = intval(presdef('filter', $_REQUEST, Group::FILTER_NONE));

    # TODO jQuery won't send an empty array, so we have a hack to ensure we can empty out the pending members.  What's
    # the right way to do this?
    $members = presdef('members', $_REQUEST, presdef('memberspresentbutempty', $_REQUEST, 0) ? [] : NULL);
    $ban = array_key_exists('ban', $_REQUEST) ? filter_var($_REQUEST['ban'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $logs = array_key_exists('logs', $_REQUEST) ? filter_var($_REQUEST['logs'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $modmailsonly = array_key_exists('modmailsonly', $_REQUEST) ? filter_var($_REQUEST['modmailsonly'], FILTER_VALIDATE_BOOLEAN) : FALSE;
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

                if ($email) {
                    # We're getting a minimal set of membership information, typically from the unsubscribe page.
                    $ret = ['ret' => 3, 'status' => "We don't know that email" ];
                    $uid = $u->findByEmail($email);

                    if ($uid) {
                        $u = new User($dbhr, $dbhm, $uid);
                        $memberships = $u->getMemberships(FALSE, presdef('grouptype', $_REQUEST, NULL));
                        $ret = ['ret' => 0, 'status' => 'Success', 'memberships' => [] ];
                        foreach ($memberships as $membership) {
                            $ret['memberships'][] = [ 'id' => $membership['id'], 'namedisplay' => $membership['namedisplay'] ];
                        }
                    }
                } else if ($me) {
                    $groupids = [];
                    $proceed = FALSE;

                    if ($groupid && ($me->isModOrOwner($groupid) || ($userid && $userid == $me->getId()))) {
                        # Get just one.  We can get this if we're a mod or it's our own.
                        $groupids[] = $groupid;
                        $limit = $userid ? 1 : $limit;
                        $proceed = TRUE;
                    } else if ($me->isModerator()) {
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
                                $proceed = TRUE;
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
                            $members = $g->getMembers($limit, $search, $ctx, $userid, $collection, $groupids, $yps, $ydt, $filter);

                            if ($userid) {
                                $ret = [
                                    'member' => count($members) == 1 ? $members[0] : NULL,
                                    'context' => $ctx,
                                    'ret' => 0,
                                    'status' => 'Success'
                                ];

                                if ($logs) {
                                    $u = new User($dbhr, $dbhm, $userid);
                                    $atts = $u->getPublic(NULL, TRUE, $logs, $logctx, FALSE, FALSE, FALSE, $modmailsonly);
                                    $ret['member']['logs'] = $atts['logs'];
                                    $ret['member']['merges'] = $atts['merges'];
                                    $ret['logcontext'] = $logctx;
                                }
                            } else {
                                if ($me->isAdminOrSupport()) {
                                    # Get any sessions.
                                    $u = new User($dbhr, $dbhm);
                                    foreach ($members as &$member) {
                                        if (pres('userid', $member)) {
                                            $member['sessions'] = $u->getSessions($dbhr, $dbhm, $member['userid']);
                                        }
                                    }
                                }

                                # Get some/all.
                                $ret = [
                                    'members' => $members,
                                    'groups' => [],
                                    'context' => $ctx,
                                    'ret' => 0,
                                    'status' => 'Success'
                                ];

                                foreach ($members as $member) {
                                    if (!pres($member['groupid'], $ret['groups'])) {
                                        $g = new Group($dbhr, $dbhm, $member['groupid']);
                                        $ret['groups'][$member['groupid']] = $g->getPublic();
                                    }
                                }
                            }
                        }
                    }
                }

                break;
            }

            case 'PUT': {
                $ret = ['ret' => 2, 'status' => 'Permission denied'];

                # We might have been passed a userid; if not, then assume we're acting on ourselves.
                $userid = $userid ? $userid : ($me ? $me->getId() : NULL);
                $u = new User($dbhr, $dbhm, $userid);

                if ($u && $me && $u->getId() && $me->getId()) {
                    if ($userid && $userid != $me->getId()) {
                        # If this isn't us, we can add them, but not as someone with higher permissions than us.
                        $role = $u->roleMin($role, $me->getRoleForGroup($groupid));
                    }

                    if ($email) {
                        # Get the emailid we'd like to use on this group.  This will add it if absent.
                        $emailid = $u->addEmail($email);
                    } else {
                        # We've not asked to use a specific email address.  Just use our preferred one.
                        $emailid = $u->getAnEmailId();
                    }

                    $u->addMembership($groupid, $role, $emailid);

                    $g = new Group($dbhr, $dbhm, $groupid);
                    if ($g->onYahoo()) {
                        # This group is on Yahoo too, so we should trigger a membership application to there if we
                        # don't already have one of our emails on the group.
                        #
                        # TODO Need to handle the case where this application is rejected.  In FDv1-2 this could
                        # not occur as FBUser members were pre-approved, but it can now.
                        list ($eid, $alreadymail) = $u->getEmailForYahooGroup($groupid, TRUE);

                        if (!$eid) {
                            $u->triggerYahooApplication($groupid);
                        }
                    }

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }

                break;
            }

            case 'POST': {
                $ret = ['ret' => 2, 'status' => 'Permission denied'];
                $role = $me ? $me->getRoleForGroup($groupid) : User::ROLE_NONMEMBER;

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

                if ($email) {
                    # We are unsubscribing when logged out.  There is a DoS attack here, but there's a benefit in
                    # allowing users who can't manage to log in to unsubscribe.  We only allow an unsubscribe on a
                    # group as a member to avoid the DoS hitting mods.
                    $ret = ['ret' => 3, 'status' => "We don't know that email" ];
                    $uid = $u->findByEmail($email);

                    if ($uid) {
                        $u = new User($dbhm, $dbhm, $uid);
                        $ret = ['ret' => 4, 'status' => "Can't remove from that group" ];
                        if ($u->isApprovedMember($groupid) && !$u->isModOrOwner($groupid)) {
                            $ret = ['ret' => 0, 'status' => 'Success' ];
                            $u->removeMembership($groupid);
                        }
                    }
                } else if ($u && $me && ($me->isAdminOrSupport() || $me->isModOrOwner($groupid) || $userid == $me->getId())) {
                    # We can remove them, but not if they are someone higher than us.
                    $myrole = $me->getRoleForGroup($groupid);
                    if ($myrole == $u->roleMax($myrole, $u->getRoleForGroup($groupid))) {
                        $rc = $u->removeMembership($groupid, $ban);

                        if ($rc) {
                            $ret = [
                                'ret' => 0,
                                'status' => 'Success'
                            ];
                        }
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

                        # We don't want to default the role to anything here, otherwise we might patch ourselves
                        # to a member.
                        $role = presdef('role', $_REQUEST, NULL);

                        if ($role) {
                            # We can set the role, but not to something higher than our own.
                            $role = $u->roleMin($role, $me->getRoleForGroup($groupid));
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
                            $synctime = presdef('synctime', $_REQUEST, ISODate("@" . time()));

                            if ($collection == MembershipCollection::APPROVED) {
                                # Check when the last member sync was.  If it's within the last few minutes, don't
                                # bother resyncing.  This helps with the case where the client times out waiting, and
                                # then retries forever but the sync has actually happened.
                                $last = $g->getPrivate('lastyahoomembersync');
                                $time = strtotime('now') - strtotime($last);
                                error_log("Member sync for " . $g->getPrivate('nameshort') . " $last, $time ago");

                                if ($time > 600) {
                                    # It's been a little while since we did this.  Queue it (the actual sync happens
                                    # in a background script).
                                    $g->queueSetMembers($members, $synctime);
                                } else {
                                    $ret = [ 'ret' => 0, 'status' => 'Ignore member sync as happened recently'];
                                    error_log("Ignore member sync for " . $g->getPrivate('nameshort') . " as last sync at $last");
                                }
                            } else {
                                # For other collections, which aren't large, we do the work inline.
                                $ret = $g->setMembers($members, $collection, $synctime);
                            }
                        }
                    }

                    if ($me->isModOrOwner($groupid) || $me->getId() == $userid) {
                        # We can change settings for a user if we're a mod or they are our own
                        $rc = TRUE;
                        
                        if ($settings) {
                            $rc &= $u->setGroupSettings($groupid, $settings);
                        } 
                        
                        if ($emailfrequency !== NULL) {
                            $rc &= $u->setMembershipAtt($groupid, 'emailfrequency', intval($emailfrequency));
                        }

                        $ret = $rc ? [ 'ret' => 0, 'status' => 'Success' ] : [ 'ret' => 2, 'status' => 'Set failed' ];
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
