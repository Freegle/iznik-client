<?php
function messages() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $groupid = presdef('groupid', $_REQUEST, NULL);
    $collection = presdef('collection', $_REQUEST, Collection::APPROVED);
    $start = intval(presdef('start', $_REQUEST, NULL));
    $limit = intval(presdef('limit', $_REQUEST, 100));
    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
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

    return($ret);
}
