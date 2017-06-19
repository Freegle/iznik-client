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

    const DISTANCE = 0;
//    const DISTANCE = 15000;

    const TYPE_MESSAGE = 'Message';

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($dbhr, $dbhm);

        $this->fetch($dbhr, $dbhm, $id, 'newsfeed', 'feed', $this->publicatts);
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

        $id = $this->dbhm->lastInsertId();

        if ($id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'newsfeed', 'feed', $this->publicatts);
        }

        return($id);
    }

    public function getPublic() {
        $atts = parent::getPublic();
        $users = [];

        $this->fillIn($atts, $users);
        $atts['user'] = array_pop($users);
        unset($atts['userid']);

        return($atts);
    }

    private function fillIn(&$entry, &$users) {
        unset($entry['position']);

        if ($entry['userid'] && !array_key_exists($entry['userid'], $users)) {
            $u = User::get($this->dbhr, $this->dbhm, $entry['userid']);
            $uctx = NULL;
            $users[$entry['userid']] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE, FALSE);
            $users[$entry['userid']]['publiclocation'] = $u->getPublicLocation();
        }

        if (pres('msgid', $entry)) {
            $m = new Message($this->dbhr, $this->dbhm, $entry['msgid']);
            $entry['refmsg'] = $m->getPublic(FALSE, FALSE);
        }

        $entry['timestamp'] = ISODate($entry['timestamp']);

        $entry['loved'] = FALSE;
        $entry['loves'] = 0;
    }

    public function getFeed($userid, &$ctx) {
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

            # We return most recent first.
            $idq = $ctx ? "newsfeed.id > {$ctx['id']}" : 'newsfeed.id > 0';
            $first = Newsfeed::DISTANCE ? "MBRContains($box, position) AND $idq" : $idq;

            $sql = "SELECT * FROM newsfeed WHERE $first AND replyto IS NULL ORDER BY id DESC LIMIT 10;";
            error_log($sql);
            $entries = $this->dbhr->preQuery($sql);

            foreach ($entries as &$entry) {
                $this->fillIn($entry, $users);
                $replies = $this->dbhr->preQuery("SELECT * FROM newsfeed WHERE replyto = ? ORDER BY id ASC;", [
                    $entry['id']
                ]);

                $entry['replies'] = [];
                foreach ($replies as &$reply) {
                    $this->fillIn($reply, $users);
                    $entry['replies'][] = $reply;
                }

                $ctx = [ 'id' => $entry['id'] ];
                $items[] = $entry;
            }
        }

        return([$users, $items]);
    }
}