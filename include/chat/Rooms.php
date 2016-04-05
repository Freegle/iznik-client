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

    public function listForUser($userid) {
        $ret = [];
        $u = new User($this->dbhr, $this->dbhm, $userid);
        $mod = $u->isModerator();

        $sql = "SELECT id, modonly FROM chat_rooms WHERE groupid IS NULL OR groupid IN (SELECT groupid FROM memberships WHERE userid = ?);";
        $rooms = $this->dbhr->preQuery($sql, [ $userid ]);
        foreach ($rooms as $room) {
            if (!$room['modonly'] || $mod) {
                $ret[] = $room['id'];
            }
        }

        return(count($ret) == 0 ? NULL : $ret);
    }

    public function canSee($userid) {
        $rooms = $this->listForUser($userid);
        return($rooms ? in_array($this->id, $rooms) : FALSE);
    }
    
    public function pokeMembers() {
        # Poke members of a chat room.
        $modq = $this->chatroom['modonly'] ? " AND role IN ('Owner', 'Moderator') " : "";
        $sql = "SELECT userid FROM memberships WHERE groupid = ? $modq;";
        $members = $this->dbhr->preQuery($sql, [ $this->chatroom['groupid'] ]);
        $data = [
            'roomid' => $this->id
        ];

        foreach ($members as $member) {
            Notifications::poke($member['userid'], $data);
        }
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