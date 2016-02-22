<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/user/User.php');

class Notifications
{
    const PUSH_GOOGLE = 'Google';

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

    public function curl_exec($ch) {
        return curl_exec( $ch );
    }

    private function googleCloud($userid, $subscription) {
        $url = 'https://gcm-http.googleapis.com/gcm/send';

        // Data to send
        $post = array(
            'registration_ids'  => [ $subscription ]
        );

        // Set CURL request headers (authentication and type)
        $headers = array(
            'Authorization: key=' . GOOGLE_PUSH_KEY,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );
        $result = $this->curl_exec( $ch );

        if (strpos($result, 'NotRegistered') !== FALSE) {
            # No longer registrered on this subscription.  Zap it from our DB to avoid clutter and hammering Google.
            $this->dbhm->preExec("DELETE FROM users_push_notifications WHERE userid = ? AND subscription = ?;", [
                $userid,
                $subscription
            ]);
        } else {
            $this->dbhm->preExec("UPDATE users_push_notifications SET lastsent = NOW() WHERE userid = ? AND subscription = ?;", [
                $userid,
                $subscription
            ]);
        }

        error_log("CURL result " . var_export($result, TRUE));
        curl_close( $ch );
    }

    public function notify($userid) {
        $count = 0;
        $notifs = $this->dbhr->preQuery("SELECT * FROM users_push_notifications WHERE userid = ?;", [ $userid ]);
        foreach ($notifs as $notif) {
            $count++;
            $this->googleCloud($userid, $notif['subscription']);
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
