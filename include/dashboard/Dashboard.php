<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

# This gives us a summary of what we need to know for this user
class Dashboard {
    private $dbhr;
    private $dbhm;
    private $me;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, User $me) {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->me = $me;
    }

    public function get($systemwide, $allgroups, $groupid) {
        $ret = [];

        if ($systemwide && $this->me->getPrivate('systemrole')) {
            # Get a summary of messages across the whole site for the last 30 days
            $mysqltime = date ("Y-m-d", strtotime("Midnight 30 days ago"));
            $sql = "SELECT COUNT(*) AS count, DATE(arrival) AS date FROM `messages_approved` WHERE arrival > ? GROUP BY date ORDER BY date ASC;";
            $ret['messagehistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

            # Show spam rate
            $sql = "SELECT COUNT(*) AS count, DATE(timestamp) AS date FROM `logs` WHERE timestamp > ? AND type = 'Message' AND subtype = 'ClassifiedSpam' GROUP BY date ORDER BY date ASC;";
            $ret['spamhistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

            $mysqltime = date ("Y-m-d", strtotime("Midnight 2 days ago"));

            # Get domain breakdown
            $sql = "SELECT SUBSTRING_INDEX(`fromaddr`, '@', -1) AS domain, COUNT(*) AS count FROM `messages_approved` WHERE arrival > ? GROUP BY domain ORDER BY count DESC LIMIT 10;";
            $ret['domainhistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

            # Get source breakdown
            $sql = "SELECT sourceheader AS source, COUNT(*) AS count FROM `messages_approved` WHERE arrival > ? AND sourceheader IS NOT NULL GROUP BY sourceheader ORDER BY count DESC LIMIT 10;";
            $ret['sourcehistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);
        } else {
            # We want the summaries for one or more groups.  Get the list.
            $groups = [];
            if ($groupid) {
                $groups[] = $groupid;
            } else {
                $membs = $this->me->getMemberships();
                foreach ($membs as $memb) {
                    $groups[] = $memb['id'];
                }
            }

            if (count($groups) > 0) {
                $groups = '(' . implode(',', $groups) . ')';

                $mysqltime = date("Y-m-d", strtotime("Midnight 30 days ago"));
                $sql = "SELECT COUNT(*) AS count, DATE(arrival) AS date FROM `messages_approved` WHERE arrival > ? AND groupid IN $groups GROUP BY date ORDER BY date ASC;";
                $ret['messagehistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

                # Show spam rate
                $sql = "SELECT COUNT(*) AS count, DATE(timestamp) AS date FROM `logs` WHERE timestamp > ?  AND `group` IN $groups AND type = 'Message' AND subtype = 'ClassifiedSpam' GROUP BY date ORDER BY date ASC;";
                $ret['spamhistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

                $mysqltime = date("Y-m-d", strtotime("Midnight 2 days ago"));

                # Get domain breakdown
                $sql = "SELECT SUBSTRING_INDEX(`fromaddr`, '@', -1) AS domain, COUNT(*) AS count FROM `messages_approved` WHERE arrival > ? AND groupid IN $groups GROUP BY domain ORDER BY count DESC LIMIT 10;";
                $ret['domainhistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

                # Get source breakdown
                $sql = "SELECT sourceheader AS source, COUNT(*) AS count FROM `messages_approved` WHERE arrival > ? AND groupid IN $groups AND sourceheader IS NOT NULL GROUP BY sourceheader ORDER BY count DESC LIMIT 10;";
                $ret['sourcehistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);
            }
        }

        return($ret);
    }
}