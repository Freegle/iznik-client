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

    public function get() {
        $ret = [];

        switch ($this->me->getPrivate('systemrole')) {
            case User::ROLE_ADMIN: {
                # Get a summary of messages across the whole site for the last 30 days
                $mysqltime = date ("Y-m-d", strtotime("Midnight 30 days ago"));
                $sql = "SELECT COUNT(*) AS count, DATE(arrival) AS date FROM `messages_approved` WHERE arrival > ? GROUP BY date ORDER BY date ASC;";
                $ret['messagehistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

                # Show spam rate
                $sql = "SELECT COUNT(*) AS count, DATE(timestamp) AS date FROM `logs` WHERE timestamp > ? AND type = 'Message' AND subtype = 'ClassifiedSpam' GROUP BY date ORDER BY date ASC;";
                $ret['spamhistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

                # Get domain breakdown
                $sql = "SELECT SUBSTRING_INDEX(`fromaddr`, '@', -1) AS domain, COUNT(*) AS count FROM `messages_approved` WHERE arrival > ? GROUP BY domain ORDER BY count DESC LIMIT 10;";
                $ret['domainhistory'] = $this->dbhr->preQuery($sql, [$mysqltime]);

                break;
            }
            default : {
                break;
            }
        }

        return($ret);
    }
}