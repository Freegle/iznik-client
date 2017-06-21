<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/user/User.php');

class Notifications
{
    const TYPE_COMMENT_ON_YOUR_POST = 'CommentOnYourPost';
    const TYPE_COMMENT_ON_COMMENT = 'CommentOnCommented';
    const TYPE_LOVED_POST = 'LovedPost';
    const TYPE_LOVED_COMMENT = 'LovedComment';

    private $dbhr, $dbhm, $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($dbhr, $dbhm);
    }

    public function countUnseen($userid) {
        $counts = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM users_notifications WHERE touser = ? AND seen = 0;", [
            $userid
        ]);
        return($counts[0]['count']);
    }

    public function get($userid, &$ctx) {
        $ret = [];
        $idq = $ctx && pres('id', $ctx) ? (" AND id < " . intval($ctx['id'])) : '';
        $sql = "SELECT * FROM users_notifications WHERE touser = ? $idq ORDER BY id DESC;";
        $notifs = $this->dbhr->preQuery($sql, [ $userid ]);

        foreach ($notifs as &$notif) {
            $notif['timestamp'] = ISODate($notif['timestamp']);

            if (pres('fromuser', $notif)) {
                $u = User::get($this->dbhr, $this->dbhm, $notif['fromuser']);
                $notif['fromuser'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE, FALSE);
            }

            if (pres('newsfeedid', $notif)) {
                $nots = $this->dbhr->preQuery("SELECT * FROM newsfeed WHERE id = ?;", [
                    $notif['newsfeedid']
                ]);

                foreach ($nots as $not) {
                    unset($not['position']);
                    $not['message'] = $not['message'] ? substr($not['message'], 0, 60) : NULL;
                    $notif['newsfeed'] = $not;
                }
            }

            $ret[] = $notif;

            $ctx = [
                'id' => $notif['id']
            ];
        }

        return($ret);
    }

    public function add($from, $to, $type, $newsfeedid) {
        $rc = NULL;
        $sql = "INSERT INTO users_notifications (`fromuser`, `touser`, `type`, `newsfeedid`) VALUES (?, ?, ?, ?);";
        $rc = $this->dbhm->preExec($sql, [ $from, $to, $type, $newsfeedid ]);
        return($this->dbhm->lastInsertId());
    }

    public function seen($userid, $id) {
        $idq = $id ? (" AND id = " . intval($id)) : '';
        $sql = "UPDATE users_notifications SET seen = 1 WHERE touser = ? $idq;";
        $rc = $this->dbhm->preExec($sql, [ $userid ] );
        return($rc);
    }
}
