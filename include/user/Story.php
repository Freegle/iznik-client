<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

class Story extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'date', 'public', 'headline', 'story', 'reviewed');
    var $settableatts = array('public', 'headline', 'story', 'reviewed');

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
            $groupname = $membs[0]['namedisplay'];
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
        $sql1 = "SELECT DISTINCT users_stories.id FROM users_stories WHERE reviewed = 1 AND public = 1 AND userid IS NOT NULL ORDER BY date DESC LIMIT 10;";
        $sql2 = "SELECT DISTINCT users_stories.id FROM users_stories INNER JOIN memberships ON memberships.userid = users_stories.userid WHERE memberships.groupid = $groupid AND reviewed = 1 AND public = 1 AND users_stories.userid IS NOT NULL ORDER BY date DESC LIMIT 10;";
        $sql = $groupid ? $sql2 : $sql1;
        $ids = $this->dbhr->preQuery($sql);
        $ret = [];

        foreach ($ids as $id) {
            $s = new Story($this->dbhr, $this->dbhm, $id['id']);
            $ret[] = $s->getPublic();
        }

        return($ret);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM users_stories WHERE id = ?;", [ $this->id ]);
        return($rc);
    }
}