<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

# This gives us a summary of what we need to know for this user
class Dashboard {
    private $dbhr;
    private $dbhm;
    private $me;
    private $stats;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, User $me) {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->me = $me;
        $this->stats = new Stats($dbhr, $dbhm);
    }

    public function get($systemwide, $allgroups, $groupid, $type) {
        $groupids = [];

        # Get the possible groups.
        if ($systemwide && $this->me->isAdminOrSupport()) {
            $groups = "SELECT id FROM groups WHERE publish = 1;";
            foreach ($groups as $group) {
                $groupids = $group['id'];
            }
        } else if ($groupid) {
            $groupids[] = $groupid;
        } else if ($allgroups) {
            $groupids = $this->me->getModeratorships();
        }

        $groupids = count($groupids) == 0 ? [0] : $groupids;
        if ($type) {
            # Filter by type
            $groups = $this->dbhr->preQuery("SELECT id FROM groups WHERE id IN (" . implode(',', $groupids) . ") AND type = ?;", [ $type ]);
            $groupids = [0];
            foreach ($groups as $group) {
                $groupids[] = $group['id'];
            }
        }

        $ret = $this->stats->getMulti(date ("Y-m-d"), $groupids);

        return($ret);
    }
}