<?php
function newsfeed() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

    if ($me) {
        $n = new Newsfeed($dbhr, $dbhm);
        $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

        switch ($_REQUEST['type']) {
            case 'GET': {
                $ctx = presdef('context', $_REQUEST, NULL);
                list ($users, $items) = $n->get($me->getId(), $ctx);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'newsfeed' => $items,
                    'users' => $users
                ];
                break;
            }

            case 'POST': {
                $message = presdef('message', $_REQUEST, NULL);
                $replyto = pres('replyto', $_REQUEST) ? intval($_REQUEST['replyto']) : NULL;

                $id = $n->create($me->getId(), $message, NULL, NULL, $replyto, NULL);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'id' => $id
                ];
                break;
            }
        }
    }

    return($ret);
}
