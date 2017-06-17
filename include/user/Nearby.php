<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/mailtemplates/relevant/nearby.php');

class Nearby
{
    /** @var  $dbhr LoggedPDO */
    var $dbhr;
    /** @var  $dbhm LoggedPDO */
    var $dbhm;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    # Split out for UT to override
    public function sendOne($mailer, $message) {
        $mailer->send($message);
    }

    public function messages($groupid) {
        list ($transport, $mailer) = getMailer();
        $count = 0;

        $g = Group::get($this->dbhr, $this->dbhm, $groupid);

        if ($g->getSetting('relevant', 1)) {
            # Find the recent extant messages
            $mysqltime = date ("Y-m-d", strtotime("Midnight 31 days ago"));
            $sql = "SELECT DISTINCT messages.id, messages.type FROM messages LEFT OUTER JOIN messages_outcomes ON messages_outcomes.msgid = messages.id INNER JOIN messages_groups ON messages_groups.msgid = messages.id AND collection = 'Approved' INNER JOIN groups ON groups.id = messages_groups.groupid AND groups.id = ? WHERE messages_outcomes.msgid IS NULL AND messages.type IN ('Offer', 'Wanted') AND messages.arrival > '$mysqltime' LIMIT 1000;";
            $msgs = $this->dbhr->preQuery($sql, [ $groupid ] );

            foreach ($msgs as $msg) {
                $m = new Message($this->dbhr, $this->dbhm, $msg['id']);
                $lid = $m->getPrivate('locationid');
                $u = new User($this->dbhr, $this->dbhm, $m->getFromuser());
                $name = $u->getName();

                if ($lid && !$m->hasOutcome()) {
                    $l = new Location($this->dbhr, $this->dbhm, $lid);
                    $lat = $l->getPrivate('lat');
                    $lng = $l->getPrivate('lng');

                    if ($lat && $lng && $m->getFromuser()) {
                        # We have a message which is still extant and where we know the location.  Find nearby
                        # users we've not mailed about this message.
                        error_log("{$msg['id']} " . $m->getPrivate('subject') . " at $lat, $lng");
                        $sql = "SELECT users.id, locations.lat, locations.lng, haversine($lat, $lng, locations.lat, locations.lng) AS dist FROM users INNER JOIN memberships ON users.id = memberships.userid INNER JOIN locations ON locations.id = users.lastlocation LEFT JOIN users_nearby ON users_nearby.userid = users.id AND users_nearby.msgid = {$msg['id']} WHERE groupid = $groupid AND users.id != " . $m->getFromuser() . " AND users_nearby.msgid IS NULL ORDER BY dist ASC LIMIT 100;";
                        $users = $this->dbhr->preQuery($sql);

                        foreach ($users as $user) {
                            $u2 = new User($this->dbhr, $this->dbhm, $user['id']);

                            if ($u2->getPrivate('relevantallowed')) {
                                $p1 = new POI($lat, $lng);
                                $p2 = new POI($user['lat'], $user['lng']);
                                $metres = $p1->getDistanceInMetersTo($p2);
                                $miles = $metres / 1609.344;
                                $miles = round($miles, 1);

                                # We mail the most nearby people - but too far it's probably not worth it.
                                if ($miles <= 2) {
                                    # Check we've not mailed them recently.
                                    $mailed = $this->dbhr->preQuery("SELECT MAX(timestamp) AS max FROM users_nearby WHERE userid = ?;", [
                                        $user['id']
                                    ]);

                                    if (count($mailed) == 0 || (time() - strtotime($mailed[0]['max']) > 7 * 24 * 60 * 60)) {
                                        $this->dbhm->preExec("INSERT INTO users_nearby (userid, msgid) VALUES (?, ?);", [
                                            $user['id'],
                                            $msg['id']
                                        ]);

                                        $subj = $u->getName() . " ($miles mile" . ($miles != 1 ? 's' : '') . " away) needs your help!";
                                        $noemail = 'relevantoff-' . $user['id'] . "@" . USER_DOMAIN;
                                        $textbody = "$name, who's just $miles mile" . ($miles != 1 ? 's' : '') . " miles from you, has posted " . $m->getSubject() . ".  Do you know anyone who can help?  The post is here: https://" . USER_SITE . "/message/{$msg['id']}?src=nearby";

                                        $email = $u2->getEmailPreferred();
                                        $html = relevant_nearby(USER_SITE, USERLOGO, $name, $miles, $m->getSubject(), $msg['id'], $msg['type'], $email, $noemail);

                                        try {
                                            $message = Swift_Message::newInstance()
                                                ->setSubject($subj)
                                                ->setFrom([NOREPLY_ADDR => SITE_NAME ])
                                                ->setReturnPath($u->getBounce())
                                                ->setTo([ $email => $u->getName() ])
                                                ->setBody($textbody)
                                                ->addPart($html, 'text/html');

                                            $this->sendOne($mailer, $message);
                                            error_log("...user {$user['id']} dist $miles");
                                            $count++;
                                        } catch (Exception $e) {
                                            error_log("Send to $email failed with " . $e->getMessage());
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return($count);
    }
}
