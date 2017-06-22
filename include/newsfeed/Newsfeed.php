<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');
require_once(IZNIK_BASE . '/include/group/Volunteering.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');
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
        $id = NULL;

        $u = User::get($this->dbhr, $this->dbhm, $userid);
        list($lat, $lng) = $userid ? $u->getLatLng() : [ NULL, NULL ];
        error_log("$lat, $lng for $userid");

        if ($lat || $lng || $type == Newsfeed::TYPE_CENTRAL_PUBLICITY) {
            # Only put it in the newsfeed if we have a location, otherwise we wouldn't show it.
            $pos = ($lat || $lng) ? "GeomFromText('POINT($lng $lat)')" : "GeomFromText('POINT(-2.5209 53.9450)')";

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

                if ($replyto) {
                    $origs = $this->dbhr->preQuery("SELECT * FROM newsfeed WHERE id = ?;", [ $replyto ]);
                    foreach ($origs as $orig) {
                        # Comment on thread.  We want to notify the original poster and anyone else who
                        # has commented on this thread.
                        $n = new Notifications($this->dbhr, $this->dbhm);
                        $n->add($userid, $orig['userid'], Notifications::TYPE_COMMENT_ON_YOUR_POST, $id);

                        $commenters = $this->dbhr->preQuery("SELECT DISTINCT userid FROM newsfeed WHERE replyto = ? AND userid != ?;", [
                            $replyto,
                            $orig['userid']
                        ]);

                        foreach ($commenters as $commenter) {
                            $n->add($userid, $commenter['userid'], Notifications::TYPE_COMMENT_ON_COMMENT, $id);
                        }
                    }
                }
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

        foreach ($atts['replies'] as &$reply) {
            $u = User::get($this->dbhr, $this->dbhm, $reply['userid']);
            $reply['user'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE, FALSE);
        }

        return($atts);
    }

    private function fillIn(&$entry, &$users, $checkreplies = TRUE) {
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

        if (pres('publicityid', $entry)) {
            $pubs = $this->dbhr->preQuery("SELECT postid FROM groups_facebook_toshare WHERE id = ?;", [ $entry['publicityid'] ]);

            if (preg_match('/(.*)_(.*)/', $pubs[0]['postid'], $matches)) {
                # Create the iframe version of the Facebook plugin.
                $pageid = $matches[1];
                $postid = $matches[2];
                $entry['publicity'] = [
                    'id' => $entry['publicityid'],
                    'postid' => $pubs[0]['postid'],
                    'iframe' => '<iframe src="https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2F' . $pageid . '%2Fposts%2F' . $postid . '%2F&width=auto&show_text=true&appId=' . FBGRAFFITIAPP_ID . '&height=500" width="500" height="500" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true"></iframe>'
                ];
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

        $entry['replies'] = [];

        if ($checkreplies) {
            # Don't cache replies - might be lots and might change frequently.
            $replies = $this->dbhr->preQuery("SELECT * FROM newsfeed WHERE replyto = ? ORDER BY id ASC;", [
                $entry['id']
            ], FALSE);

            foreach ($replies as &$reply) {
                # Replies only one deep at present.
                $this->fillIn($reply, $users, FALSE);
                $entry['replies'][] = $reply;
            }
        }

        return($use);
    }

    public function getFeed($userid, $dist = Newsfeed::DISTANCE, $types, &$ctx) {
        $u = User::get($this->dbhr, $this->dbhm, $userid);
        $users = [];
        $items = [];

        # We want the newsfeed items which are close to us.  Use the location in settings, or failing that the
        # last location they've posted from.
        list ($lat, $lng) = $u->getLatLng();

        # To use the spatial index we need to have a box.
        $ne = GreatCircle::getPositionByDistance($dist, 45, $lat, $lng);
        $sw = GreatCircle::getPositionByDistance($dist, 225, $lat, $lng);

        $box = "GeomFromText('POLYGON(({$sw['lng']} {$sw['lat']}, {$sw['lng']} {$ne['lat']}, {$ne['lng']} {$ne['lat']}, {$ne['lng']} {$sw['lat']}, {$sw['lng']} {$sw['lat']}))')";

        # We return most recent first.
        $idq = pres('id', $ctx) ? ("newsfeed.id < " . intval($ctx['id'])) : 'newsfeed.id > 0';
        $first = $dist ? "(MBRContains($box, position) OR publicityid IS NOT NULL) AND $idq" : $idq;
        $typeq = $types ? (" AND `type` IN ('" . implode("','", $types) . "') ") : '';

        $sql = "SELECT * FROM newsfeed WHERE $first AND replyto IS NULL $typeq ORDER BY id DESC LIMIT 5;";
        #error_log($sql);
        $entries = $this->dbhr->preQuery($sql);

        foreach ($entries as &$entry) {
            $use = $this->fillIn($entry, $users);

            if ($use) {
                $items[] = $entry;
            }

            $ctx = [
                'id' => $entry['id'],
                'distance' => $dist
            ];
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

            # We want to notify the original poster.  The type depends on whether this was the start of a thread or
            # a comment on it.
            $n = new Notifications($this->dbhr, $this->dbhm);
            $n->add($me->getId(), $this->feed['userid'], $this->feed['replyto'] ? Notifications::TYPE_LOVED_COMMENT : Notifications::TYPE_LOVED_POST, $this->id);
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