<?php
function newsfeed() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

    if ($me) {
        $id = intval(presdef('id', $_REQUEST, NULL));
        $n = new Newsfeed($dbhr, $dbhm, $id);
        $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

        switch ($_REQUEST['type']) {
            case 'GET': {
                if ($id) {
                    $lovelist = array_key_exists('lovelist', $_REQUEST) ? filter_var($_REQUEST['lovelist'], FILTER_VALIDATE_BOOLEAN) : FALSE;
                    $entry = $n->getPublic($lovelist);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'newsfeed' => $entry
                    ];
                } else {
                    $count = array_key_exists('count', $_REQUEST) ? filter_var($_REQUEST['count'], FILTER_VALIDATE_BOOLEAN) : FALSE;

                    if ($count) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'unseencount' => $n->getUnseen($me->getId())
                        ];
                    } else {
                        $ctx = presdef('context', $_REQUEST, NULL);
                        $dist = Newsfeed::DISTANCE;

                        if ($ctx && array_key_exists('distance', $ctx)) {
                            $dist = $ctx['distance'];

                            if ($dist == 'nearby') {
                                $dist = $n->getNearbyDistance($me->getId());
                            }
                        }

                        $dist = intval($dist);

                        $types = presdef('types', $_REQUEST, NULL);

                        list ($users, $items) = $n->getFeed($me->getId(), $dist, $types, $ctx);

                        $ret = [
                            'ret' => 0,
                            'context' => $ctx,
                            'status' => 'Success',
                            'newsfeed' => $items,
                            'users' => $users
                        ];
                    }
                }
                break;
            }

            case 'POST': {
                $message = presdef('message', $_REQUEST, NULL);
                $replyto = pres('replyto', $_REQUEST) ? intval($_REQUEST['replyto']) : NULL;
                $action = presdef('action', $_REQUEST, NULL);
                $reason = presdef('reason', $_REQUEST, NULL);

                if ($action == 'Love') {
                    $n->like();

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'Unlove') {
                    $n->unlike();

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'Report') {
                    $n->report($reason);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'Seen') {
                    $n->seen($me->getId());

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'ReferToWanted') {
                    $n->referToWanted();

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else {
                    $s = new Spam($dbhr, $dbhm);
                    $spammers = $s->getSpammerByUserid($me->getId());
                    if (!$spammers) {
                        $id = $n->create(Newsfeed::TYPE_MESSAGE, $me->getId(), $message, NULL, NULL, $replyto, NULL);
                    }

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'id' => $id
                    ];
                }
                break;
            }

            case 'PATCH': {
                # Can mod own posts or if mod.
                $message = presdef('message', $_REQUEST, NULL);

                $ret = [
                    'ret' => 2,
                    'status' => 'Permission denied'
                ];

                if ($me->isModerator() || ($me->getId() == $n->getPrivate('userid'))) {
                    $n->setPrivate('message', $message);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;
            }

            case 'DELETE': {
                $id = intval(presdef('id', $_REQUEST, NULL));

                $ret = [
                    'ret' => 2,
                    'status' => 'Permission denied'
                ];

                # Can delete own posts or if mod.
                if ($me->isModerator() || ($me->getId() == $n->getPrivate('userid'))) {
                    $n->delete($me->getId(), $id);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;
            }
        }
    }

    return($ret);
}
