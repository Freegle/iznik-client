<?php

class Message {
    private $dbhr;
    private $dbhm;

    function __construct($dbhr, $dbhm) {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }
}