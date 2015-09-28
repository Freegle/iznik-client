<?php
function message() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $collection = presdef('collection', $_REQUEST, Collection::APPROVED);
    $id = intval(presdef('id', $_REQUEST, NULL));
    $reason = presdef('reason', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $m = NULL;

    switch ($collection) {
        case Collection::APPROVED:
            $m = new ApprovedMessage($dbhr, $dbhm, $id);
            break;
        case Collection::PENDING:
            if (!$me) {
                $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];
            } else {
                $m = new PendingMessage($dbhr, $dbhm, $id);
                if (!$me->isModOrOwner($m->getGroupID())) {
                    $ret = [ 'ret' => 2, 'status' => 'Permission denied' ];
                    $m = NULL;
                }
            }
            break;
        case Collection::SPAM:
            if (!$me) {
                $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];
            } else {
                $m = new SpamMessage($dbhr, $dbhm, $id);
                if (!$me->isModOrOwner($m->getGroupID())) {
                    $ret = [ 'ret' => 2, 'status' => 'Permission denied' ];
                    $m = NULL;
                }
            }
            break;
    }

    if ($m) {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'message' => $m->getPublic()
            ];
        } else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            if (!$me->isModOrOwner($m->getGroupID())) {
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

    return($ret);
}
