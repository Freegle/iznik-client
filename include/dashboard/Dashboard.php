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
                $sql = "SELECT COUNT(*) AS count, DATE(arrival) AS date FROM `messages_history` WHERE arrival > ? GROUP BY date ORDER BY date ASC;";
                $msgs = $this->dbhr->preQuery($sql, [$mysqltime]);

                # We get it back as an array - change the keys to the dates.
                $ret['messagehistory'] = [
                    'messages' => []
                ];

                foreach ($msgs as $msg) {
                    $ret['messagehistory'][$msg['date']] = $msg['count'];
                }

                break;
            }
            default : {
                break;
            }
        }

        return($ret);
    }
}