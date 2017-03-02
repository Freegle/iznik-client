<?php
function plugin() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $p = new Plugin($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    if (!$me) {
        $ret = ['ret' => 1, 'status' => 'Not logged in'];
    } else {
        switch ($_REQUEST['type']) {
            case 'GET': {
                $groups = $me->getModeratorships();
                $work = [];

                foreach ($groups as $group) {
                    # We only want to include work if we are an active mod on the group.
                    if ($me->activeModForGroup($group)) {
                        $work = array_merge($work, $p->get($group));
                    }
                }

                $b = new BulkOp($dbhr, $dbhm);
                $opsdue = $b->checkDue();

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'plugin' => $work,
                    'bulkops' => $opsdue
                ];
                break;
            }

            case 'DELETE': {
                $p->delete($id);
                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
                break;
            }
        }
    }

    return($ret);
}
