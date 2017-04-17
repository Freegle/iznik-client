<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/mail/Newsletter.php');
require_once(IZNIK_BASE . '/mailtemplates/stories/story_ask.php');
require_once(IZNIK_BASE . '/mailtemplates/stories/story_central.php');
require_once(IZNIK_BASE . '/mailtemplates/stories/story_one.php');
require_once(IZNIK_BASE . '/mailtemplates/stories/story_newsletter.php');

class Story extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'date', 'public', 'headline', 'story', 'reviewed');
    var $settableatts = array('public', 'headline', 'story', 'reviewed', 'newsletterreviewed', 'newsletter');

    const ASK_OUTCOME_THRESHOLD = 3;
    const ASK_OFFER_THRESHOLD = 5;

    const LIKE = 'Like';
    const UNLIKE = 'Unlike';

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
            if ($me->hasPermission(User::PERM_NEWSLETTER) || $me->moderatorForUser($this->story['userid'])) {
                $ret['user'] = $u->getPublic();
                $ret['user']['email'] = $u->getEmailPreferred();
            }
        }

        $membs = $u->getMemberships();
        $groupname = NULL;

        if (count($membs) > 0) {
            shuffle($membs);
            foreach ($membs as $memb) {
                if ($memb['type'] == Group::GROUP_FREEGLE && $memb['onmap']) {
                    $groupname = $memb['namedisplay'];
                }
            }
        }

        $ret['groupname'] = $groupname;

        $likes = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM users_stories_likes WHERE storyid = ?;", [
            $this->id
        ], FALSE, FALSE);

        $ret['likes'] = $likes[0]['count'];
        $ret['liked'] = FALSE;
        if ($me) {
            $likes = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM users_stories_likes WHERE storyid = ? AND userid = ?;", [
                $this->id,
                $me->getId()
            ], FALSE, FALSE);
            $ret['liked'] = $likes[0]['count'] > 0;
        }

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

    public function getForReview($groupids, $newsletter) {
        $sql = $newsletter ? ("SELECT DISTINCT users_stories.id FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE reviewed = 1 AND public = 1 AND newsletterreviewed = 0 ORDER BY date DESC") : ("SELECT DISTINCT users_stories.id FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE memberships.groupid IN (" . implode(',', $groupids) . ") AND reviewed = 0 ORDER BY date DESC");
        $ids = $this->dbhr->preQuery($sql);
        $ret = [];

        foreach ($ids as $id) {
            $s = new Story($this->dbhr, $this->dbhm, $id['id']);
            $ret[] = $s->getPublic();
        }

        return($ret);
    }

    public function getReviewCount($newsletter) {
        $sql = $newsletter ? "SELECT COUNT(DISTINCT(users_stories.id)) AS count FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE reviewed = 1 AND public = 1 AND newsletterreviewed = 0 ORDER BY date DESC" : ("SELECT COUNT(DISTINCT users_stories.id) AS count FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE memberships.groupid IN (SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Moderator', 'Owner')) AND reviewed = 0 ORDER BY date DESC;");
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;
        $ids = $this->dbhr->preQuery($sql, [
            $myid
        ]);
        return($ids[0]['count']);
    }

    public function getStories($groupid, $story, $limit = 20, $reviewnewsletter = FALSE) {
        $limit = intval($limit);
        if ($reviewnewsletter) {
            $sql = "SELECT DISTINCT users_stories.id FROM users_stories WHERE newsletter = 1 AND mailedtomembers = 0 ORDER BY RAND();";
        } else {
            $sql1 = "SELECT DISTINCT users_stories.id FROM users_stories WHERE reviewed = 1 AND public = 1 AND userid IS NOT NULL ORDER BY date DESC LIMIT $limit;";
            $sql2 = "SELECT DISTINCT users_stories.id FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE memberships.groupid = $groupid AND reviewed = 1 AND public = 1 AND users_stories.userid IS NOT NULL ORDER BY date DESC LIMIT $limit;";
            $sql = $groupid ? $sql2 : $sql1;
        }

        $ids = $this->dbhr->preQuery($sql);
        $ret = [];

        foreach ($ids as $id) {
            $s = new Story($this->dbhr, $this->dbhm, $id['id']);
            $thisone = $s->getPublic();
            if (!$story) {
                unset($thisone['story']);
            }
            $include = TRUE;

            foreach ($this->exclude as $word) {
                if (stripos($thisone['headline'], $word) !== FALSE || (pres('story', $thisone) && stripos($thisone['story'], $word) !== FALSE)) {
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

                    $html = story_ask($u->getName(), $u->getEmailPreferred(), $url);
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

    public function sendIt($mailer, $message) {
        $mailer->send($message);
    }

    public function sendToCentral($id = NULL) {
        $idq = $id ? " AND id = $id " : "";
        $stories = $this->dbhr->preQuery("SELECT id FROM users_stories WHERE mailedtocentral = 0 AND public = 1 AND reviewed = 1 $idq;");
        $url = "https://" . USER_SITE . "stories/fornewsletter";
        $html = "<p><span style=\"color: red\">Please go  <a href=\"$url\">here</a> to vote for which go into the next member newsletter.</span></p>";
        $text = "Please go to $url to vote for which go into the next member newsletter\n\n";
        $count = 0;

        foreach ($stories as $story) {
            $s = new Story($this->dbhr, $this->dbhm, $story['id']);
            $atts = $s->getPublic();

            $include = TRUE;

            foreach ($this->exclude as $word) {
                if (stripos($atts['headline'], $word) !== FALSE || (pres('story', $atts) && stripos($atts['story'], $word) !== FALSE)) {
                    $include = FALSE;
                }
            }

            if ($include) {
                $html .= story_one($atts['groupname'], $atts['headline'], $atts['story']);
                $text = $atts['headline'] . "\nFrom a freegler on {$atts['groupname']}\n\n{$atts['story']}\n\n";
                $this->dbhm->preExec("UPDATE users_stories SET mailedtocentral = 1 WHERE id = ?;", [ $story['id'] ]);
                $count++;
            }
        }

        $url = 'https://' . USER_SITE . '/stories';
        $html = story_central(CENTRAL_MAIL_TO, CENTRAL_MAIL_TO, $url, $html);

        $message = Swift_Message::newInstance()
            ->setSubject("Recent stories from freeglers")
            ->setFrom([CENTRAL_MAIL_FROM => SITE_NAME])
            ->setReturnPath(CENTRAL_MAIL_FROM)
            ->setTo(CENTRAL_MAIL_TO)
            ->setBody($text)
            ->addPart($html, 'text/html');

        list ($transport, $mailer) = getMailer();
        $this->sendIt($mailer, $message);
        return($count);
    }

    public function generateNewsletter($min = 3, $max = 10, $id = NULL) {
        # We generate a newsletter from stories which have been marked as suitable for publication.
        $nid = NULL;
        $count = 0;

        # Find the date of the last newsletter; we're only interested in stories since then.
        $last = $this->dbhr->preQuery("SELECT MAX(created) AS max FROM newsletters WHERE type = 'Stories';");
        $since = $last[0]['max'];

        # Get unsent stories.  Pick the ones we have voted for most often.
        $idq = $id ? " AND id = $id " : "";
        $stories = $this->dbhr->preQuery("SELECT id, COUNT(*) AS count FROM users_stories LEFT JOIN users_stories_likes ON storyid = users_stories.id WHERE newsletterreviewed = 1 AND newsletter = 1 AND mailedtomembers = 0 $idq AND (? IS NULL OR date > ?) GROUP BY id ORDER BY count DESC LIMIT $max;", [
            $since,
            $since
        ]);

        if (count($stories) >= $min) {
            # Enough to be worth sending a newsletter.
            shuffle($stories);

            $n = new Newsletter($this->dbhr, $this->dbhm);
            $nid = $n->create(NULL,
                "Lovely stories from other freeglers!",
                "This is a selection of recent stories from other freeglers.  If you can't read the HTML version, have a look at https://" . USER_SITE . '/stories');

            # Heading intro.
            $header = story_newsletter();
            $n->addArticle(Newsletter::TYPE_HEADER, 0, $header, NULL);

            foreach ($stories as $story) {
                $s = new Story($this->dbhr, $this->dbhm, $story['id']);
                $atts = $s->getPublic();

                $include = TRUE;

                foreach ($this->exclude as $word) {
                    if (stripos($atts['headline'], $word) !== FALSE || (pres('story', $atts) && stripos($atts['story'], $word) !== FALSE)) {
                        $include = FALSE;
                    }
                }

                if ($include) {
                    $count++;
                }

                $n->addArticle(Newsletter::TYPE_ARTICLE, $count, story_one($atts['groupname'], $atts['headline'], $atts['story'], FALSE), NULL);
                $this->dbhm->preExec("UPDATE users_stories SET mailedtomembers = 1 WHERE id = ?;", [ $story['id'] ]);
            }
        }

        return ($count >= $min ? $nid : NULL);
    }

    public function like() {
        $me = whoAmI($this->dbhr, $this->dbhm);
        if ($me) {
            $this->dbhm->preExec("INSERT IGNORE INTO users_stories_likes (storyid, userid) VALUES (?,?);", [
                $this->id,
                $me->getId()
            ]);
        }
    }

    public function unlike() {
        $me = whoAmI($this->dbhr, $this->dbhm);
        if ($me) {
            $this->dbhm->preExec("DELETE FROM users_stories_likes WHERE storyid = ? AND userid = ?;", [
                $this->id,
                $me->getId()
            ]);
        }
    }
}