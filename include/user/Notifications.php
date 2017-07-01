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
    const TYPE_TRY_FEED = 'TryFeed';

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

    private function snip(&$msg) {
        if ($msg) {
            if (strlen($msg) > 57) {
                $msg = substr($msg, 0, strpos(wordwrap($msg, 60), "\n")) . '...';
            }
        }
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
                    $this->snip($not['message']);

                    if ($not['replyto']) {
                        $origs = $this->dbhr->preQuery("SELECT * FROM newsfeed WHERE id = ?;", [
                            $not['replyto']
                        ]);

                        foreach ($origs as &$orig) {
                            $this->snip($orig['message']);
                            unset($orig['position']);
                            $not['replyto'] = $orig;
                        }
                    }

                    unset($not['position']);
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

    public function add($from, $to, $type, $newsfeedid, $url = NULL) {
        $id = NULL;

        if ($from != $to) {
            $sql = "INSERT INTO users_notifications (`fromuser`, `touser`, `type`, `newsfeedid`, `url`) VALUES (?, ?, ?, ?, ?);";
            $this->dbhm->preExec($sql, [ $from, $to, $type, $newsfeedid, $url ]);
            $id = $this->dbhm->lastInsertId();
        }
        return($id);
    }

    public function seen($userid, $id) {
        $idq = $id ? (" AND id = " . intval($id)) : '';
        $sql = "UPDATE users_notifications SET seen = 1 WHERE touser = ? $idq;";
        $rc = $this->dbhm->preExec($sql, [ $userid ] );
        return($rc);
    }

    public function off($uid) {
        $u = User::get($this->dbhr, $this->dbhm, $uid);

        $settings = json_decode($u->getPrivate('settings'), TRUE);
        $settings['notificationmails'] = FALSE;
        $u->setPrivate('settings', json_encode($settings));
        error_log("Off for $uid to " . json_encode($settings));

        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_NOTIFICATIONOFF,
            'user' => $uid
        ]);

        $email = $u->getEmailPreferred();

        if ($email) {
            list ($transport, $mailer) = getMailer();
            $html = relevant_off(USER_SITE, USERLOGO);

            $message = Swift_Message::newInstance()
                ->setSubject("Email Change Confirmation")
                ->setFrom([NOREPLY_ADDR => SITE_NAME])
                ->setReturnPath($u->getBounce())
                ->setTo([ $email => $u->getName() ])
                ->setBody("Thanks - we've turned off the mails for notifications.")
                ->addPart($html, 'text/html');

            $this->sendOne($mailer, $message);
        }
    }
}
