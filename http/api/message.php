<?php
function message() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $collection = presdef('collection', $_REQUEST, Collection::APPROVED);
    $id = intval(presdef('id', $_REQUEST, NULL));
    $reason = presdef('reason', $_REQUEST, NULL);
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $action = presdef('action', $_REQUEST, NULL);
    $subject = presdef('subject', $_REQUEST, NULL);
    $body = presdef('body', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
        case 'DELETE': {
            $m = NULL;
            $m = new Message($dbhr, $dbhm, $id);
            error_log("Looking for message $id found " . $m->getID());

            if (!$m->getID()) {
                $ret = ['ret' => 3, 'status' => 'Message does not exist'];
                $m = NULL;
            } else {
                switch ($collection) {
                    case Collection::APPROVED:
                        break;
                    case Collection::PENDING:
                        if (!$me) {
                            $ret = ['ret' => 1, 'status' => 'Not logged in'];
                            $m = NULL;
                        } else {
                            if (!$me->isModOrOwner($m->getGroups()[0])) {
                                $ret = ['ret' => 2, 'status' => 'Permission denied'];
                                $m = NULL;
                            }
                        }
                        break;
                    case Collection::SPAM:
                        if (!$me) {
                            $ret = ['ret' => 1, 'status' => 'Not logged in'];
                            $m = NULL;
                        } else {
                            if (!$me->isModOrOwner($m->getGroups()[0])) {
                                $ret = ['ret' => 2, 'status' => 'Permission denied'];
                                $m = NULL;
                            }
                        }
                        break;
                }
            }

            if ($m) {
                if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'message' => $m->getPublic()
                    ];
                } else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                    if (!$me->isModOrOwner($m->getGroups()[0])) {
                        $ret = ['ret' => 2, 'status' => 'Permission denied'];
                    } else {
                        $m->delete($reason);
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success'
                        ];
                    }
                }
            }
        }
        break;

        case 'POST': {
            $m = new Message($dbhr, $dbhm, $id);
            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($m && $me && $me->isModOrOwner($groupid)) {
                # Check that this is pending.
                if (!$m->isPending($groupid)) {
                    $ret = ['ret' => 3, 'status' => 'Message is not pending'];
                } else {
                    if ($action == 'Delete') {
                        $m->delete('Deleted by moderator', $groupid);
                    } else if ($action == 'Reject') {
                        $m->reject($groupid, $subject, $body);
                    } else if ($action == 'Approve') {
                        $m->approve($groupid);
                    }

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
            }
        }
    }

    return($ret);
}
