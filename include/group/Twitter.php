<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');
require_once(IZNIK_BASE . '/include/user/Story.php');

use Abraham\TwitterOAuth\TwitterOAuth;

class Twitter {
    var $publicatts = ['name', 'token', 'secret', 'authdate', 'valid', 'locked', 'msgid', 'msgarrival', 'eventid', 'lasterror', 'lasterrortime'];
    
    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $groupid, $fetched = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->groupid = $groupid;
        $this->tw = NULL;

        foreach ($this->publicatts as $att) {
            $this->$att = NULL;
        }

        # Note that we must return it even if there's no name yet, because that's used during the setting process.
        $groups = $fetched ? [ $fetched ] : $this->dbhr->preQuery("SELECT * FROM groups_twitter WHERE groupid = ?;", [ $groupid ]);
        foreach ($groups as $group) {
            foreach ($this->publicatts as $att) {
                $this->$att = $group[$att];
            }

            $this->tw = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $this->token, $this->secret);
        }
    }

    public function getPublic() {
        $ret = [];
        foreach ($this->publicatts as $att) {
            $ret[$att] = $this->$att;
        }
        
        return($ret);
    }

    public function setTw($tw)
    {
        $this->tw = $tw;
    }
    
    public function set($name, $token, $secret) {
        $this->dbhm->preExec("INSERT INTO groups_twitter (groupid, name, token, secret, authdate, valid) VALUES (?,?,?,?,NOW(),1) ON DUPLICATE KEY UPDATE name = ?, token = ?, secret = ?, authdate = NOW(), valid = 1;",
            [
                $this->groupid,
                $name, $token, $secret,
                $name, $token, $secret
            ]);

        $this->name = $name;
        $this->token = $token;
        $this->secret = $secret;
    }

    public function tweet($status, $media) {
        $this->tw->setTimeouts(120, 120);
        $content = $this->tw->get("account/verify_credentials");
        $ret = NULL;
        $rc = FALSE;
        $valid = TRUE;
        $locked = FALSE;

        if ($content) {
            if ($media) {
                # The API uploads from file, unfortunately.
                $fname = tempnam('/tmp', 'twitter_');
                file_put_contents($fname, $media);

                try {
                    $ret = $this->tw->upload('media/upload', array('media' => $fname));
                    $ret = json_decode(json_encode($ret), TRUE);

                    if (!pres('errors', $ret)) {
                        $ret = $this->tw->post('statuses/update', [
                            'status' => $status,
                            'media_ids' => implode(',', [$ret['media_id_string']])
                        ]);
                    }
                } catch (Exception $e) {}

                unlink($fname);
            } else {
                $ret = $this->tw->post('statuses/update', [
                    'status' => $status
                ]);
            }

            $ret = json_decode(json_encode($ret), TRUE);

            if (pres('errors', $ret)) {
                # Something failed.
                #error_log("Tweet failed " . var_export($ret, TRUE));
                $this->dbhm->preExec("UPDATE groups_twitter SET lasterror = ?, lasterrortime = NOW() WHERE groupid = ?;", [ var_export($ret['errors'], TRUE), $this->groupid ]);

                if ($ret['errors'][0]['code'] == 220) {
                    # This indicates invalid credentials.
                    #error_log("Now invalid");
                    $valid = FALSE;
                }

                if ($ret['errors'][0]['code'] == 326) {
                    # Locked.
                    $locked = TRUE;
                }
            } else {
                $rc = TRUE;

                if ($this->locked || !$this->valid) {
                    # Tweeted successfully - no longer locked or invalid.
                    $this->dbhm->preExec("UPDATE groups_twitter SET locked = 0, valid = 1 WHERE groupid = ?;", [ $this->groupid ]);
                    $this->locked = FALSE;
                    $this->valid = TRUE;
                }
            }
        }

        if (!$valid) {
            $this->dbhm->preExec("UPDATE groups_twitter SET valid = 0 WHERE groupid = ?;", [ $this->groupid ]);
            $this->valid = FALSE;
            #error_log("Twitter link not valid for {$this->groupid}");
        }

        if ($locked) {
            $this->dbhm->preExec("UPDATE groups_twitter SET locked = 1 WHERE groupid = ?;", [ $this->groupid ]);
            $this->locked = TRUE;
        }

        return($rc);
    }

    public function tweetEvents() {
        # We want to tweet:
        # - any events since the last one, with a max of the 24 hours ago to avoid flooding things
        # - which start after now and within the next 96 hours
        $addedsince = date("Y-m-d", strtotime("24 hours ago"));
        $startafter = date("Y-m-d H:i:s");
        $startbefore = date("Y-m-d", strtotime("+96 hours"));
        $eventid = $this->eventid ? $this->eventid : 0;
        $sql = "SELECT DISTINCT communityevents_groups.eventid, communityevents_dates.start FROM communityevents_groups INNER JOIN groups ON groups.id = communityevents_groups.groupid INNER JOIN communityevents_dates ON communityevents_dates.eventid = communityevents_groups.eventid WHERE communityevents_groups.groupid = ? AND ((communityevents_groups.arrival >= ? AND communityevents_dates.eventid > ?) OR communityevents_dates.start <= ?) AND communityevents_dates.start >= ? ORDER BY communityevents_dates.start ASC;";

        $events = $this->dbhr->preQuery($sql, [
            $this->groupid,
            $addedsince,
            $eventid,
            $startbefore,
            $startafter
        ]);
        $eventid = NULL;
        $worked = 0;

        foreach ($events as $event) {
            $e = new CommunityEvent($this->dbhr, $this->dbhm, $event['eventid']);

            if (!$e->getPrivate('deleted')) {
                # We tweet the title, first date later than now, and a link.
                $atts = $e->getPublic();

                # Get a string representation of the date in UK time.
                $tz1 = new DateTimeZone('UTC');
                $tz2 = new DateTimeZone('Europe/London');
                $datetime = new DateTime($event['start'], $tz1);
                $datetime->setTimezone($tz2);
                $datestr = $datetime->format('D jS F g:i a');

                $status = $atts['title'];
                $status = substr($status, 0, 80);
                $status .= " on $datestr";

                $link = 'https://' . USER_SITE . "/communityevent/{$event['eventid']}?t=". time();

                $status .= " $link";
                $rc = $this->tweet($status, pres('photo', $atts) ? file_get_contents($atts['photo']['path']) : NULL);
                error_log($status);

                if ($rc) {
                    $worked++;
                }

                # Whether the tweet works or not, we might as well assume it does - tweets are ephemeral so there's no
                # point getting too het up if they don't work.
                $eventid = max($eventid, $event['eventid']);
            }
        }

        if ($eventid) {
            $this->dbhm->preExec("UPDATE groups_twitter SET eventid = ? WHERE groupid = ?;", [ $eventid, $this->groupid ]);
        }

        return($worked);
    }

    public function tweetStory($id = NULL) {
        # We tweet from the central account.
        $this->tw = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_ACCOUNT_TOKEN, TWITTER_ACCOUNT_SECRET);

        # We want to tweet:
        # - any story which hasn't been tweeted, or
        # - a random one
        $idq = $id ? " AND id = $id " : "";
        $sql = "SELECT id FROM users_stories WHERE public = 1 and reviewed = 1 $idq ORDER BY tweeted ASC LIMIT 1;";
        $stories = $this->dbhr->preQuery($sql);
        foreach ($stories as $story) {
            $s = new Story($this->dbhr, $this->dbhm, $story['id']);

            # We tweet the title, first date later than now, and a link.
            $atts = $s->getPublic();

            $status = 'A freegle story: "' . substr($atts['headline'], 0, 90) . '"';
            $status .= ' #LoveFreegle read more...';

            $link = 'https://' . USER_SITE . "/story/{$story['id']}?src=tweetstory&t=". time();

            $status .= " $link";
            $img = rand(1, 5);
            $rc = $this->tweet($status, file_get_contents(IZNIK_BASE . "/http/images/stories/story$img.png"));

            $this->dbhm->preExec("UPDATE users_stories SET tweeted = 1 WHERE id = ?;", [ $story['id'] ]);
        }
    }

    public function tweetMessages() {
        # We want to tweet any messages since the last one, with a max of the 24 hours ago to avoid flooding things.
        $mysqltime = date ("Y-m-d H:i:s.u", strtotime($this->msgarrival ? $this->msgarrival : "1 hour ago"));
        $sql = "SELECT messages_groups.msgid, messages_groups.arrival FROM messages_groups INNER JOIN groups ON groups.id = messages_groups.groupid INNER JOIN messages ON messages_groups.msgid = messages.id INNER JOIN users ON users.id = messages.fromuser LEFT JOIN messages_outcomes ON messages.id = messages_outcomes.msgid WHERE messages_groups.groupid = ? AND messages_groups.arrival > ? AND messages_groups.collection = 'Approved' AND users.publishconsent = 1 AND messages.type IN ('Offer', 'Wanted') AND messages_outcomes.msgid IS NULL ORDER BY messages_groups.arrival ASC;";

        $msgs = $this->dbhr->preQuery($sql, [ $this->groupid, $mysqltime ]);
        $msgid = NULL;
        $msgarrival = NULL;
        $worked = 0;

        foreach ($msgs as $msg) {
            $m = new Message($this->dbhr, $this->dbhm, $msg['msgid']);
            $atts = $m->getAttachments();
            $media = count($atts) > 0 ? $atts[0]->getData() : NULL;

            # We tweet the subject and a link.
            $status = $m->getSubject();
            $status = substr($status, 0, 80);

            $link = "https://" . USER_SITE . "/message/{$msg['msgid']}?src=" . User::SRC_TWITTER;

            $status .= " $link";
            $rc = $this->tweet($status, $media);
            
            if ($rc) {
                $worked++;
            }

            # Whether the tweet works or not, we might as well assume it does - tweets are ephemeral so there's no
            # point getting too het up if they don't work.
            $msgid = $msgid ? max($msg['msgid'], $msgid) : $msg['msgid'];
            $msgarrival = $msg['arrival'];
        }

        if ($msgid) {
            $this->dbhm->preExec("UPDATE groups_twitter SET msgid = ?, msgarrival = ? WHERE groupid = ?;", [ $msgid, $msgarrival, $this->groupid ]);
        }
        
        return($worked);
    }
}
