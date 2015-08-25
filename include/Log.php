<?php

require_once(IZNIK_BASE . '/include/utils.php');

# Logging.  This is not guaranteed against loss in the event of serious failure.  logs_sql is, though.
class Log
{
    /** @var  $dbhr LoggedPDO */
    var $dbhr;
    /** @var  $dbhm LoggedPDO */
    var $dbhm;

    # Log types must match the enumeration in the logs table.
    const TYPE_GROUP = 'Group';
    const TYPE_MESSAGE = 'Message';

    const SUBTYPE_CREATED = 'Created';
    const SUBTYPE_DELETED = 'Deleted';
    const SUBTYPE_RECEIVED = 'Received';
    const SUBTYPE_FAILURE = 'Failure';
    const SUBTYPE_CLASSIFIED_SPAM = 'ClassifiedSpam';
    const SUBTYPE_SENT = 'Sent';

    function __construct($dbhr, $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function log($params) {
        # TODO background
        # We assume that the parameters passed match fields in the logs table.
        # If they don't, the caller is at fault and should be taken out and shot.
        $p = [];
        foreach ($params as $key => $val) {
            $p[] = ":$key";
        }


        $atts = implode('`,`', array_keys($params));

        $sql = "INSERT INTO logs (`$atts`) VALUES (" .
            implode(',', $p) . ");";

        # No need to check return code - if it doesn't work, nobody dies.
        $this->dbhm->preExec($sql, $params);
    }
}