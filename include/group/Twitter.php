<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

use Abraham\TwitterOAuth\TwitterOAuth;

class Twitter {
    var $publicatts = ['name', 'token', 'secret', 'authdate', 'valid'];
    
    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $groupid)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->groupid = $groupid;

        foreach ($this->publicatts as $att) {
            $this->$att = NULL;
        }

        $groups = $this->dbhr->preQuery("SELECT * FROM groups_twitter WHERE groupid = ?;", [ $groupid ]);
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
        error_log("Tweet using {$this->token}, {$this->secret}");
        $tw = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $this->token, $this->secret);
        $content = $tw->get("account/verify_credentials");
        error_log("Login" . var_export($content, TRUE));

        if ($content) {
            if ($media) {
                # The API uploads from file, unfortunately.
                $fname = tempnam('/tmp', 'twitter_');
                file_put_contents($fname, $media);

                try {
                    $media = $tw->upload('media/upload', array('media' => $fname));

                    $ret = $tw->post('statuses/update', [
                        'status' => $status,
                        'media_ids' => implode(',', [$media->media_id_string])
                    ]);
                } catch (Exception $e) {}

                unlink($fname);
            } else {
                $ret = $tw->post('statuses/update', [
                    'status' => $status
                ]);
            }

            $ret = json_decode(json_encode($ret), TRUE);

            if (pres('errors', $ret)) {
                # Something failed.
                $this->dbhm->preExec("UPDATE groups_twitter SET lasterror = ?, lasterrortime = NOW() WHERE groupid = ?;", [ var_export($ret['errors'], TRUE), $this->groupid ]);
            }
        } else {
            $this->dbhm->preExec("UPDATE groups_twitter SET valid = 0 WHERE groupid = ?;", [ $this->groupid ]);
        }
    }
}
