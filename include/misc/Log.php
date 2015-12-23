<?php

require_once(IZNIK_BASE . '/include/utils.php');

# Logging.  This is not guaranteed against loss in the event of serious failure.
class Log
{
    /** @var  $dbhr LoggedPDO */
    var $dbhr;
    /** @var  $dbhm LoggedPDO */
    var $dbhm;

    # Log types must match the enumeration in the logs table.
    const TYPE_GROUP = 'Group';
    const TYPE_USER = 'User';
    const TYPE_MESSAGE = 'Message';
    const TYPE_PLUGIN = 'Plugin';
    const TYPE_CONFIG = 'Config';
    const TYPE_STDMSG = 'StdMsg';
    const TYPE_LOCATION = 'Location';

    const SUBTYPE_CREATED = 'Created';
    const SUBTYPE_DELETED = 'Deleted';
    const SUBTYPE_EDIT = 'Edit';
    const SUBTYPE_APPROVED = 'Approved';
    const SUBTYPE_REJECTED = 'Rejected';
    const SUBTYPE_RECEIVED = 'Received';
    const SUBTYPE_NOTSPAM = 'NotSpam';
    const SUBTYPE_HOLD = 'Hold';
    const SUBTYPE_RELEASE = 'Release';
    const SUBTYPE_FAILURE = 'Failure';
    const SUBTYPE_JOINED = 'Joined';
    const SUBTYPE_REPLIED = 'Replied';
    const SUBTYPE_LOGIN = 'Login';
    const SUBTYPE_CLASSIFIED_SPAM = 'ClassifiedSpam';
    const SUBTYPE_SENT = 'Sent';
    const SUBTYPE_YAHOO_DELIVERY_TYPE = 'YahooDeliveryType';
    const SUBTYPE_YAHOO_POSTING_STATUS = 'YahooPostingStatus';
    const SUBTYPE_ROLE_CHANGE = 'RoleChange';

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
        $rc = $this->dbhm->preExec($sql, $params);
    }
}