<?php
function comment() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));
    $userid = intval(presdef('userid', $_REQUEST, NULL));
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $user1 = $user2 = $user3 = $user4 = $user5 = $user6 = $user7 = $user8 = $user9 = $user10 = $user11 = NULL;

    for ($i = 1; $i <= 11; $i++) {
        ${"user$i"} = presdef("user$i", $_REQUEST, NULL);
    }

    $u = new User($dbhr, $dbhm, $userid);

    # Access control is done inside the calls, rather than in here.
    #
    # TODO There's inconsistency between what is done in the API layer and what is done in the underlying classes
    # which might be bad for maintenance.
    if ($id || $_REQUEST['type'] == 'POST') {
        $ret = [
            'ret' => 2,
            'status' => 'Failed'
        ];

        switch ($_REQUEST['type']) {
            case 'GET': {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'comment' => $u->getComment($id)
                ];

                break;
            }

            case 'POST': {
                $id = $u->addComment($groupid,
                    $user1,
                    $user2,
                    $user3,
                    $user4,
                    $user5,
                    $user6,
                    $user7,
                    $user8,
                    $user9,
                    $user10,
                    $user11);

                if ($id) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'id' => $id
                    ];
                }

                break;
            }

            case 'PUT': {
                $rc = $u->editComment($id,
                    $user1,
                    $user2,
                    $user3,
                    $user4,
                    $user5,
                    $user6,
                    $user7,
                    $user8,
                    $user9,
                    $user10,
                    $user11);

                if ($rc) {
                    $ret = ['ret' => 0, 'status' => 'Success' ];
                }
                break;
            }

            case 'DELETE': {
                $rc = $u->deleteComment($id);

                if ($rc) {
                    $ret = ['ret' => 0, 'status' => 'Success' ];
                }
            }
        }
    } else {
        $ret = [
            'ret' => 2,
            'status' => 'Invalid comment id'
        ];
    }

    return($ret);
}
