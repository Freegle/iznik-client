<?php
function correlate() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $collection = presdef('collection', $_REQUEST, Collection::APPROVED);
    $messages = presdef('messages', $_REQUEST, NULL);
    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

        if ($me) {
            # Check if we're logged in and have rights.
            $g = new Group($dbhr, $dbhm, $groupid);
            $ret = [ 'ret' => 3, 'status' => 'Permission denied' ];

            if ($me->isModOrOwner($groupid)) {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'missingonserver' => $g->correlate($collection, $messages)
                ];
            }
        }
    }

    return($ret);
}
