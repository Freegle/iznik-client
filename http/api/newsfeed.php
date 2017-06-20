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
                    $entry = $n->getPublic();

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'newsfeed' => $entry
                    ];
                } else {
                    $ctx = presdef('context', $_REQUEST, NULL);
                    list ($users, $items) = $n->getFeed($me->getId(), $ctx);

                    $ret = [
                        'ret' => 0,
                        'context' => $ctx,
                        'status' => 'Success',
                        'newsfeed' => $items,
                        'users' => $users
                    ];
                }
                break;
            }

            case 'POST': {
                $message = presdef('message', $_REQUEST, NULL);
                $replyto = pres('replyto', $_REQUEST) ? intval($_REQUEST['replyto']) : NULL;
                $action = presdef('action', $_REQUEST, NULL);

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
                } else {
                    $id = $n->create($me->getId(), $message, NULL, NULL, $replyto, NULL);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'id' => $id
                    ];
                }
                break;
            }
        }
    }

    return($ret);
}
