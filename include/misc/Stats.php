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
            $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages_groups WHERE groupid = ? AND DATE(arrival) = ? AND collection = ?;",
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
        $sql = "SELECT yahooDeliveryType, COUNT(*) AS count FROM memberships INNER JOIN users_emails ON memberships.userid = users_emails.userid AND email NOT LIKE 'FBUser%' AND email NOT LIKE '%trashnothing%' WHERE groupid = ? GROUP BY yahooDeliveryType;";
        $sources = $this->dbhr->preQuery($sql,
            [
                $this->groupid
            ]);

        $srcs = [];
        foreach ($sources as $src) {
            $srcs[$src['yahooDeliveryType']] = $src['count'];
        }

        $this->setBreakdown($date, Stats::YAHOO_DELIVERY_BREAKDOWN, json_encode($srcs));

        $sql = "SELECT yahooPostingStatus, COUNT(*) AS count FROM memberships INNER JOIN users_emails ON memberships.userid = users_emails.userid AND email NOT LIKE 'FBUser%' AND email NOT LIKE '%trashnothing%' WHERE groupid = ? GROUP BY yahooPostingStatus;";
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

    public function get($date)
    {
        $stats = $this->dbhr->preQuery("SELECT * FROM stats WHERE date = ?;", [ $date ]);
        $ret = [
                Stats::APPROVED_MESSAGE_COUNT => 0,
                Stats::APPROVED_MEMBER_COUNT => 0,
                Stats::SPAM_MESSAGE_COUNT => 0,
                Stats::SPAM_MEMBER_COUNT => 0,
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

    function getMulti($date, $groupids) {
        # Get stats across multiple groups.
        #
        # Stats we want a value for each of the last month.
        $ret = [];
        $ret['groupids'] = $groupids;
        $start = date('Y-m-d', strtotime("30 days ago", strtotime($date)));
        $end = date('Y-m-d', strtotime("today", strtotime($date)));

        foreach ([Stats::APPROVED_MESSAGE_COUNT, Stats::APPROVED_MEMBER_COUNT, Stats::SPAM_MESSAGE_COUNT, Stats::SPAM_MEMBER_COUNT] as $type) {
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

        # Breakdowns we have to parse and sum the individual values.
        foreach ([Stats::MESSAGE_BREAKDOWN, Stats::POST_METHOD_BREAKDOWN, Stats::YAHOO_POSTING_BREAKDOWN, Stats::YAHOO_DELIVERY_BREAKDOWN] as $type) {
            $ret[$type] = [];

            $breakdowns = $this->dbhr->preQuery("SELECT breakdown FROM stats WHERE date = ? AND groupid IN (" . implode(',', $groupids) . ") AND type = ?;",
                [
                    $date,
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