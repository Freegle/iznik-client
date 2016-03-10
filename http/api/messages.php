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
    $message = presdef('message', $_REQUEST, NULL);
    $yahoopendingid = presdef('yahoopendingid', $_REQUEST, NULL);
    $yahooapprovedid = presdef('yahooapprovedid', $_REQUEST, NULL);
    $collections = presdef('collections', $_REQUEST, [ MessageCollection::APPROVED, MessageCollection::SPAM ]);
    $messages = presdef('messages', $_REQUEST, NULL);
    $subaction = presdef('subaction', $_REQUEST, NULL);

    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $groups = [];

            if ($groupid) {
                # A group was specified
                $groups[] = $groupid;
            } else if ($me) {
                # No group was specified - use the current memberships, if we have any, excluding those that our
                # preferences say shouldn't be in.
                $mygroups = $me->getMemberships(TRUE);
                foreach ($mygroups as $group) {
                    $settings = $me->getGroupSettings($group['id']);
                    if (!array_key_exists('showmessages', $settings) ||
                        $settings['showmessages']) {
                        $groups[] = $group['id'];
                    }
                }

                # Ensure that if we aren't in any groups, we don't treat this as a systemwide search.
                $groups[] = 0;
            }

            $msgs = NULL;
            $c = new MessageCollection($dbhr, $dbhm, $collection);

            $ret = [
                'ret' => 0,
                'status' => 'Success'
            ];

            switch ($subaction) {
                case NULL:
                    # Just a normal fetch.
                    list($groups, $msgs) = $c->get($ctx, $limit, $groups);
                    break;
                case 'search':
                case 'searchmess':
                    # A search on message info.
                    $search = presdef('search', $_REQUEST, NULL);
                    $search = $search ? trim($search) : NULL;
                    $ctx = presdef('context', $_REQUEST, NULL);
                    $limit = presdef('limit', $_REQUEST, Search::Limit);
                    $messagetype = presdef('messagetype', $_REQUEST, NULL);
                    $nearlocation = presdef('nearlocation', $_REQUEST, NULL);

                    if (is_numeric($search)) {
                        $m = new Message($dbhr, $dbhm, $search);

                        if ($m->getID() == $search) {
                            # Found by message id.
                            list($groups, $msgs) = $c->fillIn([ [ 'id' => $search ] ], $limit, NULL);
                        }
                    } else {
                        # Not an id search
                        $m = new Message($dbhr, $dbhm);
                        $msgs = $m->search($search, $ctx, $limit, NULL, $groups);
                        list($groups, $msgs) = $c->fillIn($msgs, $limit, $messagetype, NULL);
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

                    $g = new Group($dbhr, $dbhm);
                    $membctx = NULL;
                    $members = $g->getMembers(1000, $search, $membctx, NULL, $collection, $groupids, NULL, NULL);
                    $userids = [];
                    foreach ($members as $member) {
                        error_log("Got user {$member['userid']}");
                        $userids[] = $member['userid'];
                    }

                    $members = NULL;

                    # Now get the messages for those members.
                    $c = new MessageCollection($dbhr, $dbhm, $collection);
                    list ($groups, $msgs) = $c->get($ctx, $limit, $groupids, $userids);
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

            $g = new Group($dbhr, $dbhm, $groupid);
            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($source && $g && $me && $me->isModOrOwner($groupid)) {
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
                        'routed' => $rc
                    ];
                }
            }
        }
        break;

        case 'POST': {
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

            if ($me) {
                # Check if we're logged in and have rights.
                $g = new Group($dbhr, $dbhm, $groupid);
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
