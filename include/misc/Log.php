<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/message/Message.php');


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
    const SUBTYPE_LOGOUT = 'Logout';
    const SUBTYPE_CLASSIFIED_SPAM = 'ClassifiedSpam';
    const SUBTYPE_SUSPECT = 'Suspect';
    const SUBTYPE_SENT = 'Sent';
    const SUBTYPE_YAHOO_DELIVERY_TYPE = 'YahooDeliveryType';
    const SUBTYPE_YAHOO_POSTING_STATUS = 'YahooPostingStatus';
    const SUBTYPE_OUR_POSTING_STATUS = 'OurPostingStatus';
    const SUBTYPE_OUR_EMAIL_FREQUENCY = 'OurEmailFrequency';
    const SUBTYPE_ROLE_CHANGE = 'RoleChange';
    const SUBTYPE_MERGED = 'Merged';
    const SUBTYPE_SPLIT = 'Split';
    const SUBTYPE_LICENSED = 'License';
    const SUBTYPE_LICENSE_PURCHASE = 'LicensePurchase';
    const SUBTYPE_YAHOO_APPLIED = 'YahooApplied';
    const SUBTYPE_YAHOO_CONFIRMED = 'YahooConfirmed';
    const SUBTYPE_YAHOO_JOINED = 'YahooJoined';
    const SUBTYPE_MAILOFF = 'MailOff';
    const SUBTYPE_EVENTSOFF = 'EventsOff';
    const SUBTYPE_NEWSLETTERSOFF = 'NewslettersOff';
    const SUBTYPE_RELEVANTOFF = 'RelevantOff';
    const SUBTYPE_VOLUNTEERSOFF = 'VolunteersOff';
    const SUBTYPE_BOUNCE = 'Bounce';
    const SUBTYPE_SUSPEND_MAIL = 'SuspendMail';
    const SUBTYPE_AUTO_REPOSTED = 'Autoreposted';
    const SUBTYPE_OUTCOME = 'Outcome';
    const SUBTYPE_NOTIFICATIONOFF = 'NotificationOff';
    const SUBTYPE_AUTO_APPROVED = 'Autoapproved';

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

    public function get($types, $subtypes, $groupid, $date, $search, $limit, &$ctx) {
        $groupq = " groupid = $groupid ";
        $typeq = $types ? (" AND logs.type IN ('" . implode("','", $types) . "') ") : '';
        $subtypeq = $subtypes ? (" AND `subtype` IN ('" . implode("','", $subtypes) . "') ") : '';
        $mysqltime = date("Y-m-d", strtotime("midnight $date days ago"));
        $dateq = $date ? " AND timestamp >= '$mysqltime' " : '';

        $searchq = $this->dbhr->quote("%$search%");

        $idq = pres('id', $ctx) ? " AND logs.id < {$ctx['id']} " : '';
        
        # We might have consecutive logs for the same messages/users, so try to speed that up.
        $msgs = [];
        $users = [];

        $g = Group::get($this->dbhr, $this->dbhm, $groupid);
        $onyahoo = $g->onYahoo();

        if (!$search) {
            # This is simple.
            $sql = "SELECT * FROM logs WHERE $groupq $idq $typeq $subtypeq $dateq ORDER BY id DESC LIMIT $limit";
        } else {
            # This is complex.  We want to search in the various user names, the email address, and the message
            # subject.
            $sql = "SELECT logs.* FROM logs 
                LEFT JOIN users ON users.id = logs.user 
                LEFT JOIN messages ON messages.id = logs.msgid
                WHERE $groupq $idq $typeq $subtypeq $dateq AND 
                ((users.firstname LIKE $searchq OR users.lastname LIKE $searchq OR users.fullname LIKE $searchq OR CONCAT(users.firstname, ' ', users.lastname) LIKE $searchq) OR
                 (messages.fromaddr LIKE $searchq OR messages.subject LIKE $searchq))
                ORDER BY logs.id DESC LIMIT $limit";
        }

        error_log($sql);
        $logs = $this->dbhr->preQuery($sql);

        foreach ($logs as &$log) {
            $log['timestamp'] = ISODate($log['timestamp']);
            
            if (pres('user', $log)) {
                $u = pres($log['user'], $users) ? $users[$log['user']] : User::get($this->dbhr, $this->dbhm, $log['user']);
                $users[$log['user']] = $u;
                
                $ctx2 = NULL;

                if ($u->getId() == $log['user']) {
                    $log['user'] = $u->getPublic(FALSE, FALSE, FALSE, $ctx2, FALSE, FALSE, FALSE, FALSE, FALSE);

                    if ($onyahoo) {
                        $log['user']['email'] = $u->getEmailForYahooGroup($groupid)[1];
                    } else {
                        $log['user']['email'] = $u->getEmailPreferred();
                    }
                }
            }

            if (pres('byuser', $log)) {
                $u = pres($log['byuser'], $users) ? $users[$log['byuser']] : User::get($this->dbhr, $this->dbhm, $log['byuser']);
                $users[$log['byuser']] = $u;
                
                $ctx2 = NULL;

                if ($u->getId() == $log['byuser']) {
                    $log['byuser'] = $u->getPublic(FALSE, FALSE, FALSE, $ctx2, FALSE, FALSE, FALSE, FALSE, FALSE);

                    if ($onyahoo) {
                        $log['byuser']['email'] = $u->getEmailForYahooGroup($groupid)[1];
                    }
                }
            }

            if (pres('msgid', $log)) {
                $m = pres($log['msgid'], $msgs) ? $msgs[$log['msgid']] : new Message($this->dbhr, $this->dbhm, $log['msgid']);
                $msgs[$log['msgid']] = $m;
                
                if ($m->getID() == $log['msgid']) {
                    $log['message'] = $m->getPublic(FALSE, FALSE, FALSE);
                }
            }

            if ($log['subtype'] == Log::SUBTYPE_OUTCOME && $log['text']) {
                # Trim the long text leaving just the outcome.
                $p = strpos($log['text'], ' ');

                if ($p) {
                    $log['text'] = substr($log['text'], 0, $p);
                    $log['text'] = str_replace(':', '', $log['text']);
                }
            }

            $ctx['id'] = $log['id'];
        }

        return($logs);
    }
}