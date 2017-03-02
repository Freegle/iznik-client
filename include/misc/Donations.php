<?php

require_once(IZNIK_BASE . '/include/utils.php');

class Donations
{
    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function get() {
        $ret = [
            'target' => DONATION_TARGET
        ];

        $mysqltime = date("Y-m-d", strtotime('1st January this year'));

        $totals = $this->dbhr->preQuery("SELECT SUM(GrossAmount) AS raised FROM users_donations AS raised WHERE timestamp >= ?;", [
            $mysqltime
        ]);
        $ret['raised'] = $totals[0]['raised'];
        return($ret);
    }
}