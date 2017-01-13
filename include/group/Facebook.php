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
    var $publicatts = ['name', 'token', 'type', 'authdate', 'valid', 'msgid', 'msgarrival', 'eventid', 'sharefrom', 'token', 'groupid', 'id' ];

    const TYPE_PAGE = 'Page';
    const TYPE_GROUP = 'Group';

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

    public function getFB($graffiti) {
        error_log("Get FB $graffiti");
        $fb = new Facebook\Facebook([
            'app_id' => $graffiti ? FBGRAFFITIAPP_ID : FBAPP_ID,
            'app_secret' => $graffiti ? FBGRAFFITIAPP_SECRET : FBAPP_SECRET
        ]);

        return($fb);
    }

    public function set($groupid, $token, $name, $id, $type = GroupFacebook::TYPE_PAGE) {
        $this->dbhm->preExec("INSERT INTO groups_facebook (groupid, name, id, token, authdate, type) VALUES (?,?,?,?,NOW(), ?) ON DUPLICATE KEY UPDATE name = ?, id = ?, token = ?, authdate = NOW(), valid = 1, type = ?;",
            [
                $groupid,
                $name,
                $id,
                $token,
                $type,
                $name,
                $id,
                $token,
                $type
            ]);

        $this->token = $token;
    }

    public function getPostsToShare($sharefrom, $since = "yesterday") {
        $fb = $this->getFB(FALSE);

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
            $minid = $ctx ? $ctx['id'] : 0;

            $modships = [];

            # Remove groups which aren't linked.
            $groups = $this->dbhr->preQuery("SELECT memberships.groupid FROM memberships INNER JOIN groups_facebook ON groups_facebook.groupid = memberships.groupid WHERE userid = ? AND role IN ('Owner', 'Moderator') AND valid = 1;",
                [
                    $me->getId()
                ]);

            foreach ($groups as $group) {
                $modships[] = $group['groupid'];
            }

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
        if ($me) {
            # We need to be a mod on the relevant group.
            $modships = $me->getModeratorships();

            if (count($modships) > 0) {
                $groupids = implode(',', $modships);
                $sql = "SELECT DISTINCT groups_facebook_toshare.*, groups_facebook.type AS facebooktype FROM groups_facebook_toshare INNER JOIN groups_facebook ON groups_facebook.sharefrom = groups_facebook_toshare.sharefrom AND groupid IN ($groupids) AND groupid = ? AND groups_facebook_toshare.id = ?;";
                $actions = $this->dbhr->preQuery($sql, [ $this->groupid, $id ]);
                error_log("Perform " . var_export($actions, TRUE));
                foreach ($actions as $action) {
                    try {
                        # Whether or not this worked, remember that we've tried, so that we don't try again.
                        $this->dbhm->preExec("INSERT IGNORE INTO groups_facebook_shares (groupid, postid) VALUES (?,?);", [
                            $this->groupid,
                            $action['postid']
                        ]);

                        $page = $action['facebooktype'] == GroupFacebook::TYPE_PAGE;
                        $fb = $this->getFB($page);

                        if ($page) {
                            # Like the original post.
                            $res = $fb->post($action['postid'] . '/likes', [], $this->token);
                            #error_log("Like returned " . var_export($res, true));
                        }

                        # We want to share the post out with the existing details - but we need to remove the id, otherwise
                        # it's an invalid op.
                        $params = json_decode($action['data'], TRUE);
                        unset($params['id']);

                        #error_log("Post to {$this->name} {$this->id} with {$this->token} action " . var_export($params, TRUE));
                        $result = $fb->post($this->id . '/feed', $params, $this->token);
                        #error_log("Post returned " . var_export($result, true));
                    } catch (Exception $e) {
                        $code = $e->getCode();
                        error_log("Failed on {$this->groupid} code $code message " . $e->getMessage() . " token " . $this->token);

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

    public function postMessages() {
        # We want to post any messages since the last one, with a max of 1 hour ago to avoid flooding things.
        $mysqltime = date ("Y-m-d H:i:s", strtotime($this->msgarrival ? $this->msgarrival : "1 hour ago"));
        $sql = "SELECT messages_groups.msgid, messages_groups.arrival FROM messages_groups INNER JOIN groups ON groups.id = messages_groups.groupid INNER JOIN messages ON messages_groups.msgid = messages.id INNER JOIN users ON users.id = messages.fromuser WHERE messages_groups.groupid = ? AND messages_groups.arrival > ? AND messages_groups.collection = 'Approved' AND users.publishconsent = 1 AND messages.type IN ('Offer', 'Wanted') ORDER BY messages_groups.arrival ASC;";

        $msgs = $this->dbhr->preQuery($sql, [ $this->groupid, $mysqltime ]);
        error_log($sql . var_export([ $this->groupid, $mysqltime  ], TRUE));
        $msgid = NULL;
        $worked = 0;

        foreach ($msgs as $msg) {
            $params = [
                'link' => 'https://' . USER_SITE . '/message/' . $msg['msgid'] . '?src=fbgroup',
                'description' => 'Please click to view and reply - no PMs please.  Everything on Freegle is completely free.'
            ];

            # Whether the post works or not, we might as well assume it does.  If it fails it's most likely because
            # we are rate-limited, and we'd never get out of that state.
            $msgid = $msg['msgid'];

            try {
                $fb = $this->getFB(FALSE);
                $result = $fb->post($this->id . '/feed', $params, $this->token);
                error_log("Post returned " . var_export($result, true));

                # Try to avoid rate-limiting.  This number covers the traffic we expect.
                sleep(10);
                $worked++;
            } catch (Exception $e) {
                error_log("Post failed with " . $e->getMessage());
                $code = $e->getCode();
                error_log("Failed on {$this->groupid} code $code message " . $e->getMessage() . " token " . $this->token);

                # These numbers come from FacebookResponseException.
                if ($code == 100 || $code == 102 || $code == 190) {
                    $this->dbhm->preExec("UPDATE groups_facebook SET valid = 0, lasterrortime = NOW(), lasterror = ? WHERE groupid = ?;", [
                        $e->getMessage(),
                        $this->groupid
                    ]);
                }
            }
        }

        if ($msgid) {
            $this->dbhm->preExec("UPDATE groups_facebook SET msgid = ? WHERE groupid = ?;", [ $msgid, $this->groupid ]);
            error_log("UPDATE groups_facebook SET msgid = $msgid WHERE groupid = {$this->groupid};");
        }

        return($worked);
    }
}
