<?php
function messages() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $collection = presdef('collection', $_REQUEST, MessageCollection::APPROVED);
    $ctx = presdef('context', $_REQUEST, NULL);
    $limit = intval(presdef('limit', $_REQUEST, 5));
    $source = presdef('source', $_REQUEST, NULL);
    $from = presdef('from', $_REQUEST, NULL);
    $fromuser = presdef('fromuser', $_REQUEST, NULL);
    $types = presdef('types', $_REQUEST, NULL);
    $message = presdef('message', $_REQUEST, NULL);
    $yahoopendingid = presdef('yahoopendingid', $_REQUEST, NULL);
    $yahooapprovedid = presdef('yahooapprovedid', $_REQUEST, NULL);
    $collections = presdef('collections', $_REQUEST, [ MessageCollection::APPROVED, MessageCollection::SPAM ]);
    $messages = presdef('messages', $_REQUEST, NULL);
    $subaction = presdef('subaction', $_REQUEST, NULL);
    $modtools = array_key_exists('modtools', $_REQUEST) ? filter_var($_REQUEST['modtools'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $grouptype = presdef('grouptype', $_REQUEST, NULL);
    $exactonly = array_key_exists('exactonly', $_REQUEST) ? filter_var($_REQUEST['exactonly'], FILTER_VALIDATE_BOOLEAN) : FALSE;

    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $groups = [];
            $userids = [];

            if ($collection != MessageCollection::DRAFT) {
                if ($subaction == 'searchall' && $me && $me->isAdminOrSupport()) {
                    # We are intentionally searching the whole system, and are allowed to.
                } else if ($groupid) {
                    # A group was specified
                    $groups[] = $groupid;
                } else if ($me) {
                    # No group was specified - use the current memberships, if we have any, excluding those that our
                    # preferences say shouldn't be in.
                    $mygroups = $me->getMemberships($modtools, $grouptype);
                    foreach ($mygroups as $group) {
                        $settings = $me->getGroupSettings($group['id']);
                        if (!MODTOOLS || !array_key_exists('active', $settings) || $settings['active']) {
                            $groups[] = $group['id'];
                        }
                    }

                    if (count($groups) == 0) {
                        # Ensure that if we aren't in any groups, we don't treat this as a systemwide search.
                        $groups[] = 0;
                    }
                }
            }

            if ($fromuser) {
                # We're looking for messages from a specific user
                $userids[] = $fromuser;
            }
            
            $msgs = NULL;
            $c = new MessageCollection($dbhr, $dbhm, $collection);

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'searchgroups' => $groups,
                'searchgroup' => $groupid
            ];

            switch ($subaction) {
                case NULL:
                    # Just a normal fetch.
                    list($groups, $msgs) = $c->get($ctx, $limit, $groups, $userids, Message::checkTypes($types), $collection == MessageCollection::ALLUSER ? MessageCollection::OWNPOSTS: NULL);
                    break;
                case 'search':
                case 'searchmess':
                case 'searchall':
                    # A search on message info.
                    $search = presdef('search', $_REQUEST, NULL);
                    $search = $search ? trim($search) : NULL;
                    $ctx = presdef('context', $_REQUEST, NULL);
                    $limit = presdef('limit', $_REQUEST, Search::Limit);
                    $messagetype = presdef('messagetype', $_REQUEST, NULL);
                    $nearlocation = presdef('nearlocation', $_REQUEST, NULL);
                    $nearlocation = $nearlocation ? intval($nearlocation) : NULL;

                    if (is_numeric($search)) {
                        $m = new Message($dbhr, $dbhm, $search);

                        if ($m->getID() == $search) {
                            # Found by message id.
                            list($groups, $msgs) = $c->fillIn([ [ 'id' => $search ] ], $limit, NULL);
                        }
                    } else {
                        # Not an id search
                        $m = new Message($dbhr, $dbhm);

                        if ($nearlocation) {
                            # We need to look in the groups near this location.
                            $l = new Location($dbhr, $dbhm, $nearlocation);
                            $groups = $l->groupsNear();
                        }

                        do {
                            $searched = $m->search($search, $ctx, $limit, NULL, $groups, $nearlocation, $exactonly);
                            list($groups, $msgs) = $c->fillIn($searched, $limit, $messagetype, NULL);
                            # We might have excluded all the messages we found; if so, keep going.
                        } while (count($searched) > 0 && count($msgs) == 0);
                    }

                    break;
                case 'searchmemb':
                    # A search for messages based on member.  It is most likely that this is a search where relatively
                    # few members match, so it is quickest for us to get all the matching members, then use a context
                    # to return paged results within those.  We put a fallback limit on the number of members to stop
                    # ourselves exploding, though.
                    $search = presdef('search', $_REQUEST, NULL);
                    $search = $search ? trim($search) : NULL;
                    $ctx = presdef('context', $_REQUEST, NULL);
                    $limit = presdef('limit', $_REQUEST, Search::Limit);

                    $groupids = $groupid ? [ $groupid ] : NULL;

                    $g = Group::get($dbhr, $dbhm);
                    $membctx = NULL;
                    $members = $g->getMembers(1000, $search, $membctx, NULL, $collection, $groupids, NULL, NULL, NULL);
                    $userids = [];
                    foreach ($members as $member) {
                        $userids[] = $member['userid'];
                    }

                    $members = NULL;
                    $groups = [];
                    $msgs = [];

                    if (count($userids) > 0) {
                        # Now get the messages for those members.
                        $c = new MessageCollection($dbhr, $dbhm, $collection);
                        list ($groups, $msgs) = $c->get($ctx, $limit, $groupids, $userids, $collection == MessageCollection::ALLUSER ?  MessageCollection::OWNPOSTS : NULL);
                    }
                    break;
            }

            $ret['context'] = $ctx;
            $ret['groups'] = $groups;
            $ret['messages'] = $msgs;
        }
        break;

        case 'PUT': {
            # We are trying to sync a message.
            switch ($source) {
                case Message::YAHOO_PENDING:
                case Message::YAHOO_APPROVED:
                    break;
                default:
                    $source = NULL;
                    break;
            }

            $g = Group::get($dbhr, $dbhm, $groupid);
            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($source && $g && $me && $me->isModOrOwner($groupid)) {
                # We might already have this message, in which case it might be rejected.  We don't want to resync
                # such messages as it would put them back to Pending.
                $m = new Message($dbhr, $dbhm);
                list ($msgid, $collection) = $m->findEarlierCopy($groupid, $yahoopendingid, $yahooapprovedid);

                $ret = ['ret' => 3, 'status' => 'Not new or pending'];

                if (!$msgid || $collection == MessageCollection::PENDING) {
                    # This message is new to us, or we are updating an existing pending message.
                    $r = new MailRouter($dbhr, $dbhm);
                    $id = $r->received($source, $from, $g->getPrivate('nameshort') . '@yahoogroups.com', $message, $groupid);
                    $ret = ['ret' => 3, 'status' => 'Failed to create message - possible duplicate'];

                    if ($id) {
                        $rc = $r->route();
                        $m = new Message($dbhr, $dbhm, $id);

                        if ($yahoopendingid) {
                            $m->setYahooPendingId($groupid, $yahoopendingid);
                        }

                        if ($yahooapprovedid) {
                            $m->setYahooApprovedId($groupid, $yahooapprovedid);
                        }

                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'routed' => $rc,
                            'id' => $id
                        ];
                    }
                } else if ($msgid && $collection == MessageCollection::APPROVED && $yahoopendingid) {
                    # This is a message which is on Pending on Yahoo but has already been approved on here.
                    # Approve it again - which should result in plugin work which will remove it from Yahoo.
                    $m = new Message($dbhr, $dbhm, $msgid);
                    $m->setYahooPendingId($groupid, $yahoopendingid);
                    $m->approve($groupid);
                    $ret = [
                        'ret' => 0,
                        'status' => 'Already approved - do so again'
                    ];
                }
            }
        }
        break;

        case 'POST': {
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

            if ($me) {
                # Check if we're logged in and have rights.
                $g = Group::get($dbhr, $dbhm, $groupid);
                $ret = [ 'ret' => 3, 'status' => 'Permission denied' ];

                if ($me->isModOrOwner($groupid)) {
                    $ret = [ 'ret' => 0, 'status' => 'Success' ];
                    list($ret['missingonserver'], $ret['missingonclient']) = $g->correlate($collections, $messages);
                }
            }
        }
    }

    return($ret);
}
