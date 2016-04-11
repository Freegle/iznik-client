<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/chat/Messages.php');

class ChatRoom extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'groupid', 'modonly', 'description');
    var $settableatts = array('name');

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'chat_rooms', 'chatroom', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($name, $gid = NULL, $modonly = FALSE) {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO chat_rooms (name, groupid, modonly) VALUES (?,?,?)", [
                $name,
                $gid,
                $modonly
            ]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'chat_rooms', 'chatroom', $this->publicatts);
            return($id);
        } else {
            return(NULL);
        }
    }

    public function setAttributes($settings) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        foreach ($this->settableatts as $att) {
            if (array_key_exists($att, $settings)) {
                $this->setPrivate($att, $settings[$att]);
            }
        }
    }

    public function getPublic() {
        $ret = $this->getAtts($this->publicatts);
        return($ret);
    }

    public function lastSeenForUser($userid) {
        # Find if we have any unseen messages.
        $sql = "SELECT lastmsgseen FROM chat_roster WHERE chatid = ? AND userid = ?;";
        $counts = $this->dbhr->preQuery($sql, [ $this->id, $userid ]);
        #return(round(rand(1, 10)));
        return(count($counts) > 0 ? $counts[0]['lastmsgseen'] : NULL);
    }

    public function unseenForUser($userid) {
        # Find if we have any unseen messages.
        $sql = "SELECT COUNT(*) AS count FROM chat_messages WHERE id > COALESCE((SELECT lastmsgseen FROM chat_roster WHERE chatid = ? AND userid = ?), 0) AND chatid = ? AND userid != ?;";
        $counts = $this->dbhr->preQuery($sql, [ $this->id, $userid, $this->id, $userid  ]);
        #return(round(rand(1, 10)));
        return($counts[0]['count']);
    }

    public function listForUser($userid) {
        $ret = [];
        $u = new User($this->dbhr, $this->dbhm, $userid);

        $sql = "SELECT id, modonly, groupid FROM chat_rooms WHERE groupid IS NULL OR groupid IN (SELECT groupid FROM memberships WHERE userid = ?);";
        $rooms = $this->dbhr->preQuery($sql, [ $userid ]);
        foreach ($rooms as $room) {
            #error_log("Consider {$room['id']} group {$room['groupid']} modonly {$room['modonly']} " . $u->isModOrOwner($room['groupid']));
            if (!$room['modonly'] || $u->isModOrOwner($room['groupid'])) {
                $show = TRUE;

                if ($room['groupid']) {
                    # See if the group allows chat.
                    $g = new Group($this->dbhr, $this->dbhm, $room['groupid']);
                    $show = $g->getSetting('showchat', TRUE);
                }

                if ($show) {
                    $ret[] = $room['id'];
                }
            }
        }

        return(count($ret) == 0 ? NULL : $ret);
    }

    public function canSee($userid) {
        $rooms = $this->listForUser($userid);
        return($rooms ? in_array($this->id, $rooms) : FALSE);
    }

    public function updateRoster($userid, $lastmsgseen) {
        # We have a unique key, and an update on current timestamp.
        $this->dbhm->preExec("REPLACE INTO chat_roster (chatid, userid, lastmsgseen) VALUES (?,?,?);",
            [
                $this->id,
                $userid,
                $lastmsgseen
            ]);
    }

    public function getRoster() {
        $mysqltime = date("Y-m-d H:i:s", strtotime("3600 seconds ago"));
        $sql = "SELECT TIMESTAMPDIFF(SECOND, date, NOW()) AS secondsago, chat_roster.* FROM chat_roster INNER JOIN users ON users.id = chat_roster.userid WHERE `chatid` = ? AND `date` >= ? ORDER BY COALESCE(users.fullname, users.firstname, users.lastname);";
        $roster = $this->dbhr->preQuery($sql, [ $this->id, $mysqltime ]);

        foreach ($roster as &$rost) {
            $u = new User($this->dbhr, $this->dbhm, $rost['userid']);
            $rost['status'] = $rost['secondsago'] < 60 ? 'Online' : ($rost['secondsago'] < 600 ? 'Away' : 'Offline');
            $ctx = NULL;
            $rost['user'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE);
        }

        return($roster);
    }
    
    public function pokeMembers() {
        # Poke members of a chat room.
        $data = [
            'roomid' => $this->id
        ];

        $mysqltime = date("Y-m-d H:i:s", strtotime("60 seconds ago"));
        $sql = "SELECT * FROM chat_roster WHERE `chatid` = ? AND `date` >= ?;";
        $roster = $this->dbhr->preQuery($sql, [ $this->id, $mysqltime ]);
        $count = 0;

        foreach ($roster as $rost) {
            Notifications::poke($rost['userid'], $data);
            $count++;
        }

        error_log("Poked $count on {$this->id}");
    }

    public function getMessages($limit = 100) {
        $sql = "SELECT id, userid FROM chat_messages WHERE chatid = ? ORDER BY date DESC LIMIT $limit;";
        $msgs = $this->dbhr->preQuery($sql, [ $this->id ]);
        $msgs = array_reverse($msgs);
        $users = [];

        $ret = [];
        $lastuser = NULL;
        $consecutive = 0;
        $lastmsg = NULL;

        foreach ($msgs as $msg) {
            $m = new ChatMessage($this->dbhr, $this->dbhm, $msg['id']);
            $atts = $m->getPublic();
            $atts['date'] = ISODate($atts['date']);

            $atts['sameaslast'] = ($lastuser === $msg['userid']);

            if (count($ret) > 0) {
                $ret[count($ret) - 1]['sameasnext'] = ($lastuser === $msg['userid']);
            }

            if (!array_key_exists($msg['userid'], $users)) {
                $u = new User($this->dbhr, $this->dbhm, $msg['userid']);
                $users[$msg['userid']] = $u->getPublic(NULL, FALSE);
            }

            $ret[] = $atts;
            $lastuser = $msg['userid'];
        }

        return([$ret, $users]);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM chat_rooms WHERE id = ?;", [$this->id]);
        return($rc);
    }
}