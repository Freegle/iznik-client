<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');

use Facebook\FacebookSession;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookCanvasLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

class GroupFacebook {
    var $publicatts = ['name', 'token', 'authdate', 'valid', 'msgid', 'eventid', 'sharefrom', 'token', 'groupid', 'id' ];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $groupid = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->groupid = $groupid;

        foreach ($this->publicatts as $att) {
            $this->$att = NULL;
        }

        $groups = $this->dbhr->preQuery("SELECT * FROM groups_facebook WHERE groupid = ?;", [ $groupid ]);
        foreach ($groups as $group) {
            foreach ($this->publicatts as $att) {
                $this->$att = $group[$att];
            }
        }
    }

    public function getPublic() {
        $ret = [];
        foreach ($this->publicatts as $att) {
            $ret[$att] = $this->$att;
        }

        return($ret);
    }

    public function findById($id) {
        $groups = $this->dbhr->preQuery("SELECT groupid FROM groups_facebook WHERE id = ?;", [ $id ]);
        return(count($groups) > 0 ? $groups[0]['groupid'] : NULL);
    }

    public function getFB() {
        $fb = new Facebook\Facebook([
            'app_id' => FBGRAFFITIAPP_ID,
            'app_secret' => FBGRAFFITIAPP_SECRET
        ]);

        return($fb);
    }

    public function set($groupid, $token, $name, $id) {
        $this->dbhm->preExec("INSERT INTO groups_facebook (groupid, name, id, token, authdate) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE token = ?, authdate = NOW(), valid = 1;",
            [
                $groupid,
                $name,
                $id,
                $token,
                $token
            ]);

        $this->token = $token;
    }

    public function getPostsToShare($sharefrom, $since = "yesterday") {
        $fb = $this->getFB();

        # Get posts we might want to share.  This returns only posts by the page itself.
        try {
            $ret = $fb->get($sharefrom . "/posts?since=$since&fields=id,link,message,type,caption,icon,name", $this->token);

            $posts = $ret->getDecodedBody();
            #error_log("Posts " . var_export($posts, TRUE));

            foreach ($posts['data'] as $wallpost) {
                $rc = $this->dbhm->preExec("INSERT IGNORE INTO groups_facebook_toshare (sharefrom, postid, data) VALUES (?,?,?);", [
                    $sharefrom,
                    $wallpost['id'],
                    json_encode($wallpost)
                ]);
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            error_log("Failed code $code message " . $e->getMessage() . " token " . $this->token);
        }
    }

    public function listSocialActions(&$ctx, $mindate = NULL) {
        # We want posts which have been collected from the sharefrom page which have not already been shared, for
        # groups where we are a moderator.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $ret = [];
        $dateq = $mindate ? " AND groups_facebook_toshare.date >= '$mindate'" : '';


        if ($me) {
            $modships = $me->getModeratorships();
            $minid = $ctx ? $ctx['id'] : 0;

            if (count($modships) > 0) {
                $groupids = implode(',', $modships);
                $sql = "SELECT DISTINCT groups_facebook_toshare.*, 'Facebook' AS actiontype FROM groups_facebook_toshare INNER JOIN groups_facebook ON groups_facebook.sharefrom = groups_facebook_toshare.sharefrom AND valid = 1 WHERE groupid IN ($groupids) AND groups_facebook_toshare.id > ? $dateq ORDER BY groups_facebook_toshare.id DESC;";
                #error_log($sql);
                $posts = $this->dbhr->preQuery($sql, [ $minid ]);

                foreach ($posts as &$post) {
                    $ctx['id'] = $post['id'];
                    $posteds = $this->dbhr->preQuery("SELECT groupid FROM groups_facebook_shares WHERE postid = ?;", [
                        $post['postid']
                    ]);

                    $remaining = $modships;

                    foreach ($posteds as $posted) {
                        $remaining = array_diff($remaining, [ $posted['groupid'] ]);
                    }

                    if (count($remaining) > 0) {
                        $post['groups'] = $remaining;

                        if (preg_match('/(.*)_(.*)/', $post['postid'], $matches)) {
                            # Create the iframe version of the Facebook plugin.
                            $pageid = $matches[1];
                            $postid = $matches[2];
                            $post['iframe'] = '<iframe src="https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2F' . $pageid . '%2Fposts%2F' . $postid . '%2F&width=auto&show_text=true&appId=' . FBGRAFFITIAPP_ID . '&height=500" width="500" height="500" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true"></iframe>';
                        }

                        $ret[] = $post;
                    }
                }
            }
        }

        return($ret);
    }

    public function performSocialAction($id) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $ret = [];
        $fb = $this->getFB();

        if ($me) {
            # We need to be a mod on the relevant group.
            $modships = $me->getModeratorships();

            if (count($modships) > 0) {
                $groupids = implode(',', $modships);
                $sql = "SELECT DISTINCT groups_facebook_toshare.* FROM groups_facebook_toshare INNER JOIN groups_facebook ON groups_facebook.sharefrom = groups_facebook_toshare.sharefrom AND groupid IN ($groupids) AND groups_facebook_toshare.id = ?;";
                $actions = $this->dbhr->preQuery($sql, [ $id ]);
                foreach ($actions as $action) {
                    try {
                        # Whether or not this worked, remember that we've tried, so that we don't try again.
                        #
                        # TODO should we handle transient errors better?
                        $this->dbhm->preExec("INSERT IGNORE INTO groups_facebook_shares (groupid, postid) VALUES (?,?);", [
                            $this->groupid,
                            $action['postid']
                        ]);

                        # Like the original post.
                        $res = $fb->post($action['postid'] . '/likes', [], $this->token);
                        #error_log("Like returned " . var_export($res, true));

                        # We want to share the post out with the existing details - but we need to remove the id, otherwise
                        # it's an invalid op.
                        $params = json_decode($action['data'], TRUE);
                        unset($params['id']);
                        #error_log("Post to {$this->name} with {$this->token} action " . var_export($params, TRUE));
                        $result = $fb->post($this->id . '/feed', $params, $this->token);
                        #error_log("Post returned " . var_export($result, true));
                    } catch (Exception $e) {
                        $code = $e->getCode();
                        error_log("Failed code $code message " . $e->getMessage() . " token " . $this->token);

                        # These numbers come from FacebookResponseException.
                        if ($code == 100 || $code == 102 || $code == 190) {
                            $this->dbhm->preExec("UPDATE groups_facebook SET valid = 0, lasterrortime = NOW(), lasterror = ? WHERE groupid = ?;", [
                                $e->getMessage(),
                                $this->groupid
                            ]);
                        }
                    }
                }
            }
        }
    }
}
