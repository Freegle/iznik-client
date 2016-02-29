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
    const TYPE_BULKOP = 'BulkOp';
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
    const SUBTYPE_APPLIED = 'Applied';
    const SUBTYPE_LEFT = 'Left';
    const SUBTYPE_REPLIED = 'Replied';
    const SUBTYPE_MAILED = 'Mailed';
    const SUBTYPE_LOGIN = 'Login';
    const SUBTYPE_CLASSIFIED_SPAM = 'ClassifiedSpam';
    const SUBTYPE_SUSPECT = 'Suspect';
    const SUBTYPE_SENT = 'Sent';
    const SUBTYPE_YAHOO_DELIVERY_TYPE = 'YahooDeliveryType';
    const SUBTYPE_YAHOO_POSTING_STATUS = 'YahooPostingStatus';
    const SUBTYPE_ROLE_CHANGE = 'RoleChange';
    const SUBTYPE_MERGED = 'Merged';
    const SUBTYPE_SPLIT = 'Split';
    const SUBTYPE_LICENSED = 'License';
    const SUBTYPE_LICENSE_PURCHASE = 'LicensePurchase';

    function __construct($dbhr, $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function log($params) {
        # We assume that the parameters passed match fields in the logs table.
        # If they don't, the caller is at fault and should be taken out and shot.
        $q = [];
        foreach ($params as $key => $val) {
            $q[] = $this->dbhm->quote($val);
        }

        $atts = implode('`,`', array_keys($params));
        $vals = implode(',', $q);

        $sql = "INSERT INTO logs (`$atts`) VALUES ($vals);";

        # No need to check return code - if it doesn't work, nobody dies.
        $this->dbhm->background($sql);
    }
}