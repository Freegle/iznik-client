<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/mailtemplates/story.php');

class Story extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'date', 'public', 'headline', 'story', 'reviewed');
    var $settableatts = array('public', 'headline', 'story', 'reviewed');

    const ASK_OUTCOME_THRESHOLD = 3;
    const ASK_OFFER_THRESHOLD = 5;

    # TODO Generic
    private $exclude = [ 'freecycle', 'freecycling' ];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'users_stories', 'story', $this->publicatts);
    }

    public function create($userid, $public, $headline, $story) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO users_stories (public, userid, headline, story) VALUES (?,?,?,?);", [
            $public,
            $userid,
            $headline,
            $story
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            if ($id) {
                $this->fetch($this->dbhr, $this->dbhm, $id, 'users_stories', 'story', $this->publicatts);
            }
        }

        return($id);
    }

    public function getPublic() {
        $ret = parent::getPublic();
        $me = whoAmI($this->dbhr, $this->dbhm);

        $u = User::get($this->dbhr, $this->dbhm, $this->story['userid']);

        if ($me && $me->isModerator() && $this->story['userid']) {
            if ($me->moderatorForUser($this->story['userid'])) {
                $ret['user'] = $u->getPublic();
                $ret['user']['email'] = $u->getEmailPreferred();
            }
        }

        $membs = $u->getMemberships();
        $groupname = NULL;

        if (count($membs) > 0) {
            shuffle($membs);
            foreach ($membs as $memb) {
                if ($memb['type'] == Group::GROUP_FREEGLE) {
                    $groupname = $membs[0]['namedisplay'];
                }
            }
        }

        $ret['groupname'] = $groupname;

        return($ret);
    }

    public function canSee() {
        # Can see our own, or all if we have permissions, or if it's public
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;
        return($this->story['public'] || $this->story['userid'] == $myid || ($me && $me->isAdminOrSupport()));
    }

    public function canMod() {
        # We can modify if it's ours, we are an admin, or a mod on a group that the author is a member of.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;
        $author = User::get($this->dbhr, $this->dbhm, $this->story['userid']);
        $authormembs = $author->getMemberships(FALSE);
        $ret = ($this->story['userid'] == $myid) || ($me && $me->isAdminOrSupport());

        if ($myid) {
            $membs = $me->getMemberships(TRUE);
            foreach ($membs as $memb) {
                foreach ($authormembs as $authormemb) {
                    if ($authormemb['id'] == $memb['id']) {
                        $ret = TRUE;
                    }
                }
            }
        }

        return($ret);
    }

    public function getForReview($groupids) {
        $sql = "SELECT DISTINCT users_stories.id FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE memberships.groupid IN (" . implode(',', $groupids) . ") AND reviewed = 0 ORDER BY date DESC";
        $ids = $this->dbhr->preQuery($sql);
        $ret = [];

        foreach ($ids as $id) {
            $s = new Story($this->dbhr, $this->dbhm, $id['id']);
            $ret[] = $s->getPublic();
        }

        return($ret);
    }

    public function getReviewCount($groupids) {
        $sql = "SELECT COUNT(DISTINCT users_stories.id) AS count FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE memberships.groupid IN (SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Moderator', 'Owner')) AND reviewed = 0 ORDER BY date DESC";
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;
        $ids = $this->dbhr->preQuery($sql, [
            $myid
        ]);
        return($ids[0]['count']);
    }

    public function getStories($groupid) {
        $sql1 = "SELECT DISTINCT users_stories.id FROM users_stories WHERE reviewed = 1 AND public = 1 AND userid IS NOT NULL ORDER BY date DESC LIMIT 20;";
        $sql2 = "SELECT DISTINCT users_stories.id FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE memberships.groupid = $groupid AND reviewed = 1 AND public = 1 AND users_stories.userid IS NOT NULL ORDER BY date DESC LIMIT 20;";
        $sql = $groupid ? $sql2 : $sql1;
        $ids = $this->dbhr->preQuery($sql);
        $ret = [];

        foreach ($ids as $id) {
            $s = new Story($this->dbhr, $this->dbhm, $id['id']);
            $thisone = $s->getPublic();
            $include = TRUE;

            foreach ($this->exclude as $word) {
                if (stripos($thisone['headline'], $word) !== FALSE || stripos($thisone['story'], $word) !== FALSE) {
                    $include = FALSE;
                }
            }

            if ($include) {
                $ret[] = $thisone;
            }
        }

        return($ret);
    }

    public function askForStories($earliest, $userid = NULL, $outcomethreshold = Story::ASK_OUTCOME_THRESHOLD, $offerthreshold = Story::ASK_OFFER_THRESHOLD, $groupid = NULL) {
        $userq = $userid ? " AND fromuser = $userid " : "";
        $groupq = $groupid ? " INNER JOIN messages_groups ON messages_groups.msgid = messages.id AND messages_groups.groupid = $groupid " : "";
        $sql = "SELECT DISTINCT fromuser FROM messages $groupq LEFT OUTER JOIN users_stories_requested ON users_stories_requested.userid = messages.fromuser WHERE  messages.arrival > ? AND fromuser IS NOT NULL AND users_stories_requested.date IS NULL $userq;";
        $users = $this->dbhr->preQuery($sql, [ $earliest ]);
        $asked = 0;

        error_log("Found " . count($users) . " possible users");

        foreach ($users as $user) {
            $outcomes = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages_outcomes WHERE userid = ? AND outcome IN ('Taken', 'Received');", [ $user['fromuser'] ]);
            $outcomecount = $outcomes[0]['count'];
            $offers = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages WHERE fromuser = ? AND type = 'Offer';", [ $user['fromuser'] ]);
            $offercount = $offers[0]['count'];
            #error_log("Userid {$user['fromuser']} outcome count $outcomecount offer count $offercount");

            if ($outcomecount > $outcomethreshold || $offercount > $offerthreshold) {
                # Record that we've thought about asking.  This means we won't consider them repeatedly.
                $this->dbhm->preExec("INSERT INTO users_stories_requested (userid) VALUES (?);", [ $user['fromuser'] ]);

                # We only want to ask if they are a member of a group which has stories enabled.
                $u = new User($this->dbhr, $this->dbhm, $user['fromuser']);
                $membs = $u->getMemberships();
                $ask = FALSE;
                foreach ($membs as $memb) {
                    $g = Group::get($this->dbhr, $this->dbhm, $memb['id']);
                    $stories = $g->getSetting('stories', 1);
                    #error_log("Consider send for " . $u->getEmailPreferred() . " stories $stories, groupid $groupid vs {$memb['id']}");
                    if ($stories && (!$groupid || $groupid == $memb['id'])) {
                        $ask = TRUE;
                    }
                }

                if ($ask) {
                    $asked++;
                    $url = $u->loginLink(USER_SITE, $user['fromuser'], '/stories');

                    $html = story($u->getName(), $u->getEmailPreferred(), $url);
                    error_log("..." . $u->getEmailPreferred());

                    try {
                        $message = Swift_Message::newInstance()
                            ->setSubject("Tell us your Freegle story!")
                            ->setFrom([NOREPLY_ADDR => SITE_NAME])
                            ->setReturnPath($u->getBounce())
                            ->setTo([ $u->getEmailPreferred() => $u->getName() ])
                            ->setBody("We'd love to hear your Freegle story.  Tell us at $url")
                            ->addPart($html, 'text/html');

                        list ($transport, $mailer) = getMailer();
                        $mailer->send($message);
                    } catch (Exception $e) {}
                }
            }
        }

        return($asked);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM users_stories WHERE id = ?;", [ $this->id ]);
        return($rc);
    }
}