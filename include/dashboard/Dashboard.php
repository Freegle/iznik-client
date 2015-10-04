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

    public function get($systemwide, $allgroups, $groupid, $type) {
        $ret = [];

        $mysqltime = date ("Y-m-d", strtotime("Midnight 30 days ago"));

        if ($type) {
            $typeq1 = " INNER JOIN groups ON groups.id = messages_groups.groupid AND groups.type = ? ";
            $typeq2 = " INNER JOIN groups ON groups.id = logs.groupid AND groups.type = ? ";
            $params = [ $type, $mysqltime ];
        } else {
            $typeq1 = NULL;
            $typeq2 = NULL;
            $params = [ $mysqltime ];
        }

        if ($systemwide && $this->me->getPrivate('systemrole')) {
            # Get a summary of messages across the whole site for the last 30 days
            $sql = "SELECT COUNT(*) AS count, DATE(messages.arrival) AS date FROM messages INNER JOIN messages_groups  ON messages.id = messages_groups.msgid $typeq1 WHERE messages.arrival > ? AND collection = 'Approved' GROUP BY DATE(messages.arrival) ORDER BY date ASC;";
            $ret['messagehistory'] = $this->dbhr->preQuery($sql, $params);

            # Show spam rate
            $sql = "SELECT COUNT(*) AS count, DATE(timestamp) AS date FROM `logs` $typeq2 WHERE timestamp > ? AND logs.type = 'Message' AND subtype = 'ClassifiedSpam' GROUP BY date ORDER BY date ASC;";
            $ret['spamhistory'] = $this->dbhr->preQuery($sql, $params);

            # Get domain breakdown
            $sql = "SELECT SUBSTRING_INDEX(`fromaddr`, '@', -1) AS domain, COUNT(*) AS count FROM messages INNER JOIN messages_groups  ON messages.id = messages_groups.msgid $typeq1 WHERE messages.arrival > ? AND collection = 'Approved' GROUP BY domain ORDER BY count DESC LIMIT 10;";
            $ret['domainhistory'] = $this->dbhr->preQuery($sql, $params);

            # Get source breakdown
            $sql = "SELECT sourceheader AS source, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid $typeq1 WHERE messages.arrival > ? AND sourceheader IS NOT NULL AND collection = 'Approved' GROUP BY sourceheader ORDER BY count DESC LIMIT 10;";
            $ret['sourcehistory'] = $this->dbhr->preQuery($sql, $params);
        } else {
            # We want the summaries for one or more groups.  Get the list.
            $groups = [];
            $membs = $this->me->getMemberships();
            foreach ($membs as $memb) {
                # We want groups of the appropriate type on which we are a mod or owner
                if (($memb['role'] == User::ROLE_OWNER || $memb['role'] == User::ROLE_MODERATOR) &&
                    (!$type || $memb['type'] == $type)) {
                    if ($groupid) {
                        if ($memb['id'] == $groupid) {
                            # We have asked for stats on a group
                            $groups[] = $groupid;
                        }
                    } else if ($allgroups) {
                        # We want all groups that we are a mod or owner on.
                        $groups[] = $memb['id'];
                    }
                }
            }

            if (count($groups) > 0) {
                $groups = '(' . implode(',', $groups) . ')';

                $sql = "SELECT COUNT(*) AS count, DATE(messages.arrival) AS date FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid $typeq1 WHERE messages.arrival > ? AND groupid IN $groups AND collection = 'Approved' GROUP BY DATE(messages.arrival) ORDER BY date ASC;";
                $ret['messagehistory'] = $this->dbhr->preQuery($sql, $params);

                # Show spam rate
                $sql = "SELECT COUNT(*) AS count, DATE(timestamp) AS date FROM `logs` $typeq2 WHERE timestamp > ?  AND `groupid` IN $groups AND logs.type = 'Message' AND subtype = 'ClassifiedSpam' GROUP BY date ORDER BY date ASC;";
                $ret['spamhistory'] = $this->dbhr->preQuery($sql, $params);

                # Get domain breakdown
                $sql = "SELECT SUBSTRING_INDEX(`fromaddr`, '@', -1) AS domain, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid $typeq1 WHERE messages.arrival > ? AND groupid IN $groups AND collection = 'Approved' GROUP BY domain ORDER BY count DESC LIMIT 10;";
                $ret['domainhistory'] = $this->dbhr->preQuery($sql, $params);

                # Get source breakdown
                $sql = "SELECT sourceheader AS source, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid $typeq1 WHERE messages.arrival > ? AND groupid IN $groups  AND collection = 'Approved' AND sourceheader IS NOT NULL GROUP BY sourceheader ORDER BY count DESC LIMIT 10;";
                $ret['sourcehistory'] = $this->dbhr->preQuery($sql, $params);
            }
        }

        return($ret);
    }
}