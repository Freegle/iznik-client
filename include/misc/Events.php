<?php

require_once(IZNIK_BASE . '/include/utils.php');

class Events {
    private $dbhr;
    private $dbhm;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm) {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function record($id, $sessid, $route, $target, $action, $timestamp, $posx, $posy, $viewx, $viewy, $data) {
        # TODO POST protection will stop blatant hacks but URLs with timestamps would get through.
        $timestamp = $timestamp ? ($timestamp * 0.001) : 'NULL';
        $posx = $posx ? $posx : 'NULL';
        $posy = $posy ? $posy : 'NULL';
        $data = $data ? $data: 'NULL';
        $lastip = presdef('REMOTE_ADDR', $_SERVER, 'NULL');

        $sql = "INSERT IGNORE INTO logs_events (`userid`, `sessionid`, `timestamp`, `clienttimestamp`, `route`, `target`, `event`, `posx`, `posy`, `viewx`, `viewy`, `data`, `ip`) VALUES ($id, " . $this->dbhr->quote($sessid) . ", CURTIME(3), FROM_UNIXTIME($timestamp), " . $this->dbhr->quote($route) . ", " . $this->dbhr->quote($target) . ", " . $this->dbhr->quote($action) . ", $posx, $posy, $viewx, $viewy, " . $this->dbhr->quote($data) . ", " . $this->dbhr->quote($lastip) . ");";
        $this->dbhm->background($sql);
    }
}