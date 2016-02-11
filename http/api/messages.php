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
                    # A search.
                    $search = presdef('search', $_REQUEST, NULL);
                    $search = $search ? trim($search) : NULL;
                    $ctx = presdef('context', $_REQUEST, NULL);
                    $limit = presdef('limit', $_REQUEST, Search::Limit);

                    if (is_numeric($search)) {
                        $m = new Message($dbhr, $dbhm, $search);

                        if ($m->getID() == $search) {
                            # Found by message id.
                            list($groups, $msgs) = $c->fillIn([ [ 'id' => $search ] ], $limit);
                        }
                    } else {
                        # Not an id search
                        $m = new Message($dbhr, $dbhm);
                        $msgs = $m->search($search, $ctx, $limit, NULL, $groups);
                        list($groups, $msgs) = $c->fillIn($msgs, $limit);
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

            $g = new Group($dbhr, $dbhm, $groupid);
            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($g && $me && $me->isModOrOwner($groupid)) {
                $r = new MailRouter($dbhr, $dbhm);
                $id = $r->received($source, $from, $g->getPrivate('nameshort') . '@yahoogroups.com', $message, $groupid);
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
