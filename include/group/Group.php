<?php

require_once(BASE_DIR . '/include/utils.php');

class Group
{
    private $dbhr;
    private $dbhm;

    function __construct($dbhr, $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function create($shortname) {

    }
}