<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');
require_once(IZNIK_BASE . '/include/group/Volunteering.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');
require_once(IZNIK_BASE . '/lib/GreatCircle.php');

class Newsfeed extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'timestamp', 'type', 'userid', 'imageid', 'msgid', 'replyto', 'groupid', 'eventid', 'volunteeringid', 'publicityid', 'message', 'position');

    /** @var  $log Log */
    private $log;
    var $feed;

    const DISTANCE = 15000;

    const TYPE_MESSAGE = 'Message';
    const TYPE_COMMUNITY_EVENT = 'CommunityEvent';
    const TYPE_VOLUNTEER_OPPORTUNITY = 'VolunteerOpportunity';
    const TYPE_CENTRAL_PUBLICITY = 'CentralPublicity';

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($dbhr, $dbhm);

        $this->fetch($dbhr, $dbhm, $id, 'newsfeed', 'feed', $this->publicatts);
    }

    public function create($type, $userid, $message, $imageid = NULL, $msgid = NULL, $replyto = NULL, $groupid = NULL, $eventid = NULL, $volunteeringid = NULL, $publicityid = NULL) {
        $u = User::get($this->dbhr, $this->dbhm, $userid);

        $lid = $u->getPrivate('lastlocation');
        $lat = NULL;
        $id = NULL;

        if ($lid) {
            # Only put it in the newsfeed if we have a location, otherwise we wouldn't show it.
            $l = new Location($this->dbhr, $this->dbhm, $lid);
            $lat = $l->getPrivate('lat');
            $lng = $l->getPrivate('lng');
            $pos = "GeomFromText('POINT($lng $lat)')";

            $this->dbhm->preExec("INSERT INTO newsfeed (`type`, userid, imageid, msgid, replyto, groupid, eventid, volunteeringid, publicityid, message, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, $pos);", [
                $type,
                $userid,
                $imageid,
                $msgid,
                $replyto,
                $groupid,
                $eventid,
                $volunteeringid,
                $publicityid,
                $message
            ]);

            $id = $this->dbhm->lastInsertId();

            if ($id) {
                $this->fetch($this->dbhr, $this->dbhm, $id, 'newsfeed', 'feed', $this->publicatts);
            }
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
        $use = TRUE;

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

        if (pres('eventid', $entry)) {
            $e = new CommunityEvent($this->dbhr, $this->dbhm, $entry['eventid']);
            $use = FALSE;
            if (!$e->getPrivate('pending') && !$e->getPrivate('deleted')) {
                $use = TRUE;
                $entry['communityevent'] = $e->getPublic();
            }
        }

        if (pres('volunteeringid', $entry)) {
            $v = new Volunteering($this->dbhr, $this->dbhm, $entry['volunteeringid']);
            $use = FALSE;
            if (!$v->getPrivate('pending') && !$v->getPrivate('deleted')) {
                $use = TRUE;
                $entry['volunteering'] = $v->getPublic();
            }
        }

        $entry['timestamp'] = ISODate($entry['timestamp']);

        $me = whoAmI($this->dbhr, $this->dbhm);

        $likes = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM newsfeed_likes WHERE newsfeedid = ?;", [
            $entry['id']
        ], FALSE, FALSE);

        $entry['loves'] = $likes[0]['count'];
        $entry['loved'] = FALSE;

        if ($me) {
            $likes = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM newsfeed_likes WHERE newsfeedid = ? AND userid = ?;", [
                $entry['id'],
                $me->getId()
            ], FALSE, FALSE);
            $entry['loved'] = $likes[0]['count'] > 0;
        }

        return($use);
    }

    public function getFeed($userid, $dist = Newsfeed::DISTANCE, &$ctx) {
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
            $ne = GreatCircle::getPositionByDistance($dist, 45, $lat, $lng);
            $sw = GreatCircle::getPositionByDistance($dist, 225, $lat, $lng);

            $box = "GeomFromText('POLYGON(({$sw['lng']} {$sw['lat']}, {$sw['lng']} {$ne['lat']}, {$ne['lng']} {$ne['lat']}, {$ne['lng']} {$sw['lat']}, {$sw['lng']} {$sw['lat']}))')";

            # We return most recent first.
            $idq = pres('id', $ctx) ? "newsfeed.id < {$ctx['id']}" : 'newsfeed.id > 0';
            $first = $dist ? "MBRContains($box, position) AND $idq" : $idq;

            $sql = "SELECT * FROM newsfeed WHERE $first AND replyto IS NULL ORDER BY id DESC LIMIT 5;";
            error_log($sql);
            $entries = $this->dbhr->preQuery($sql);

            foreach ($entries as &$entry) {
                $use = $this->fillIn($entry, $users);

                if ($use) {
                    $replies = $this->dbhr->preQuery("SELECT * FROM newsfeed WHERE replyto = ? ORDER BY id ASC;", [
                        $entry['id']
                    ]);

                    $entry['replies'] = [];
                    foreach ($replies as &$reply) {
                        $this->fillIn($reply, $users);
                        $entry['replies'][] = $reply;
                    }

                    $items[] = $entry;
                }

                $ctx = [
                    'id' => $entry['id'],
                    'distance' => $dist
                ];
            }
        }

        return([$users, $items]);
    }

    public function like() {
        $me = whoAmI($this->dbhr, $this->dbhm);
        if ($me) {
            $this->dbhm->preExec("INSERT IGNORE INTO newsfeed_likes (newsfeedid, userid) VALUES (?,?);", [
                $this->id,
                $me->getId()
            ]);
        }
    }

    public function unlike() {
        $me = whoAmI($this->dbhr, $this->dbhm);
        if ($me) {
            $this->dbhm->preExec("DELETE FROM newsfeed_likes WHERE newsfeedid = ? AND userid = ?;", [
                $this->id,
                $me->getId()
            ]);
        }
    }
}