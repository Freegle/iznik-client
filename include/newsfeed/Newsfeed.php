<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');
require_once(IZNIK_BASE . '/lib/GreatCircle.php');

class Newsfeed extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'timestamp', 'type', 'userid', 'imageid', 'msgid', 'replyto', 'groupid', 'message', 'position');

    /** @var  $log Log */
    private $log;
    var $feed;

    const DISTANCE = 15000;

    const TYPE_MESSAGE = 'Message';

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($userid, $message, $imageid = NULL, $msgid = NULL, $replyto = NULL, $groupid = NULL) {
        $u = User::get($this->dbhr, $this->dbhm, $userid);

        $lid = $u->getPrivate('lastlocation');
        $lat = NULL;
        $pos = 'NULL';

        if ($lid) {
            $l = new Location($this->dbhr, $this->dbhm, $lid);
            $lat = $l->getPrivate('lat');
            $lng = $l->getPrivate('lng');
            $pos = "GeomFromText('POINT($lng $lat)')";
        }

        $this->dbhm->preExec("INSERT INTO newsfeed (`type`, userid, imageid, msgid, replyto, groupid, message, position) VALUES (?, ?, ?, ?, ?, ?, ?, $pos);", [
            Newsfeed::TYPE_MESSAGE,
            $userid,
            $imageid,
            $msgid,
            $replyto,
            $groupid,
            $message
        ]);

        return($this->dbhm->lastInsertId());
    }

    public function get($userid, &$ctx) {
        $u = User::get($this->dbhr, $this->dbhm, $userid);
        $users = [];
        $items = [];

        $lid = $u->getPrivate('lastlocation');

        if ($lid) {
            # We want the newsfeed items which are close to us.
            $l = new Location($this->dbhr, $this->dbhm, $lid);
            $lat = $l->getPrivate('lat');
            $lng = $l->getPrivate('lng');

            # To use the spatial index we need to have a box.
            $ne = GreatCircle::getPositionByDistance(Newsfeed::DISTANCE, 45, $lat, $lng);
            $sw = GreatCircle::getPositionByDistance(Newsfeed::DISTANCE, 225, $lat, $lng);

            $box = "GeomFromText('POLYGON(({$sw['lng']} {$sw['lat']}, {$sw['lng']} {$ne['lat']}, {$ne['lng']} {$ne['lat']}, {$ne['lng']} {$sw['lat']}, {$sw['lng']} {$sw['lat']}))')";
            error_log("Check $lat, $lng box $box");

            # We return most recent first.
            $idq = $ctx ? " AND newsfeed.id > {$ctx['id']}" : '';

            $entries = $this->dbhr->preQuery("SELECT * FROM newsfeed WHERE MBRContains($box, position) $idq ORDER BY id DESC LIMIT 10;");

            foreach ($entries as $entry) {
                if ($entry['userid'] && !array_key_exists($entry['userid'], $users)) {
                    $u = User::get($this->dbhr, $this->dbhm, $entry['userid']);
                    $uctx = NULL;
                    $users[$entry['userid']] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE, FALSE);
                }

                if (pres('msgid', $entry)) {
                    $m = new Message($this->dbhr, $this->dbhm, $entry['msgid']);
                    $entry['refmsg'] = $m->getPublic(FALSE, FALSE);
                }

                $ctx = [ 'id' => $entry['id'] ];
                $items[] = $entry;
            }
        }

        return([$users, $items]);
    }
}