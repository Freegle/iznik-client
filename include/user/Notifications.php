<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/user/User.php');

use Minishlink\WebPush\WebPush;
use Pheanstalk\Pheanstalk;

class Notifications
{
    const PUSH_GOOGLE = 'Google';
    const PUSH_FIREFOX = 'Firefox';
    const PUSH_TEST = 'Test';
    const PUSH_ANDROID = 'Android';
    const PUSH_IOS = 'IOS';

    private $dbhr, $dbhm, $log, $pheanstalk = NULL;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($dbhr, $dbhm);
    }

    public function get($userid) {
        $ret = NULL;

        $sql = "SELECT * FROM users_push_notifications WHERE userid = ?;";
        $notifs = $this->dbhr->preQuery($sql, [ $userid ]);
        foreach ($notifs as &$notif) {
            $notif['added'] = ISODate($notif['added']);
            $ret = $notif;
        }

        return($ret);
    }

    public function add($userid, $type, $val) {
        $rc = NULL;
        if ($userid) {
            $sql = "INSERT INTO users_push_notifications (`userid`, `type`, `subscription`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE userid = ?, type = ?;";
            $rc = $this->dbhm->preExec($sql, [ $userid, $type, $val, $userid, $type ]);
            Session::clearSessionCache();
        }
        return($rc);
    }

    public function remove($userid) {
        $sql = "DELETE FROM users_push_notifications WHERE userid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $userid ] );
        return($rc);
    }

    public function uthook($rc = NULL) {
        # Mocked in UT to force an exception.
        return($rc);
    }

    private function queueSend($userid, $params, $endpoint, $payload) {
        #error_log("queueSend $userid $endpoint params " . var_export($params, TRUE));
        try {
            $this->uthook();

            if (!$this->pheanstalk) {
                $this->pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);
            }

            $str = json_encode(array(
                'type' => 'webpush',
                'queued' => time(),
                'userid' => $userid,
                'params' => $params,
                'endpoint' => $endpoint,
                'payload' => $payload
            ));

            $id = $this->pheanstalk->put($str);
        } catch (Exception $e) {
            # Try again in case it's a temporary error.
            error_log("Beanstalk exception " . $e->getMessage());
            $this->pheanstalk = NULL;
        }
    }

    public function executeSend($userid, $params, $endpoint, $payload) {
        try {
            #error_log("Execute send params " . var_export($params, TRUE) . " payload " . var_export($payload, TRUE));
            $params = $params ? $params : [];
            $webPush = new WebPush($params);
            $rc = $webPush->sendNotification($endpoint, $payload, NULL, TRUE);
            #error_log("Returned " . var_export($rc, TRUE) . " for {$notif['subscription']}");
            $rc = $this->uthook($rc);
        } catch (Exception $e) {
            $rc = [ 'exception' => $e->getMessage() ];
        }

        if ($rc !== TRUE) {
            error_log("Push Notification to $userid failed with " . var_export($rc, TRUE));
            $this->dbhm->preExec("DELETE FROM users_push_notifications WHERE userid = ? AND subscription = ?;", [ $userid, $endpoint ]);
        } else {
            # Don't log - lots of these.
            $this->dbhm->preExec("UPDATE users_push_notifications SET lastsent = NOW() WHERE userid = ? AND subscription = ?;", [ $userid, $endpoint  ], FALSE);
        }
    }

    public function notify($userid, $title = NULL, $message = NULL) {
        $count = 0;
        $u = User::get($this->dbhr, $this->dbhm, $userid);

        $notifs = $this->dbhr->preQuery("SELECT * FROM users_push_notifications WHERE userid = ?;", [ $userid ]);

        foreach ($notifs as $notif) {
            #error_log("Send user $userid {$notif['subscription']} type {$notif['type']}");
            $payload = NULL;
            $proceed = TRUE;
            $params = [];

            switch ($notif['type']) {
                case Notifications::PUSH_GOOGLE: {
                    $proceed = $u->notifsOn(User::NOTIFS_PUSH);
                    $params = [
                        'GCM' => GOOGLE_PUSH_KEY
                    ];
                    break;
                }
                case Notifications::PUSH_FIREFOX: {
                    $proceed = $u->notifsOn(User::NOTIFS_PUSH);
                    $params = [];
                    break;
                }
                case Notifications::PUSH_ANDROID: {
                    $proceed = $u->notifsOn(User::NOTIFS_APP);

                    if ($proceed) {
                        # We send this via GCM, but we need a payload.
                        $params = [
                            'GCM' => GOOGLE_PUSH_KEY
                        ];

                        $u = User::get($this->dbhr, $this->dbhm, $userid);
                        list ($chatcount, $title, $message, $chatids) = $u->getNotificationPayload(MODTOOLS);

                        $payload = [
                            'badge' => $chatcount,
                            'count' => $chatcount,
                            'title' => $title,
                            'message' => $message,
                            'chatids' => $chatids,
                            'image' => "www/images/user_logo.png"
                        ];
                    }

                    break;
                }
            }

            if ($proceed) {
                $this->queueSend($userid, $params, $notif['subscription'], $payload);
                $count++;
            }
        }

        return($count);
    }

    public function notifyGroupMods($groupid) {
        $count = 0;
        $mods = $this->dbhr->preQuery("SELECT userid FROM memberships WHERE groupid = ? AND role IN ('Owner', 'Moderator');",
            [ $groupid ]);

        foreach ($mods as $mod) {
            $u = User::get($this->dbhr, $this->dbhm, $mod['userid']);
            $settings = $u->getGroupSettings($groupid);

            if (!array_key_exists('pushnotify', $settings) || $settings['pushnotify']) {
                #error_log("Notify {$mod['userid']} for $groupid notify " . presdef('pushnotify', $settings, TRUE) . " settings " . var_export($settings, TRUE));
                $count += $this->notify($mod['userid']);
            }
        }

        return($count);
    }

    public function pokeGroupMods($groupid, $data) {
        $count = 0;
        $mods = $this->dbhr->preQuery("SELECT userid FROM memberships WHERE groupid = ? AND role IN ('Owner', 'Moderator');",
            [ $groupid ]);

        foreach ($mods as $mod) {
            $this->poke($mod['userid'], $data);
            $count++;
        }

        return($count);
    }

    public function fsockopen($host, $port, &$errno, &$errstr) {
        $fp = fsockopen('ssl://' . CHAT_HOST, 443, $errno, $errstr);
        return($fp);
    }

    public function fputs($fp, $str) {
        return(fputs($fp, $str));
    }

    public function poke($userid, $data) {
        # This kicks a user who is online at the moment with an outstanding long poll.
        #
        # TODO Handle multiple application servers
        filterResult($data);

        # We want to POST to notify.  We can speed this up using a persistent socket.
        $service_uri = "/publish/$userid";

        $topdata = array(
            'text' => $data,
            'channel' => $userid,
            'id' => 1
        );

        $vars = json_encode($topdata);

        $header = "Host: " . CHAT_HOST . "\r\n";
        $header .= "User-Agent: Iznik Notify\r\n";
        $header .= "Content-Type: application/json\r\n";
        $header .= "Content-Length: " . strlen($vars) . "\r\n";
        $header .= "Connection: close\r\n\r\n";

        try {
            $fp = $this->fsockopen('ssl://' . CHAT_HOST, 443, $errno, $errstr);

            if (!$fp) {
                error_log("Failed to get socket, $errstr ($errno)");
            } else {
                if (!$this->fputs($fp, "POST $service_uri  HTTP/1.1\r\n")) {
                    # This can happen if the socket is broken.  Just close it ready for next time.
                    fclose($fp);
                    error_log("Failed to post");
                } else {
                    fputs($fp, $header . $vars);
                    $server_response = fread($fp, 512);
                    #error_log("Rsp on $service_uri $server_response");
                }
            }
        } catch (Exception $e) {
            error_log("Failed to notify");
        }
    }
}
