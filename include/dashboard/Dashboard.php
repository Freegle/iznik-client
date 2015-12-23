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
            $typeq3 = " INNER JOIN groups ON groups.id = memberships.groupid AND groups.type = ? ";
            $params = [ $type, $mysqltime ];
        } else {
            $typeq1 = NULL;
            $typeq2 = NULL;
            $typeq3 = NULL;
            $params = [ $mysqltime ];
        }

        if ($systemwide && $this->me->getPrivate('systemrole')) {
            # Get a summary of messages across the whole site for the last 30 days
            $sql = "SELECT COUNT(*) AS count, DATE(messages.date) AS date FROM messages INNER JOIN messages_groups  ON messages.id = messages_groups.msgid $typeq1 WHERE messages.date > ? AND collection = 'Approved' GROUP BY DATE(messages.date) ORDER BY date ASC;";
            $ret['messagehistory'] = $this->dbhr->preQuery($sql, $params);

            # Show spam rate
            $sql = "SELECT COUNT(*) AS count, DATE(timestamp) AS date FROM `logs` $typeq2 WHERE timestamp > ? AND logs.type = 'Message' AND subtype = 'ClassifiedSpam' GROUP BY date ORDER BY date ASC;";
            $ret['spamhistory'] = $this->dbhr->preQuery($sql, $params);

            # Get domain breakdown
            $sql = "SELECT SUBSTRING_INDEX(`fromaddr`, '@', -1) AS domain, COUNT(*) AS count FROM messages INNER JOIN messages_groups  ON messages.id = messages_groups.msgid $typeq1 WHERE messages.date > ? AND collection = 'Approved' GROUP BY domain ORDER BY count DESC LIMIT 10;";
            $ret['domainhistory'] = $this->dbhr->preQuery($sql, $params);

            # Get source breakdown
            $sql = "SELECT sourceheader AS source, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid $typeq1 WHERE messages.date > ? AND sourceheader IS NOT NULL AND collection = 'Approved' GROUP BY sourceheader ORDER BY count DESC LIMIT 10;";
            $ret['sourcehistory'] = $this->dbhr->preQuery($sql, $params);

            # Get message type breakdown
            $sql = "SELECT messages.type, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid  AND collection = 'Approved' $typeq1 WHERE messages.date > ? AND sourceheader IS NOT NULL AND collection = 'Approved' AND messages.type IS NOT NULL GROUP BY messages.type ORDER BY count DESC;";
            $ret['typehistory'] = $this->dbhr->preQuery($sql, $params);

            # Get delivery settings breakdown, excluding FD and TN which generate their own email.
            $sql = "SELECT yahooDeliveryType, COUNT(*) AS count FROM memberships $typeq3 INNER JOIN users_emails ON memberships.userid = users_emails.userid AND email NOT LIKE 'FBUser%' AND email NOT LIKE '%trashnothing%' GROUP BY yahooDeliveryType ORDER BY count DESC;";
            $ret['deliveryhistory'] = $this->dbhr->preQuery($sql, [ $type ] );
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

                $sql = "SELECT COUNT(*) AS count, DATE(messages.date) AS date FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid $typeq1 WHERE messages.date > ? AND groupid IN $groups AND collection = 'Approved' GROUP BY DATE(messages.date) ORDER BY date ASC;";
                $ret['messagehistory'] = $this->dbhr->preQuery($sql, $params);

                # Show spam rate
                $sql = "SELECT COUNT(*) AS count, DATE(timestamp) AS date FROM `logs` $typeq2 WHERE timestamp > ?  AND `groupid` IN $groups AND logs.type = 'Message' AND subtype = 'ClassifiedSpam' GROUP BY date ORDER BY date ASC;";
                $ret['spamhistory'] = $this->dbhr->preQuery($sql, $params);

                # Get domain breakdown
                $sql = "SELECT SUBSTRING_INDEX(`fromaddr`, '@', -1) AS domain, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid $typeq1 WHERE messages.date > ? AND groupid IN $groups AND collection = 'Approved' GROUP BY domain ORDER BY count DESC LIMIT 10;";
                $ret['domainhistory'] = $this->dbhr->preQuery($sql, $params);

                # Get source breakdown
                $sql = "SELECT sourceheader AS source, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid $typeq1 WHERE messages.date > ? AND groupid IN $groups AND collection = 'Approved' AND sourceheader IS NOT NULL GROUP BY sourceheader ORDER BY count DESC LIMIT 10;";
                $ret['sourcehistory'] = $this->dbhr->preQuery($sql, $params);

                # Get message type breakdown
                $sql = "SELECT messages.type, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND collection = 'Approved' $typeq1 WHERE messages.date > ?  AND groupid IN $groups AND sourceheader IS NOT NULL AND collection = 'Approved' AND messages.type IS NOT NULL GROUP BY messages.type ORDER BY count DESC;";
                $ret['typehistory'] = $this->dbhr->preQuery($sql, $params);

                # Get delivery settings breakdown, excluding FD and TN which generate their own email.
                $sql = "SELECT yahooDeliveryType, COUNT(*) AS count FROM memberships $typeq3 INNER JOIN users_emails ON memberships.userid = users_emails.userid AND email NOT LIKE 'FBUser%' AND email NOT LIKE '%trashnothing%' WHERE groupid IN $groups GROUP BY yahooDeliveryType ORDER BY count DESC;";
                $ret['deliveryhistory'] = $this->dbhr->preQuery($sql, [ $type ]);
            }
        }

        return($ret);
    }
}