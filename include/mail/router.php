<?php

require_once(BASE_DIR . '/include/utils.php');
require_once(BASE_DIR . '/include/message/IncomingMessage.php');

# This class routes an incoming message
class MailRouter
{
    private $dbhr;
    private $dbhm;

    function __construct($dbhr, $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    function received($msg) {
        $msg = new IncomingMessage($this->dbhr, $this->dbhm);

        # We parse it and save it to the DB.  Then it will get picked up by background
        # processing.
        $msg->parse($msg);
        $msg->save();
    }
}