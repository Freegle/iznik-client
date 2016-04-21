<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');

class ChatRoom extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'groupid', 'modonly', 'description', 'user1', 'user2');
    var $settableatts = array('name', 'description');

    const STATUS_ONLINE = 'Online';
    const STATUS_OFFLINE = 'Offline';
    const STATUS_AWAY = 'Away';
    const STATUS_CLOSED = 'Closed';

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

    public function createGroupChat($name, $gid = NULL, $modonly = FALSE, $modtools = FALSE) {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO chat_rooms (name, groupid, modonly, modtools) VALUES (?,?,?,?)", [
                $name,
                $gid,
                $modonly,
                $modtools
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

    public function createConversation($user1, $user2) {
        $id = NULL;

        # We use a transaction to close timing windows.
        $this->dbhm->beginTransaction();

        # Find any existing chat.  Who is user1 and who is user2 doesn't really matter - it's a two way chat.
        $sql = "SELECT id FROM chat_rooms WHERE (user1 = ? AND user2 = ?) OR (user2 = ? AND user1 = ?) FOR UPDATE;";
        $chats = $this->dbhm->preQuery($sql, [
            $user1,
            $user2,
            $user1,
            $user2
        ]);
        
        $rollback = TRUE;

        if (count($chats) > 0) {
            # We have an existing chat.  That'll do nicely.
            $id = $chats[0]['id'];
        } else {
            # We don't.  Create one.
            $rc = $this->dbhm->preExec("INSERT INTO chat_rooms (user1, user2) VALUES (?,?)", [
                $user1,
                $user2
            ]);
            
            if ($rc) {
                # We created one.  We'll commit below.
                $id = $this->dbhm->lastInsertId();
                $rollback = FALSE;
            }
        }
        
        if ($rollback) {
            # We might have worked above or failed; $id is set accordingly.
            $this->dbhm->rollBack();
        } else {
            # We want to commit, and return an id if that worked.
            $rc = $this->dbhm->commit();
            $id = $rc ? $id : NULL;
        }

        if ($id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'chat_rooms', 'chatroom', $this->publicatts);

            # Now the conversation exists, set presence.
            #
            # Start off with the two members offline.
            $this->updateRoster($user1, NULL, ChatRoom::STATUS_OFFLINE);
            $this->updateRoster($user2, NULL, ChatRoom::STATUS_OFFLINE);

            # If we're logged in as one of the members, set our own presence in it to Online.  This will have the effect of
            # overwriting any previous Closed status, which would stop it appearing in our list of chats.  So if you
            # close a conversation, and then later reopen it by finding a relevant link, then it comes back.
            $me = whoAmI($this->dbhr, $this->dbhm);
            $myid = $me ? $me->getId() : NULL;

            if ($myid == $user1 || $myid == $user2) {
                $this->updateRoster($myid, NULL, ChatRoom::STATUS_ONLINE);
            }

            # Poke the (other) member(s) to let them know to pick up the new chat
            $n = new Notifications($this->dbhr, $this->dbhm);

            foreach ([$user1, $user2] as $user) {
                if ($myid != $user) {
                    $n->poke($user, [
                        'newroom' => $id
                    ]);
                }
            }
        }

        return($id);
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

        if (pres('groupid', $ret)) {
            $g = new Group($this->dbhr, $this->dbhm, $ret['groupid']);
            unset($ret['groupid']);
            $ret['group'] = $g->getPublic();
        }
        
        if (pres('user1', $ret)) {
            # This is a conversation between two people.   
            $u = new User($this->dbhr, $this->dbhm, $ret['user1']);
            unset($ret['user1']);
            $ctx = NULL;
            $ret['user1'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE);
        }
        
        if (pres('user2', $ret)) {
            # This is a conversation between two people.   
            $u = new User($this->dbhr, $this->dbhm, $ret['user2']);
            unset($ret['user2']);
            $ctx = NULL;
            $ret['user2'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE);
        }

        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me->getId();

        $ret['unseen'] = $this->unseenForUser($myid);

        if (!pres('name', $ret)) {
            # If this is not a named chat then we invent the name; we use the name of the user who isn't us, because
            # that's who we're chatting to.
            $ret['name'] = ($ret['user1']['id'] != $myid) ? $ret['user1']['displayname'] :
                $ret['user2']['displayname'];
        }

        $refmsgs = $this->dbhr->preQuery("SELECT DISTINCT refmsgid FROM chat_messages WHERE chatid = ?;", [ $this->id ]);
        $ret['refmsgids'] = [];
        foreach ($refmsgs as $refmsg) {
            $ret['refmsgids'][] = $refmsg['refmsgid'];
        }
        
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

    public function listForUser($userid, $modtools = NULL) {
        $ret = [];
        $u = new User($this->dbhr, $this->dbhm, $userid);
        $modtoolsq = ($modtools === NULL) ? '' : ("AND modtools = " . ($modtools ? 1 : 0));

        # The chats we can see are:
        # - either for a group (possibly a modonly one)
        # - a conversation between two users that we have not closed
        $sql = "SELECT chat_rooms.* FROM chat_rooms LEFT JOIN chat_roster ON chat_roster.userid = ? AND chat_rooms.id = chat_roster.chatid WHERE ((groupid IN (SELECT groupid FROM memberships WHERE userid = ?) $modtoolsq) OR user1 = ? OR user2 = ?) AND (status IS NULL OR status != ?);";
        #error_log($sql . var_export([ $userid, $userid, $userid, $userid, ChatRoom::STATUS_CLOSED ], TRUE));
        $rooms = $this->dbhr->preQuery($sql, [ $userid, $userid, $userid, $userid, ChatRoom::STATUS_CLOSED ]);
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
        #error_log("CanSee $userid, {$this->id}, " . var_export($rooms, TRUE));
        return($rooms ? in_array($this->id, $rooms) : FALSE);
    }

    public function updateRoster($userid, $lastmsgseen, $status) {
        # We have a unique key, and an update on current timestamp.
        #
        # Don't want to log these - lots of them.
        $this->dbhm->preExec("REPLACE INTO chat_roster (chatid, userid, lastip) VALUES (?,?,?);",
            [
                $this->id,
                $userid,
                presdef('REMOTE_ADDR', $_SERVER, NULL)
            ],
            FALSE);

        if ($lastmsgseen) {
            # Update the last message seen - taking care not to go backwards, which can happen if we have multiple
            # windows open.
            $this->dbhm->preExec("UPDATE chat_roster SET lastmsgseen = ? WHERE chatid = ? AND userid = ? AND (lastmsgseen IS NULL OR lastmsgseen < ?);",
                [
                    $lastmsgseen,
                    $this->id,
                    $userid,
                    $lastmsgseen
                ],
                FALSE);

            #error_log("UPDATE chat_roster SET lastmsgseen = $lastmsgseen WHERE chatid = {$this->id} AND userid = $userid AND (lastmsgseen IS NULL OR lastmsgseen < $lastmsgseen);");
        }

        $this->dbhm->preExec("UPDATE chat_roster SET status = ? WHERE chatid = ? AND userid = ?;",
            [
                $status,
                $this->id,
                $userid
            ],
            FALSE);
    }

    public function getRoster() {
        $mysqltime = date("Y-m-d H:i:s", strtotime("3600 seconds ago"));
        $sql = "SELECT TIMESTAMPDIFF(SECOND, date, NOW()) AS secondsago, chat_roster.* FROM chat_roster INNER JOIN users ON users.id = chat_roster.userid WHERE `chatid` = ? AND `date` >= ? ORDER BY COALESCE(users.fullname, users.firstname, users.lastname);";
        $roster = $this->dbhr->preQuery($sql, [ $this->id, $mysqltime ]);

        foreach ($roster as &$rost) {
            $u = new User($this->dbhr, $this->dbhm, $rost['userid']);
            switch ($rost['status']) {
                case ChatRoom::STATUS_ONLINE:
                    # We last heard that they were online; but if we've not heard from them recently then fade them out.
                    $rost['status'] = $rost['secondsago'] < 60 ? ChatRoom::STATUS_ONLINE : ($rost['secondsago'] < 600 ? ChatRoom::STATUS_AWAY : ChatRoom::STATUS_OFFLINE);
                    break;
                case ChatRoom::STATUS_AWAY:
                    # Similarly, if we last heard they were away, fade them to offline if we've not heard.
                    $rost['status'] = $rost['secondsago'] < 600 ? ChatRoom::STATUS_AWAY: ChatRoom::STATUS_OFFLINE;
                    break;
            }
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

        $n = new Notifications($this->dbhr, $this->dbhm);

        foreach ($roster as $rost) {
            $n->poke($rost['userid'], $data);
            $count++;
        }
    }

    public function getMessages($limit = 100) {
        $sql = "SELECT id, userid FROM chat_messages WHERE chatid = ? ORDER BY date DESC LIMIT $limit;";
        $msgs = $this->dbhr->preQuery($sql, [ $this->id ]);
        $msgs = array_reverse($msgs);
        $users = [];

        $ret = [];
        $lastuser = NULL;
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