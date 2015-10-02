<?php
function messages() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $groupid = presdef('groupid', $_REQUEST, NULL);
    $collection = presdef('collection', $_REQUEST, Collection::APPROVED);
    $start = intval(presdef('start', $_REQUEST, NULL));
    $limit = intval(presdef('limit', $_REQUEST, 100));
    $source = presdef('source', $_REQUEST, NULL);
    $from = presdef('from', $_REQUEST, NULL);
    $message = presdef('message', $_REQUEST, NULL);
    $yahoopendingid = presdef('yahoopendingid', $_REQUEST, NULL);
    $collections = presdef('collections', $_REQUEST, [ Collection::APPROVED, Collection::SPAM ]);
    $messages = presdef('messages', $_REQUEST, NULL);

    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET': {
            # Check if we're logged in
            $groups = [];

            if ($groupid) {
                # A group was specified
                $groups[] = $groupid;
            } else if ($me) {
                # No group was specified - use the current memberships, if we have any.
                $mygroups = $me->getMemberships();
                foreach ($mygroups as $group) {
                    $groups[] = $group['id'];
                }
            }

            $c = new Collection($dbhr, $dbhm, $collection);
            list($groups, $msgs) = $c->get($start, $limit, $groups);

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'groups' => $groups,
                'messages' => $msgs
            ];
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
                $id = $r->received($source, $from, $g->getPrivate('nameshort') . '@yahoogroups.com', $message);
                $rc = $r->route();
                $m = new Message($dbhr, $dbhm, $id);
                $m->setYahooPendingId($yahoopendingid);

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
