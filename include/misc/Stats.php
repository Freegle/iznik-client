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

    CONST TYPE_COUNT = 1;
    CONST TYPE_BREAKDOWN = 2;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $groupid = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->groupid = $groupid;
    }

    private function setCount($date, $type, $val)
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

    public function generate($date)
    {
        # Counts are a specific day
        $this->setCount($date, Stats::APPROVED_MESSAGE_COUNT,
            $this->dbhr->preQuery("SELECT COUNT(DISTINCT(messageid)) AS count FROM messages_groups INNER JOIN messages ON messages.id = messages_groups.msgid WHERE groupid = ? AND DATE(messages.arrival) = ? AND collection = ?;",
                [
                    $this->groupid,
                    $date,
                    MessageCollection::APPROVED
                ])[0]['count']);
        $this->setCount($date, Stats::APPROVED_MEMBER_COUNT,
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM memberships WHERE groupid = ? AND DATE(added) <= ? AND collection = ?;",
                [
                    $this->groupid,
                    $date,
                    MembershipCollection::APPROVED
                ])[0]['count']);
        $this->setCount($date, Stats::SPAM_MESSAGE_COUNT,
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM `logs` WHERE DATE(timestamp) = ?  AND `groupid` = ? AND logs.type = 'Message' AND subtype = 'ClassifiedSpam';",
                [
                    $date,
                    $this->groupid
                ])[0]['count']);
        $this->setCount($date, Stats::SPAM_MEMBER_COUNT,
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM `logs` INNER JOIN spam_users ON logs.user = spam_users.userid AND collection = 'Spammer' WHERE groupid = ? AND DATE(logs.timestamp) = ? AND logs.type = 'Group' AND `subtype` = 'Left';",
                [
                    $this->groupid,
                    $date
                ])[0]['count']);
        $this->setCount($date, Stats::SUPPORTQUERIES_COUNT,
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM chat_rooms WHERE DATE(created) = ? AND groupid = ?;",
                [
                    $date,
                    $this->groupid
                ])[0]['count']);
        $this->setCount($date, Stats::SUPPORTQUERIES_COUNT,
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM chat_rooms WHERE DATE(created) = ? AND groupid = ?;",
                [
                    $date,
                    $this->groupid
                ])[0]['count']);
        $this->setCount($date, Stats::FEEDBACK_HAPPY,
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages_outcomes INNER JOIN messages ON messages_outcomes.msgid = messages.id INNER JOIN messages_groups ON messages_groups.msgid = messages.id WHERE DATE(messages_outcomes.timestamp) = ? AND groupid = ? AND happiness = ?;",
                [
                    $date,
                    $this->groupid,
                    Stats::FEEDBACK_HAPPY
                ])[0]['count']);
        $this->setCount($date, Stats::FEEDBACK_FINE,
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages_outcomes INNER JOIN messages ON messages_outcomes.msgid = messages.id INNER JOIN messages_groups ON messages_groups.msgid = messages.id WHERE DATE(messages_outcomes.timestamp) = ? AND groupid = ? AND happiness = ?;",
                [
                    $date,
                    $this->groupid,
                    Stats::FEEDBACK_FINE
                ])[0]['count']);
        $this->setCount($date, Stats::FEEDBACK_UNHAPPY,
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages_outcomes INNER JOIN messages ON messages_outcomes.msgid = messages.id INNER JOIN messages_groups ON messages_groups.msgid = messages.id WHERE DATE(messages_outcomes.timestamp) = ? AND groupid = ? AND happiness = ?;",
                [
                    $date,
                    $this->groupid,
                    Stats::FEEDBACK_UNHAPPY
                ])[0]['count']);

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

    function getMulti($date, $groupids, $enddate = "today") {
        # Get stats across multiple groups.
        #
        # Stats we want a value for each of the last month.
        $ret = [];
        $ret['groupids'] = $groupids;
        $start = date('Y-m-d', strtotime("30 days ago", strtotime($date)));
        $end = date('Y-m-d', strtotime($enddate, strtotime($date)));

        foreach ([Stats::APPROVED_MESSAGE_COUNT, Stats::APPROVED_MEMBER_COUNT, Stats::SPAM_MESSAGE_COUNT, Stats::SPAM_MEMBER_COUNT, Stats::SUPPORTQUERIES_COUNT, Stats::FEEDBACK_HAPPY, Stats::FEEDBACK_FINE, Stats::FEEDBACK_UNHAPPY] as $type) {
            $ret[$type] = [];

            $counts = $this->dbhr->preQuery("SELECT SUM(count) AS count, date FROM stats WHERE date >= ? AND date < ? AND groupid IN (" . implode(',', $groupids) . ") AND type = ? GROUP BY date;",
                [
                    $start,
                    $end,
                    $type
                ]);
            foreach ($counts as $count) {
                $ret[$type][] = [ 'date' => $count['date'], 'count' => $count['count']];
            }
        }

        # Breakdowns we have to parse and sum the individual values.  Start from yesterday as we might not have complete
        # data for today.
        $start = date('Y-m-d', strtotime("yesterday", strtotime($date)));

        foreach ([Stats::MESSAGE_BREAKDOWN, Stats::POST_METHOD_BREAKDOWN, Stats::YAHOO_POSTING_BREAKDOWN, Stats::YAHOO_DELIVERY_BREAKDOWN] as $type) {
            $ret[$type] = [];

            $sql = "SELECT breakdown FROM stats WHERE date = ? AND groupid IN (" . implode(',', $groupids) . ") AND type = ?;";
            $breakdowns = $this->dbhr->preQuery($sql,
                [
                    $start,
                    $type
                ]);

            foreach ($breakdowns as $breakdown) {
                $b = json_decode($breakdown['breakdown'], TRUE);
                foreach ($b as $key => $val) {
                    $ret[$type][$key] = !array_key_exists($key, $ret[$type]) ? $val : $ret[$type][$key] + $val;
                }
            }
        }

        return($ret);
    }
}