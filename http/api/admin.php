<?php
function admin() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = presdef('id', $_REQUEST, NULL);
    $id = $id ? intval($id) : NULL;
    $a = new Admin($dbhr, $dbhm, $id);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            if ($id) {
                # We're not bothered about privacy of admins - people may not be logged in when they see them.
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'admin' => $a->getPublic()
                ];

            }
            break;
        }

        case 'POST': {
            $ret = ['ret' => 1, 'status' => 'Not logged in'];

            if ($me) {
                $ret = ['ret' => 2, 'status' => "Can't create an admin on that group" ];
                $groupid = presdef('groupid', $_REQUEST, NULL);
                $subject = presdef('subject', $_REQUEST, NULL);
                $text = presdef('text', $_REQUEST, NULL);

                if ($me->isModOrOwner($groupid)) {
                    $ret = ['ret' => 3, 'status' => "Create failed" ];
                    $aid = $a->create($groupid, $me->getId(), $subject, $text);

                    if ($aid) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'id' => $aid
                        ];
                    }
                }
            }
        }
    }

    return($ret);
}
