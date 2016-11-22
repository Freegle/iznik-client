<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/user/User.php');
use Minishlink\WebPush\WebPush;

class Notifications
{
    const PUSH_GOOGLE = 'Google';
    const PUSH_FIREFOX = 'Firefox';
    const PUSH_TEST = 'Test';
    const PUSH_ANDROID = 'Android';

    private $dbhr, $dbhm, $log;

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
        $sql = "INSERT INTO users_push_notifications (`userid`, `type`, `subscription`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE userid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $userid, $type, $val, $val ]);
        Session::clearSessionCache();
        return($rc);
    }

    public function remove($userid) {
        $sql = "DELETE FROM users_push_notifications WHERE userid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $userid ] );
        return($rc);
    }

    public function uthook() {
        # Mocked in UT to force an exception.
    }

    public function notify($userid, $title = NULL, $message = NULL) {
        $count = 0;
        $u = User::get($this->dbhr, $this->dbhm, $userid);

        $notifs = $this->dbhr->preQuery("SELECT * FROM users_push_notifications WHERE userid = ?;", [ $userid ]);

        foreach ($notifs as $notif) {
            #error_log("Send user $userid {$notif['subscription']}");
            try {
                $this->uthook();
                $payload = NULL;
                $proceed = TRUE;

                switch ($notif['type']) {
                    case Notifications::PUSH_GOOGLE: {
                        $webPush = new WebPush([
                            'GCM' => GOOGLE_PUSH_KEY
                        ]);
                        break;
                    }
                    case Notifications::PUSH_FIREFOX: {
                        $webPush = new WebPush();
                        break;
                    }
                    case Notifications::PUSH_ANDROID: {
                        $proceed = $u->notifsOn(User::NOTIFS_APP);

                        if ($proceed) {
                            # We send this via GCM, but we need a payload.
                            $webPush = new WebPush([
                                'GCM' => GOOGLE_PUSH_KEY
                            ]);

                            $u = User::get($this->dbhr, $this->dbhm, $userid);
                            list ($count, $title, $message) = $u->getNotificationPayload(MODTOOLS);

                            $payload = [
                                'badge' => $count,
                                'count' => $count,
                                'title' => $title,
                                'message' => $message,
                                "image" => "www/images/user_logo.png"
                            ];
                        }

                        break;
                    }
                }

                $rc = TRUE;

                if ($proceed) {
                    $rc = $webPush->sendNotification($notif['subscription'], $payload, null, true);
                    #error_log("Returned " . var_export($rc, TRUE) . " for {$notif['subscription']}");
                    $count++;
                }
            } catch (Exception $e) {
                $rc = [ 'exception' => $e->getMessage() ];
            }

            if ($rc !== TRUE) {
                error_log("Push Notification to $userid failed with " . var_export($rc, TRUE));
                $this->dbhm->preExec("DELETE FROM users_push_notifications WHERE userid = ? AND subscription = ?;", [
                    $userid,
                    $notif['subscription']
                ]);
            } else {
                # Don't log - lots of these.
                $this->dbhm->preExec("UPDATE users_push_notifications SET lastsent = NOW() WHERE userid = ? AND subscription = ?;", [
                    $userid,
                    $notif['subscription']
                ], FALSE);
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
