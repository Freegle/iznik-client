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
        $sql = "INSERT INTO users_push_notifications (`userid`, `type`, `subscription`) VALUES (?, ?, ?);";
        $rc = $this->dbhm->preExec($sql, [ $userid, $type, $val ]);
        return($rc);
    }

    public function notify($userid) {
        $count = 0;
        $notifs = $this->dbhr->preQuery("SELECT * FROM users_push_notifications WHERE userid = ?;", [ $userid ]);

        foreach ($notifs as $notif) {
            $count++;
            error_log("Send user $userid {$notif['subscription']}");
            try {
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
                }

                $rc = $webPush->sendNotification($notif['subscription'], null, null, true);
            } catch (Exception $e) {
                $rc = [ 'exception' => $e->getMessage() ];
            }

            if ($rc !== TRUE) {
                error_log("Push Notification failed with " . var_export($rc, TRUE));
                $this->dbhm->preExec("DELETE FROM users_push_notifications WHERE userid = ? AND subscription = ?;", [
                    $userid,
                    $notif['subscription']
                ]);
            } else {
                error_log("Push Notification worked");
                $this->dbhm->preExec("UPDATE users_push_notifications SET lastsent = NOW() WHERE userid = ? AND subscription = ?;", [
                    $userid,
                    $notif['subscription']
                ]);
            }
        }

        return($count);
    }

    public function notifyGroupMods($groupid) {
        $count = 0;
        $mods = $this->dbhr->preQuery("SELECT userid FROM memberships WHERE groupid = ? AND role IN ('Owner', 'Moderator');",
            [ $groupid ]);

        foreach ($mods as $mod) {
            $u = new User($this->dbhr, $this->dbhm, $mod['userid']);
            $settings = $u->getGroupSettings($groupid);

            if (!array_key_exists('pushnotify', $settings) || $settings['pushnotify']) {
                #error_log("Notify {$mod['userid']} for $groupid notify " . presdef('pushnotify', $settings, TRUE) . " settings " . var_export($settings, TRUE));
                $count += $this->notify($mod['userid'], $groupid);
            }
        }

        return($count);
    }
}
