<?php
function newsfeed() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

    if ($me) {
        $id = intval(presdef('id', $_REQUEST, NULL));
        $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

        switch ($_REQUEST['type']) {
            case 'GET': {
                if ($id) {
                    # Use the master as we fetch after posting and could otherwise miss it due to replication delay.
                    $n = new Newsfeed($dbhm, $dbhm, $id);
                    $lovelist = array_key_exists('lovelist', $_REQUEST) ? filter_var($_REQUEST['lovelist'], FILTER_VALIDATE_BOOLEAN) : FALSE;
                    $unfollowed = array_key_exists('unfollowed', $_REQUEST) ? filter_var($_REQUEST['unfollowed'], FILTER_VALIDATE_BOOLEAN) : FALSE;
                    $entry = $n->getPublic($lovelist, $unfollowed);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'newsfeed' => $entry
                    ];
                } else {
                    $n = new Newsfeed($dbhr, $dbhm);
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
                $n = new Newsfeed($dbhr, $dbhm, $id);
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
                    $n->refer(Newsfeed::TYPE_REFER_TO_WANTED);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'ReferToOffer') {
                    error_log("Refer to offer");
                    $n->refer(Newsfeed::TYPE_REFER_TO_OFFER);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'ReferToTaken') {
                    $n->refer(Newsfeed::TYPE_REFER_TO_TAKEN);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'ReferToReceived') {
                    $n->refer(Newsfeed::TYPE_REFER_TO_RECEIVED);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'Follow') {
                    $n->follow($myid, $id);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == 'Unfollow') {
                    $n->unfollow($myid, $id);

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
                $n = new Newsfeed($dbhr, $dbhm, $id);
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
                $n = new Newsfeed($dbhr, $dbhm, $id);
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
