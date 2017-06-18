<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');

class Stats
{
    /** @var  $dbhr LoggedPDO */
    var $dbhr;
    /** @var  $dbhm LoggedPDO */
    var $dbhm;

    CONST APPROVED_MESSAGE_COUNT = 'ApprovedMessageCount';
    CONST APPROVED_MEMBER_COUNT = 'ApprovedMemberCount';
    CONST SPAM_MESSAGE_COUNT = 'SpamMessageCount';
    CONST SPAM_MEMBER_COUNT = 'SpamMemberCount';
    CONST MESSAGE_BREAKDOWN = 'MessageBreakdown';
    CONST POST_METHOD_BREAKDOWN = 'PostMethodBreakdown';
    CONST YAHOO_DELIVERY_BREAKDOWN = 'YahooDeliveryBreakdown';
    CONST YAHOO_POSTING_BREAKDOWN = 'YahooPostingBreakdown';
    CONST OUR_POSTING_BREAKDOWN = 'OurPostingBreakdown';
    CONST SUPPORTQUERIES_COUNT = 'SupportQueries';
    CONST FEEDBACK_HAPPY = 'Happy';
    CONST FEEDBACK_FINE = 'Fine';
    CONST FEEDBACK_UNHAPPY = 'Unhappy';
    CONST SEARCHES = 'Searches';
    CONST ACTIVITY = 'Activity';
    CONST WEIGHT = 'Weight';

    CONST TYPE_COUNT = 1;
    CONST TYPE_BREAKDOWN = 2;

    CONST HEATMAP_USERS = 'Users';
    CONST HEATMAP_MESSAGES = 'Messages';

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $groupid = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->groupid = $groupid;
    }

    public function setCount($date, $type, $val)
    {
        $this->dbhm->preExec("REPLACE INTO stats (date, groupid, type, count) VALUES (?, ?, ?, ?);",
            [
                $date,
                $this->groupid,
                $type,
                $val
            ]);
    }

    private function setBreakdown($date, $type, $val)
    {
        $this->dbhm->preExec("REPLACE INTO stats (date, groupid, type, breakdown) VALUES (?, ?, ?, ?);",
            [
                $date,
                $this->groupid,
                $type,
                $val
            ]);
    }

    public function generate($date, $type = NULL)
    {
        if ($type === NULL || in_array(Stats::APPROVED_MESSAGE_COUNT, $type)) {
            # Counts are a specific day
            $activity = 0;
            $count = $this->dbhr->preQuery("SELECT COUNT(DISTINCT(messageid)) AS count FROM messages_groups INNER JOIN messages ON messages.id = messages_groups.msgid WHERE groupid = ? AND DATE(messages.arrival) = ? AND collection = ?;",
                [
                    $this->groupid,
                    $date,
                    MessageCollection::APPROVED
                ])[0]['count'];
            $activity += $count;
            $this->setCount($date, Stats::APPROVED_MESSAGE_COUNT, $count);
        }

        if ($type === NULL || in_array(Stats::APPROVED_MEMBER_COUNT, $type)) {
            $this->setCount($date, Stats::APPROVED_MEMBER_COUNT,
                $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM memberships WHERE groupid = ? AND DATE(added) <= ? AND collection = ?;",
                    [
                        $this->groupid,
                        $date,
                        MembershipCollection::APPROVED
                    ])[0]['count']);
        }

        if ($type === NULL || in_array(Stats::SPAM_MESSAGE_COUNT, $type)) {
            $this->setCount($date, Stats::SPAM_MESSAGE_COUNT,
                $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM `logs` WHERE DATE(timestamp) = ?  AND `groupid` = ? AND logs.type = 'Message' AND subtype = 'ClassifiedSpam';",
                    [
                        $date,
                        $this->groupid
                    ])[0]['count']);
        }

        if ($type === NULL || in_array(Stats::SPAM_MEMBER_COUNT, $type)) {
            $this->setCount($date, Stats::SPAM_MEMBER_COUNT,
                $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM `logs` INNER JOIN spam_users ON logs.user = spam_users.userid AND collection = 'Spammer' WHERE groupid = ? AND DATE(logs.timestamp) = ? AND logs.type = 'Group' AND `subtype` = 'Left';",
                    [
                        $this->groupid,
                        $date
                    ])[0]['count']);
        }

        if ($type === NULL || in_array(Stats::SUPPORTQUERIES_COUNT, $type)) {
            $this->setCount($date, Stats::SUPPORTQUERIES_COUNT,
                $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM chat_rooms WHERE DATE(created) = ? AND groupid = ?;",
                    [
                        $date,
                        $this->groupid
                    ])[0]['count']);
        }

        if ($type === NULL || in_array(Stats::FEEDBACK_HAPPY, $type)) {
            $this->setCount($date, Stats::FEEDBACK_HAPPY,
                $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages_outcomes INNER JOIN messages ON messages_outcomes.msgid = messages.id INNER JOIN messages_groups ON messages_groups.msgid = messages.id WHERE DATE(messages_outcomes.timestamp) = ? AND groupid = ? AND happiness = ?;",
                    [
                        $date,
                        $this->groupid,
                        Stats::FEEDBACK_HAPPY
                    ])[0]['count']);
        }

        if ($type === NULL || in_array(Stats::FEEDBACK_FINE, $type)) {
            $this->setCount($date, Stats::FEEDBACK_FINE,
                $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages_outcomes INNER JOIN messages ON messages_outcomes.msgid = messages.id INNER JOIN messages_groups ON messages_groups.msgid = messages.id WHERE DATE(messages_outcomes.timestamp) = ? AND groupid = ? AND happiness = ?;",
                    [
                        $date,
                        $this->groupid,
                        Stats::FEEDBACK_FINE
                    ])[0]['count']);
        }

        if ($type === NULL || in_array(Stats::FEEDBACK_UNHAPPY, $type)) {
            $this->setCount($date, Stats::FEEDBACK_UNHAPPY,
                $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages_outcomes INNER JOIN messages ON messages_outcomes.msgid = messages.id INNER JOIN messages_groups ON messages_groups.msgid = messages.id WHERE DATE(messages_outcomes.timestamp) = ? AND groupid = ? AND happiness = ?;",
                    [
                        $date,
                        $this->groupid,
                        Stats::FEEDBACK_UNHAPPY
                    ])[0]['count']);
        }

        if ($type === NULL || in_array(Stats::POST_METHOD_BREAKDOWN, $type)) {
            # Message breakdowns take the previous 30 days
            $start = date('Y-m-d', strtotime("30 days ago", strtotime($date)));
            $end = date('Y-m-d', strtotime("tomorrow", strtotime($date)));

            $sql = "SELECT sourceheader AS source, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid WHERE messages.arrival >= ? AND messages.arrival < ? AND groupid = ? AND collection = 'Approved' AND sourceheader IS NOT NULL GROUP BY sourceheader;";
            $sources = $this->dbhr->preQuery($sql,
                [
                    $start,
                    $end,
                    $this->groupid
                ]);

            $srcs = [];
            foreach ($sources as $src) {
                $srcs[$src['source']] = $src['count'];
            }

            $this->setBreakdown($date, Stats::POST_METHOD_BREAKDOWN, json_encode($srcs));
        }

        if ($type === NULL || in_array(Stats::MESSAGE_BREAKDOWN, $type)) {
            $sql = "SELECT messages.type, COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND collection = 'Approved' WHERE messages.arrival >= ? AND messages.arrival < ? AND groupid = ? AND collection = 'Approved' AND messages.type IS NOT NULL GROUP BY messages.type;";
            $sources = $this->dbhr->preQuery($sql,
                [
                    $start,
                    $end,
                    $this->groupid
                ]);

            $srcs = [];
            foreach ($sources as $src) {
                $srcs[$src['type']] = $src['count'];
            }

            $this->setBreakdown($date, Stats::MESSAGE_BREAKDOWN, json_encode($srcs));
        }

        if ($type === NULL || in_array(Stats::YAHOO_DELIVERY_BREAKDOWN, $type)) {
            # Settings breakdowns don't have a date restriction
            $sql = "SELECT memberships_yahoo.yahooDeliveryType, COUNT(*) AS count FROM memberships_yahoo INNER JOIN users_emails ON memberships_yahoo.emailid = users_emails.id INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id WHERE email NOT LIKE 'FBUser%' AND email NOT LIKE '%trashnothing%' AND groupid = ? GROUP BY memberships_yahoo.yahooDeliveryType;";
            $sources = $this->dbhr->preQuery($sql,
                [
                    $this->groupid
                ]);

            $srcs = [];
            foreach ($sources as $src) {
                $srcs[$src['yahooDeliveryType']] = $src['count'];
            }

            $this->setBreakdown($date, Stats::YAHOO_DELIVERY_BREAKDOWN, json_encode($srcs));
        }

        if ($type === NULL || in_array(Stats::YAHOO_POSTING_BREAKDOWN, $type)) {
            $sql = "SELECT memberships_yahoo.yahooPostingStatus, COUNT(*) AS count FROM memberships_yahoo INNER JOIN users_emails ON memberships_yahoo.emailid = users_emails.id INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id WHERE email NOT LIKE 'FBUser%' AND email NOT LIKE '%trashnothing%' AND groupid = ? GROUP BY memberships_yahoo.yahooPostingStatus;";
            $sources = $this->dbhr->preQuery($sql,
                [
                    $this->groupid
                ]);

            $srcs = [];
            foreach ($sources as $src) {
                $srcs[$src['yahooPostingStatus']] = $src['count'];
            }

            $this->setBreakdown($date, Stats::YAHOO_POSTING_BREAKDOWN, json_encode($srcs));
        }

        if ($type === NULL || in_array(Stats::OUR_POSTING_BREAKDOWN, $type)) {
            $sql = "SELECT memberships.ourPostingStatus, COUNT(*) AS count FROM memberships WHERE groupid = ? GROUP BY memberships.ourPostingStatus;";
            $sources = $this->dbhr->preQuery($sql,
                [
                    $this->groupid
                ]);

            $srcs = [];
            foreach ($sources as $src) {
                $srcs[$src['ourPostingStatus']] = $src['count'];
            }

            $this->setBreakdown($date, Stats::OUR_POSTING_BREAKDOWN, json_encode($srcs));
        }

        if ($type === NULL || in_array(Stats::SEARCHES, $type)) {
            # Searches need a bit more work.  We're looking for searches which hit this group.
            $searches = $this->dbhr->preQuery("SELECT * FROM search_history WHERE DATE(date) = ?;", [
                $date
            ]);

            $count = 0;
            foreach ($searches as $search) {
                if ($search['groups']) {
                    $groups = explode(',', $search['groups']);
                    if (in_array($this->groupid, $groups, $type)) {
                        $count++;
                    }
                }
            }
    
            $activity += $count;
            $this->setCount($date, Stats::SEARCHES, $count);
        }

        if ($type === NULL || in_array(Stats::WEIGHT, $type)) {
            # Weights also require more work.
            #
            # - Get the messages from the date
            # - For those with a suitable outcome
            #   - if we know a weight, then add it
            #   - if we don't know a weight, assume it's the average weight
            #
            # This will tail off a bit towards the current time as items won't be taken for a while.
            $avg = $this->dbhr->preQuery("SELECT SUM(popularity * weight) / SUM(popularity) AS average FROM items WHERE weight IS NOT NULL AND weight != 0")[0]['average'];
            $sql = "SELECT DISTINCT messages_outcomes.msgid, weight, subject FROM messages_outcomes INNER JOIN messages_groups ON messages_groups.msgid = messages_outcomes.msgid INNER JOIN messages ON messages.id = messages_outcomes.msgid INNER JOIN messages_items ON messages_outcomes.msgid = messages_items.msgid LEFT JOIN items ON items.id = messages_items.itemid WHERE DATE(messages_outcomes.timestamp) = ? AND groupid = ? AND outcome IN ('Taken', 'Received');";
            $msgs = $this->dbhr->preQuery($sql, [
                $date,
                $this->groupid
            ]);

            $weight = 0;
            foreach ($msgs as $msg) {
                #error_log("{$msg['msgid']} {$msg['subject']} {$msg['weight']}");
                $weight += $msg['weight'] ? $msg['weight'] : $avg;
            }
            $this->setCount($date, Stats::WEIGHT, $weight);
        }

        if ($type === NULL || in_array(Stats::ACTIVITY, $type)) {
            $this->setCount($date, Stats::ACTIVITY, $activity);
        }
    }

    public function get($date)
    {
        $stats = $this->dbhr->preQuery("SELECT * FROM stats WHERE date = ? AND groupid = ?;", [ $date, $this->groupid ]);
        $ret = [
                Stats::APPROVED_MESSAGE_COUNT => 0,
                Stats::APPROVED_MEMBER_COUNT => 0,
                Stats::SPAM_MESSAGE_COUNT => 0,
                Stats::SPAM_MEMBER_COUNT => 0,
                Stats::SUPPORTQUERIES_COUNT => 0,
                Stats::FEEDBACK_FINE => 0,
                Stats::FEEDBACK_HAPPY => 0,
                Stats::FEEDBACK_UNHAPPY => 0,
                Stats::SEARCHES => 0,
                Stats::ACTIVITY => 0,
                Stats::WEIGHT => 0,
                Stats::MESSAGE_BREAKDOWN => [],
                Stats::POST_METHOD_BREAKDOWN => [],
                Stats::YAHOO_DELIVERY_BREAKDOWN => [],
                Stats::YAHOO_POSTING_BREAKDOWN => []
        ];

        foreach ($stats as $stat) {
            switch ($stat['type']) {
                case Stats::APPROVED_MESSAGE_COUNT:
                case Stats::APPROVED_MEMBER_COUNT:
                case Stats::SPAM_MESSAGE_COUNT:
                case Stats::SUPPORTQUERIES_COUNT:
                case Stats::FEEDBACK_FINE:
                case Stats::FEEDBACK_HAPPY:
                case Stats::FEEDBACK_UNHAPPY:
                case Stats::SPAM_MEMBER_COUNT:
                case Stats::SEARCHES:
                case Stats::WEIGHT:
                case Stats::ACTIVITY:
                    $ret[$stat['type']] = $stat['count'];
                    break;
                case Stats::MESSAGE_BREAKDOWN:
                case Stats::POST_METHOD_BREAKDOWN:
                case Stats::YAHOO_DELIVERY_BREAKDOWN:
                case Stats::YAHOO_POSTING_BREAKDOWN:
                    $ret[$stat['type']] = json_decode($stat['breakdown'], TRUE);
                    break;
            }
        }

        return ($ret);
    }

    function getMulti($date, $groupids, $startdate = "30 days ago", $enddate = "today", $systemwide = FALSE) {
        # Get stats across multiple groups.
        $me = whoAmI($this->dbhr, $this->dbhm);

        $ret = [];
        $ret['groupids'] = $groupids;
        $start = date('Y-m-d', strtotime($startdate, strtotime($date)));
        #error_log("Start at $start from $startdate");
        $end = date('Y-m-d', strtotime($enddate, strtotime($date)));

        if (!MODTOOLS && $systemwide) {
            # Get a restricted set of stats for performance.
            $types = [
                Stats::ACTIVITY,
                Stats::WEIGHT
            ];
        } else {
            $types = [
                Stats::APPROVED_MESSAGE_COUNT,
                Stats::APPROVED_MEMBER_COUNT,
                Stats::SEARCHES,
                Stats::ACTIVITY,
                Stats::WEIGHT
            ];

            if ($me && ($me->isModerator() || $me->isAdmin())) {
                # Mods can see more info.
                $types = [
                    Stats::APPROVED_MESSAGE_COUNT,
                    Stats::APPROVED_MEMBER_COUNT,
                    Stats::SPAM_MESSAGE_COUNT,
                    Stats::SPAM_MEMBER_COUNT,
                    Stats::SUPPORTQUERIES_COUNT,
                    Stats::FEEDBACK_HAPPY,
                    Stats::FEEDBACK_FINE,
                    Stats::FEEDBACK_UNHAPPY,
                    Stats::SEARCHES,
                    Stats::ACTIVITY,
                    Stats::WEIGHT
                ];
            }
        }

        foreach ($types as $type) {
            $ret[$type] = [];
            #error_log("Check type $type " . "SELECT SUM(count) AS count, date FROM stats WHERE date >= '$start' AND date < '$end' AND groupid IN (" . implode(',', $groupids) . ") AND type = '$type' GROUP BY date;");

            # For many group values it's more efficient to use an index on date and type, so order the query accordingly.
            $sql = count($groupids) > 10 ? ("SELECT SUM(count) AS count, date FROM stats WHERE date >= ? AND date < ? AND type = ? AND groupid IN (" . implode(',', $groupids) . ") GROUP BY date;") : ("SELECT SUM(count) AS count, date FROM stats WHERE date >= ? AND date < ? AND groupid IN (" . implode(',', $groupids) . ") AND type = ? GROUP BY date;");
            $counts = $this->dbhr->preQuery($sql,
                [
                    # Activity stats only start from when we started tracking searches.
                    $type == Stats::ACTIVITY ? '2016-12-21' : $start,
                    $end,
                    $type
                ]);

            foreach ($counts as $count) {
                $ret[$type][] = [ 'date' => $count['date'], 'count' => $count['count']];
            }
        }

        # Breakdowns we have to parse and sum the individual values.  Start from yesterday as we might not have complete
        # data for today.
        $types = [ Stats::MESSAGE_BREAKDOWN ];

        if (MODTOOLS && $me && ($me->isModerator() || $me->isAdmin())) {
            $types = [
                Stats::MESSAGE_BREAKDOWN,
                Stats::YAHOO_POSTING_BREAKDOWN,
                Stats::YAHOO_DELIVERY_BREAKDOWN
            ];
        }

        foreach ($types as $type) {
            $ret[$type] = [];

            $sql = "SELECT breakdown FROM stats WHERE type = ? AND date >= ? AND date < ? AND groupid IN (" . implode(',', $groupids) . ");";
            #error_log("$sql $start $end");
            #error_log("SELECT breakdown FROM stats WHERE type = '$type' AND date >= '$start' AND date < '$end' AND groupid IN (" . implode(',', $groupids) . ");");
            $breakdowns = $this->dbhr->preQuery($sql,
                [
                    $type,
                    $start,
                    $end
                ]);

            foreach ($breakdowns as $breakdown) {
                $b = json_decode($breakdown['breakdown'], TRUE);
                foreach ($b as $key => $val) {
                    $ret[$type][$key] = !array_key_exists($key, $ret[$type]) ? $val : $ret[$type][$key] + $val;
                }
            }
        }

        if (MODTOOLS && $me && ($me->isModerator() || $me->isAdmin())) {
            $sql = "SELECT breakdown FROM stats WHERE type = ? AND date < ? AND groupid IN (" . implode(',', $groupids) . ") ORDER BY date DESC LIMIT 1;";
            #error_log("$sql $end");
            $breakdowns = $this->dbhr->preQuery($sql,
                [
                    Stats::POST_METHOD_BREAKDOWN,
                    $end
                ]);

            foreach ($breakdowns as $breakdown) {
                $b = json_decode($breakdown['breakdown'], TRUE);
                foreach ($b as $key => $val) {
                    $ret[Stats::POST_METHOD_BREAKDOWN][$key] = !array_key_exists($key, $ret[$type]) ? $val : $ret[$type][$key] + $val;
                }
            }
        }

        return($ret);
    }

    public function getHeatmap($type = Stats::HEATMAP_MESSAGES) {
        # We return counts per postcode.  Postcodes on average cover 15 properties, so there is some anonymity.
        # Don't support users yet.
        # $sql = $type == Stats::HEATMAP_USERS ? "SELECT id, name, lat, lng, count FROM locations INNER JOIN (SELECT areaid, COUNT(*) AS count FROM users INNER JOIN locations ON locations.id = users.lastlocation WHERE areaid IS NOT NULL GROUP BY areaid) t ON t.areaid = locations.id WHERE lat IS NOT NULL AND lng IS NOT NULL;" : "SELECT id, name, lat, lng, count FROM locations INNER JOIN (SELECT areaid, COUNT(*) AS count FROM messages INNER JOIN locations ON locations.id = messages.locationid WHERE areaid IS NOT NULL GROUP BY areaid) t ON t.areaid = locations.id WHERE lat IS NOT NULL AND lng IS NOT NULL;";
        $sql = "SELECT id, name, lat, lng, count FROM locations INNER JOIN (SELECT locationid, COUNT(*) AS count FROM messages INNER JOIN locations ON locations.id = messages.locationid WHERE locationid IS NOT NULL AND locations.type = 'Postcode' AND INSTR(locations.name, ' ') GROUP BY locationid) t ON t.locationid = locations.id WHERE lat IS NOT NULL AND lng IS NOT NULL;";
        $areas = $this->dbhr->preQuery($sql);
        return($areas);
    }
}