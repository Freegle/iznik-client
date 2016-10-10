<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');
require_once(IZNIK_BASE . '/mailtemplates/chat_notify.php');
require_once(IZNIK_BASE . '/mailtemplates/chat_notify_mod.php');
require_once(IZNIK_BASE . '/mailtemplates/chat_chaseup_mod.php');

class ChatRoom extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'chattype', 'groupid', 'description', 'user1', 'user2');
    var $settableatts = array('name', 'description');

    const TYPE_MOD2MOD = 'Mod2Mod';
    const TYPE_USER2MOD = 'User2Mod';
    const TYPE_USER2USER = 'User2User';

    const STATUS_ONLINE = 'Online';
    const STATUS_OFFLINE = 'Offline';
    const STATUS_AWAY = 'Away';
    const STATUS_CLOSED = 'Closed';

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        # Always use the master, because cached results mess up presence.
        $this->fetch($dbhm, $dbhm, $id, 'chat_rooms', 'chatroom', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    # This can be overridden in UT.
    public function constructMessage(User $u, $id, $toname, $to, $fromname, $from, $subject, $text, $html) {
        $_SERVER['SERVER_NAME'] = USER_DOMAIN;
        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom([$from => $fromname])
            ->setTo([$to => $toname])
            ->setBody($text);

        if ($html) {
            $message->addPart($html, 'text/html');
        }

        $headers = $message->getHeaders();

        $headers->addTextHeader('List-Unsubscribe', $u->listUnsubscribe(USER_SITE, $id));

        return($message);
    }

    public function mailer($message) {
        list ($transport, $mailer) = getMailer();
        $mailer->send($message);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function createGroupChat($name, $gid) {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO chat_rooms (name, chattype, groupid) VALUES (?,?,?)", [
                $name,
                ChatRoom::TYPE_MOD2MOD,
                $gid
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
        $sql = "SELECT id FROM chat_rooms WHERE (user1 = ? AND user2 = ?) OR (user2 = ? AND user1 = ?) AND chattype = ? FOR UPDATE;";
        $chats = $this->dbhm->preQuery($sql, [
            $user1,
            $user2,
            $user1,
            $user2,
            ChatRoom::TYPE_USER2USER
        ]);
        
        $rollback = TRUE;

        if (count($chats) > 0) {
            # We have an existing chat.  That'll do nicely.
            $id = $chats[0]['id'];
        } else {
            # We don't.  Create one.
            $rc = $this->dbhm->preExec("INSERT INTO chat_rooms (user1, user2, chattype) VALUES (?,?,?)", [
                $user1,
                $user2,
                ChatRoom::TYPE_USER2USER
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

    public function createUser2Mod($user1, $groupid) {
        $id = NULL;

        # We use a transaction to close timing windows.
        $this->dbhm->beginTransaction();

        # Find any existing chat.
        $sql = "SELECT id FROM chat_rooms WHERE user1 = ? AND groupid = ? AND chattype = ? FOR UPDATE;";
        $chats = $this->dbhm->preQuery($sql, [
            $user1,
            $groupid,
            ChatRoom::TYPE_USER2MOD
        ]);

        $rollback = TRUE;

        # We have an existing chat.  That'll do nicely.
        $id = count($chats) > 0 ? $chats[0]['id'] : NULL;

        if (!$id) {
            # We don't.  Create one.
            $rc = $this->dbhm->preExec("INSERT INTO chat_rooms (user1, groupid, chattype) VALUES (?,?,?)", [
                $user1,
                $groupid,
                ChatRoom::TYPE_USER2MOD
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
            # Start off with the user online.
            $this->updateRoster($user1, NULL, ChatRoom::STATUS_ONLINE);

            # Poke the group mods to let them know to pick up the new chat
            $n = new Notifications($this->dbhr, $this->dbhm);
            
            $n->pokeGroupMods($groupid, [
                'newroom' => $this->id
            ]);
        }

        return($id);
    }

    public function getPublic()
    {
        $ret = $this->getAtts($this->publicatts);

        if (pres('groupid', $ret)) {
            $g = Group::get($this->dbhr, $this->dbhm, $ret['groupid']);
            unset($ret['groupid']);
            $ret['group'] = $g->getPublic();
        }

        if (pres('user1', $ret)) {
            $u = User::get($this->dbhr, $this->dbhm, $ret['user1']);
            unset($ret['user1']);
            $ctx = NULL;
            $ret['user1'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE);

            if (pres('group', $ret)) {
                # As a mod we can see the email
                $ret['user1']['email'] = $u->getEmailPreferred();
            }
        }

        if (pres('user2', $ret)) {
            $u = User::get($this->dbhr, $this->dbhm, $ret['user2']);
            unset($ret['user2']);
            $ctx = NULL;
            $ret['user2'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE);
        }

        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        $ret['unseen'] = $this->unseenForUser($myid);

        # The name we return is not the one we created it with, which is internal.
        switch ($this->chatroom['chattype']) {
            case ChatRoom::TYPE_USER2USER:
                # We use the name of the user who isn't us, because that's who we're chatting to.
                $ret['name'] = ($ret['user1']['id'] != $myid) ? $ret['user1']['displayname'] :
                    $ret['user2']['displayname'];
                break;
            case ChatRoom::TYPE_USER2MOD:
                # If we started it, we're chatting to the group volunteers; otherwise to the user.
                $username = $ret['user1']['displayname'];
                $username = strlen(trim($username)) > 0 ? $username : 'A freegler';
                $email = presdef('email', $ret['user1'], 'No email');
                $ret['name'] = $ret['user1']['id'] == $myid ? "{$ret['group']['namedisplay']} Volunteers" : "$username ($email) on {$ret['group']['nameshort']}";
                break;
            case ChatRoom::TYPE_MOD2MOD:
                # Mods chatting to each other.
                $ret['name'] = "{$ret['group']['namedisplay']} Mods";
                break;
        }

        $refmsgs = $this->dbhr->preQuery("SELECT DISTINCT refmsgid FROM chat_messages WHERE chatid = ?;", [ $this->id ]);
        $ret['refmsgids'] = [];
        foreach ($refmsgs as $refmsg) {
            $ret['refmsgids'][] = $refmsg['refmsgid'];
        }

        $lasts = $this->dbhr->preQuery("SELECT id, date, message FROM chat_messages WHERE chatid = ? AND reviewrequired = 0 ORDER BY id DESC LIMIT 1;", [ $this->id] );
        $ret['lastmsg'] = 0;
        $ret['lastdate'] = NULL;
        $ret['snippet'] = '';

        foreach ($lasts as $last) {
            $ret['lastmsg'] = $last['id'];
            $ret['lastdate'] = ISODate($last['date']);
            $ret['snippet'] = substr($last['message'], 0, 30);
        }
        
        return($ret);
    }

    public function lastSeenForUser($userid) {
        # Find if we have any unseen messages.
        if ($this->chatroom['chattype'] == ChatRoom::TYPE_USER2MOD && $userid != $this->chatroom['user1']) {
            # This is a chat between a user and group mods - and we're checking for a user who isn't the member - so
            # must be the mod.  In that case we only return that messages are unseen if they have not been seen by
            # _any_ of the mods.
            $sql = "SELECT MAX(chat_roster.lastmsgseen) AS lastmsgseen FROM chat_roster INNER JOIN chat_rooms ON chat_roster.chatid = chat_rooms.id WHERE chatid = ? AND userid = ?;";
            $counts = $this->dbhr->preQuery($sql, [$this->id, $userid]);
        } else {
            # No fancy business - just get it from the roster.
            $sql = "SELECT chat_roster.lastmsgseen FROM chat_roster INNER JOIN chat_rooms ON chat_roster.chatid = chat_rooms.id WHERE chatid = ? AND userid = ?;";
            $counts = $this->dbhr->preQuery($sql, [$this->id, $userid]);
        }
        return (count($counts) > 0 ? $counts[0]['lastmsgseen'] : NULL);
    }
    
    public function seenLastForUser($userid) {
        $sql = "UPDATE chat_roster SET lastmsgseen = (SELECT MAX(id) FROM chat_messages WHERE chatid = ?) WHERE userid = ? AND chatid = ?;";
        $this->dbhm->preExec($sql, [
            $this->id,
            $userid,
            $this->id
        ]);
    }

    public function unseenForUser($userid) {
        # Find if we have any unseen messages.  Exclude any pending review.
        $sql = "SELECT COUNT(*) AS count FROM chat_messages WHERE id > COALESCE((SELECT lastmsgseen FROM chat_roster WHERE chatid = ? AND userid = ?), 0) AND chatid = ? AND userid != ? AND reviewrequired = 0 AND reviewrejected = 0;";
        $counts = $this->dbhr->preQuery($sql, [ $this->id, $userid, $this->id, $userid  ]);
        return($counts[0]['count']);
    }

    public function listForUser($userid, $chattypes, $search = NULL, $all = FALSE) {
        $ret = [];
        $u = User::get($this->dbhr, $this->dbhm, $userid);
        $typeq = " AND chattype IN ('" . implode("','", $chattypes) . "') ";

        # The chats we can see are:
        # - either for a group (possibly a modonly one)
        # - a conversation between two users that we have not closed
        #
        # A single query that handles this would be horrific, and having tried it, is also hard to make efficient.  So
        # break it down into smaller queries that have the dual advantage of working quickly and being comprehensible.
        $chatids = [];

        if (in_array(ChatRoom::TYPE_MOD2MOD, $chattypes)) {
            # We want chats marked by groupid for which we are a mod.
            $sql = "SELECT chat_rooms.* FROM chat_rooms INNER JOIN memberships ON memberships.userid = ? AND chat_rooms.groupid = memberships.groupid WHERE memberships.role IN ('Moderator', 'Owner') AND chattype = 'Mod2Mod';";
            #error_log("Group chats $sql, $userid");
            $rooms = $this->dbhr->preQuery($sql, [$userid]);
            foreach ($rooms as $room) {
                $chatids[] = $room['id'];
            }
        }

        if (in_array(ChatRoom::TYPE_USER2MOD, $chattypes)) {
            # If we're on ModTools then we want User2Mod chats for our group.
            #
            # If we're on the user site then we only want User2Mod chats where we are a user.
            $sql = SITE_HOST == USER_SITE ? "SELECT chat_rooms.* FROM chat_rooms WHERE user1 = ? AND chattype = 'User2Mod';" : "SELECT chat_rooms.* FROM chat_rooms INNER JOIN memberships ON memberships.userid = ? AND chat_rooms.groupid = memberships.groupid WHERE chattype = 'User2Mod';";
            $rooms = $this->dbhr->preQuery($sql, [$userid]);
            foreach ($rooms as $room) {
                $chatids[] = $room['id'];
            }
        }

        if (in_array(ChatRoom::TYPE_USER2USER, $chattypes)) {
            # We want chats where we are one of the users.
            $sql = "SELECT chat_rooms.* FROM chat_rooms WHERE (user1 = ? OR user2 = ?) AND chattype = 'User2User';";
            $rooms = $this->dbhr->preQuery($sql, [ $userid, $userid ]);
            foreach ($rooms as $room) {
                $chatids[] = $room['id'];
            }
        }

        #error_log("After group " . var_export($chatids, TRUE));

        # We also want any chats which we feature in.
        $sql = "SELECT id FROM chat_rooms WHERE user1 = ? OR user2 = ?;";
        $users = $this->dbhr->preQuery($sql, [ $userid, $userid ]);
        #error_log("User chats $sql, $userid");
        foreach ($users as $user) {
            $chatids[] = $user['id'];
        }

        #error_log("After user " . var_export($chatids, TRUE));

        if (count($chatids) > 0) {
            $sql = "SELECT chat_rooms.* FROM chat_rooms LEFT JOIN chat_roster ON chat_roster.userid = ? AND chat_rooms.id = chat_roster.chatid WHERE chat_rooms.id IN (" . implode(',', $chatids) . ") $typeq AND (status IS NULL OR status != ?)";
            #error_log($sql . var_export([ $userid, ChatRoom::STATUS_CLOSED ], TRUE));
            $rooms = $this->dbhr->preQuery($sql, [ $userid, ChatRoom::STATUS_CLOSED ]);
            foreach ($rooms as $room) {
                #error_log("Consider {$room['id']} group {$room['groupid']} modonly {$room['modonly']} " . $u->isModOrOwner($room['groupid']));
                $cansee = FALSE;
                switch ($room['chattype']) {
                    case ChatRoom::TYPE_USER2USER:
                        # We can see this if we're one of the users.
                        # TODO or a mod on the group.
                        $cansee = ($userid == $room['user1'] || $userid == $room['user2']);
                        if ($cansee) {
                            # We also don't want to see non-empty chats where all the messages are held for review, because they are likely to
                            # be spam.
                            $unheld = $this->dbhr->preQuery("SELECT CASE WHEN reviewrequired = 0 AND reviewrejected = 0 THEN 1 ELSE 0 END AS valid, COUNT(*) AS count FROM chat_messages WHERE chatid = ? GROUP BY (reviewrequired = 0 AND reviewrejected = 0) ORDER BY valid ASC;", [
                                $room['id']
                            ]);

                            $validcount = 0;
                            $invalidcount = 0;
                            foreach ($unheld as $un) {
                                $validcount = ($un['valid'] == 1) ? ++$validcount : $validcount;
                                $invalidcount = ($un['valid'] == 0) ? ++$invalidcount : $invalidcount;
                            }

                            $cansee = count($unheld) == 0 || $validcount > 0;
                            #error_log("Cansee for {$room['id']} is $cansee from " . var_export($unheld, TRUE));
                        }

                        break;
                    case ChatRoom::TYPE_MOD2MOD:
                        # We can see this if we're one of the mods.
                        $cansee = $u->isModOrOwner($room['groupid']);
                        break;
                    case ChatRoom::TYPE_USER2MOD:
                        # We can see this if we're one of the mods on the group, or the user who started it.
                        $cansee = $u->isModOrOwner($room['groupid']) || $userid == $room['user1'];
                        break;
                }

                if ($cansee) {
                    $show = TRUE;

                    if ($search) {
                        # We want to apply a search filter.
                        if (stripos($room['name'], $search) === FALSE) {
                            # We didn't get a match easily.  Now we have to search in the messages.
                            $searchq = $this->dbhr->quote("%$search%");
                            $sql = "SELECT chat_messages.id FROM chat_messages LEFT OUTER JOIN messages ON messages.id = chat_messages.refmsgid WHERE chatid = {$room['id']} AND (chat_messages.message LIKE $searchq OR messages.subject LIKE $searchq) LIMIT 1;";
                            error_log($sql);
                            $msgs = $this->dbhr->preQuery($sql);

                            $show = count($msgs) > 0;
                        }
                    }

                    if ($show && $room['chattype'] == ChatRoom::TYPE_MOD2MOD && $room['groupid']) {
                        # See if the group allows chat.
                        $g = Group::get($this->dbhr, $this->dbhm, $room['groupid']);
                        $show = $g->getSetting('showchat', TRUE);
                    }

                    if ($show && !$all) {
                        # Last check - do we have a recent enough message?
                        $msgs = $this->dbhr->preQuery("SELECT MAX(date) AS maxdate FROM chat_messages WHERE chatid = {$room['id']};");

                        # We pass this if we have had no messages, or if the last one was recent.
                        $show = !$msgs[0]['maxdate'] || (strtotime($msgs[0]['maxdate']) > strtotime("Midnight 31 days ago"));
                    }

                    if ($show) {
                        $ret[] = $room['id'];
                    }
                }
            }
        }

        return(count($ret) == 0 ? NULL : $ret);
    }

    public function canSee($userid) {
        if ($userid == $this->chatroom['user1'] || $userid == $this->chatroom['user2']) {
            # It's one of ours - so we can see it.
            $cansee = TRUE;
        } else {
            # It might be a group chat which we can see.
            $rooms = $this->listForUser($userid, [ $this->chatroom['chattype'] ], NULL, TRUE);
            #error_log("CanSee $userid, {$this->id}, " . var_export($rooms, TRUE));
            $cansee = $rooms ? in_array($this->id, $rooms) : FALSE;
        }

        if (!$cansee) {
            # If we can't see it by right, but we are a mod for the users in the chat, then we can see it.
            #error_log("$userid can't see {$this->id} of type {$this->chatroom['chattype']}");
            $me = whoAmI($this->dbhr, $this->dbhm);

            if ($me) {
                if ($me->isAdminOrSupport() ||
                    ($this->chatroom['chattype'] == ChatRoom::TYPE_USER2USER &&
                     ($me->moderatorForUser($this->chatroom['user1']) ||
                      $me->moderatorForUser($this->chatroom['user2'])))) {
                    $cansee = TRUE;
                }
            }
        }

        return($cansee);
    }

    public function updateRoster($userid, $lastmsgseen, $status) {
        # We have a unique key, and an update on current timestamp.
        #
        # Don't want to log these - lots of them.
        $this->dbhm->preExec("INSERT INTO chat_roster (chatid, userid, lastip) VALUES (?,?,?) ON DUPLICATE KEY UPDATE lastip = ?;",
            [
                $this->id,
                $userid,
                presdef('REMOTE_ADDR', $_SERVER, NULL),
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
            # Now we want to check whether to check whether this message has been seen by everyone in this chat.  If it
            # has, we flag it as seen by all, which speeds up our checking for which email notifications to send out.
            $sql = "SELECT COUNT(*) AS count FROM chat_roster WHERE chatid = ? AND (lastmsgseen IS NULL OR lastmsgseen < ?);";
            #error_log("Check for unseen $sql, {$this->id}, $lastmsgseen");

            $unseens = $this->dbhm->preQuery($sql,
                [
                    $this->id,
                    $lastmsgseen
                ]);

            if ($unseens[0]['count'] == 0) {
                $sql = "UPDATE chat_messages SET seenbyall = 1 WHERE chatid = ? AND id <= ?;";
                $this->dbhm->preExec($sql, [ $this->id, $lastmsgseen ]);
            }
        }

        $this->dbhm->preExec("UPDATE chat_roster SET date = NOW(), status = ? WHERE chatid = ? AND userid = ?;",
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
            $u = User::get($this->dbhr, $this->dbhm, $rost['userid']);
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

        $userids = [];
        $group = NULL;
        $mods = FALSE;

        switch ($this->chatroom['chattype']) {
            case ChatRoom::TYPE_USER2USER:
                # Poke both users.
                $userids[] = $this->chatroom['user1'];
                $userids[] = $this->chatroom['user2'];
                break;
            case ChatRoom::TYPE_USER2MOD:
                # Poke the initiator and all group mods.
                $userids[] = $this->chatroom['user1'];
                $mods = TRUE;
                break;
            case ChatRoom::TYPE_MOD2MOD:
                # If this is a group chat we poke all mods.
                $mods = TRUE;
                break;
        }


        $n = new Notifications($this->dbhr, $this->dbhm);
        $count = 0;

        #error_log("Chat #{$this->id} Poke mods $mods users " . var_export($userids, TRUE));

        foreach ($userids as $userid) {
            #error_log("Poke {$rost['userid']} for {$this->id}");
            $n->poke($userid, $data);
            $count++;
        }

        if ($mods) {
            $count += $n->pokeGroupMods($this->chatroom['groupid'], $data);
        }

        return($count);
    }

    public function getMessagesForReview($user, &$ctx) {
        # We want the messages for review for any group where we are a mod and the recipient of the chat message is
        # a member, where the group wants us to do this.
        $userid = $user->getId();
        $msgid = $ctx ? $ctx['msgid'] : 0;
        $sql = "SELECT chat_messages.id, chat_messages.chatid, chat_messages.userid, memberships.groupid FROM chat_messages INNER JOIN chat_rooms ON reviewrequired = 1 AND chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_messages.userid = chat_rooms.user1 THEN chat_rooms.user2 ELSE chat_rooms.user1 END) AND memberships.groupid IN (SELECT groupid FROM memberships WHERE chat_messages.id > ? AND memberships.userid = ? AND memberships.role IN ('Owner', 'Moderator'))  INNER JOIN groups ON memberships.groupid = groups.id AND ((groups.type = 'Freegle' AND groups.settings IS NULL) OR INSTR(groups.settings, '\"chatreview\":1') != 0) ORDER BY chat_messages.id ASC;";
        $msgs = $this->dbhr->preQuery($sql, [ $msgid, $userid  ]);
        $ret = [];

        $ctx = $ctx ? $ctx : [];

        foreach ($msgs as $msg) {
            # This might be for a group which we are a mod on but don't actually want to see.
            $mysettings = $user->getGroupSettings($msg['groupid']);
            $showmessages = !array_key_exists('showmessages', $mysettings) || $mysettings['showmessages'];

            if ($showmessages) {
                $m = new ChatMessage($this->dbhr, $this->dbhm, $msg['id']);
                $thisone = $m->getPublic();
                $r = new ChatRoom($this->dbhr, $this->dbhm, $msg['chatid']);
                $thisone['chatroom'] = $r->getPublic();

                $u = User::get($this->dbhr, $this->dbhm, $msg['userid']);
                $thisone['fromuser'] = $u->getPublic();

                $touserid = $msg['userid'] == $thisone['chatroom']['user1']['id'] ? $thisone['chatroom']['user2']['id'] : $thisone['chatroom']['user1']['id'];
                $u = User::get($this->dbhr, $this->dbhm, $touserid);
                $thisone['touser'] = $u->getPublic();

                $g = Group::get($this->dbhr, $this->dbhm, $msg['groupid']);
                $thisone['group'] = $g->getPublic();

                $thisone['date'] = ISODate($thisone['date']);

                $ctx['msgid'] = $msg['id'];

                $ret[] = $thisone;
            }
        }

        return($ret);
    }

    public function getMessages($limit = 100) {
        $sql = "SELECT id, userid FROM chat_messages WHERE chatid = ? ORDER BY date DESC LIMIT $limit;";
        $msgs = $this->dbhr->preQuery($sql, [ $this->id ]);
        $msgs = array_reverse($msgs);
        $users = [];

        $ret = [];
        $lastuser = NULL;
        $lastmsg = NULL;

        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        $modaccess = FALSE;

        if ($myid && $myid != $this->chatroom['user1'] && $myid != $this->chatroom['user1']) {
            $modaccess = $me->moderatorForUser($this->chatroom['user1']) ||
                $me->moderatorForUser($this->chatroom['user2']);
        }

        foreach ($msgs as $msg) {
            $m = new ChatMessage($this->dbhr, $this->dbhm, $msg['id']);
            $atts = $m->getPublic();

            if ($atts['reviewrequired'] && $msg['userid'] != $myid && !$modaccess) {
                # This message is held for review, and we didn't send it.  So we shouldn't see it.
            } else if ($atts['reviewrejected']) {
                # This message was reviewed and deemed unsuitable.  So we shouldn't see it.
            } else {
                # We should return this one.
                unset($atts['reviewrequired']);
                unset($atts['reviewedby']);
                unset($atts['reviewrejected']);
                $atts['date'] = ISODate($atts['date']);

                $atts['sameaslast'] = ($lastuser === $msg['userid']);

                if (count($ret) > 0) {
                    $ret[count($ret) - 1]['sameasnext'] = ($lastuser === $msg['userid']);
                }

                if (!array_key_exists($msg['userid'], $users)) {
                    $u = User::get($this->dbhr, $this->dbhm, $msg['userid']);
                    $users[$msg['userid']] = $u->getPublic(NULL, FALSE);
                }

                $ret[] = $atts;
                $lastuser = $msg['userid'];
            }
        }

        return([$ret, $users]);
    }

    public function lastSeenByAll() {
        $sql = "SELECT MAX(id) AS maxid FROM chat_messages WHERE chatid = ? AND seenbyall = 1;";
        $lasts = $this->dbhr->preQuery($sql, [ $this->id ]);
        $ret = NULL;

        foreach ($lasts as $last) {
            $ret = $last['maxid'];
        }

        return($ret);
    }

    public function getMembersNotSeen($lastseenbyall, $lastmessage, $age = 300) {
        # TODO We should chase for group chats too.
        # There are some general restrictions on when we email:
        # - We don't chase before someone has been offline for five minutes, which saves people who are
        #   using the site getting a bunch of emails for chats they've already seen.
        # - We don't email if we've already emailed for this message, unless that was a day ago.  That means
        #   we remind people daily when they have unread messages.
        # - When we have a new message since our last email, we don't email more often than hourly, so that if
        #   someone keeps hammering away in chat we don't flood the recipient with emails.
        $ret = [];
        #error_log("Get not seen {$this->chatroom['chattype']}");

        if ($this->chatroom['chattype'] == ChatRoom::TYPE_USER2USER) {
            # This is a conversation between two users.  They're both in the roster so we can see what their last
            # seen message was and decide who to chase.
            #
            # Used to remail - but that never stops if they don't visit the site.
            # OR ((lastmsgemailed = ? AND TIMESTAMPDIFF(HOUR, lastemailed, NOW()) > 24)
            $sql = "SELECT TIMESTAMPDIFF(SECOND, date, NOW()) AS secondsago, chat_roster.* FROM chat_roster WHERE chatid = ? HAVING secondsago >= $age AND lastemailed IS NULL OR (lastmsgemailed < ? AND TIMESTAMPDIFF(HOUR, lastemailed, NOW()) > 1);";
            #error_log("$sql {$this->id}, $lastmessage");
            $users = $this->dbhr->preQuery($sql, [ $this->id, $lastmessage ]);
            foreach ($users as $user) {
                #error_log("Last by all $lastseenbyall vs {$user['lastmsgseen']}, last message $lastmessage");
                # What's the max message this user has either seen or been mailed?
                $maxseen = presdef('lastmsgseen', $user, 0);
                $maxmailed = presdef('lastmsgemailed', $user, 0);
                $max = max($maxseen, $maxmailed);
                #error_log("Max seen $maxseen mailed $maxmailed max $max");

                if ($max < $lastmessage) {
                    # This user hasn't seen all the messages.
                    #error_log("Need to see this");
                    $ret[] = [
                        'userid' => $user['userid'],
                        'lastmsgseen' => $user['lastmsgseen'],
                        'lastmsgemailed' => $user['lastmsgemailed'],
                        'last' => $max,
                        'role' => User::ROLE_MEMBER
                    ];
                }
            }
        } else if ($this->chatroom['chattype'] == ChatRoom::TYPE_USER2MOD) {
            # This is a conversation between a user, and the mods of a group.  We chase the user if they've not
            # seen/been chased, and all the mods if none of them have seen/been chased.
            #
            # First the user.
            $sql = "SELECT TIMESTAMPDIFF(SECOND, chat_roster.date, NOW()) AS secondsago, chat_roster.* FROM chat_roster INNER JOIN chat_rooms ON chat_rooms.id = chat_roster.chatid WHERE chatid = ? AND chat_roster.userid = chat_rooms.user1 HAVING secondsago >= $age AND lastemailed IS NULL OR (lastmsgemailed < ? AND TIMESTAMPDIFF(HOUR, lastemailed, NOW()) > 1);";
            $users = $this->dbhr->preQuery($sql, [ $this->id, $lastmessage ]);

            foreach ($users as $user) {
                $maxseen = presdef('lastmsgseen', $user, 0);
                $maxmailed = presdef('lastmsgemailed', $user, 0);
                $max = max($maxseen, $maxmailed);

                if ($max < $lastmessage) {
                    # We've not seen any messages, or seen some but not this one.
                    $ret[] = [
                        'userid' => $user['userid'],
                        'lastmsgseen' => $user['lastmsgseen'],
                        'lastmsgemailed' => $user['lastmsgemailed'],
                        'last' => $max,
                        'role' => User::ROLE_MEMBER
                    ];
                }
            }

            # Now the mods.
            #
            # First get the mods.
            $mods = $this->dbhr->preQuery("SELECT userid FROM memberships WHERE groupid = ? AND role IN ('Owner', 'Moderator');", [
                $this->chatroom['groupid']
            ]);

            $modids = [];

            foreach ($mods as $mod) {
                $modids[] = $mod['userid'];
            }

            if (count($modids) > 0) {
                # If for some reason we have no mods, we can't mail them.
                #
                # Now get any current roster information.
                $modseen = FALSE;
                $rosters = $this->dbhr->preQuery("SELECT * FROM chat_roster WHERE chatid = ? AND userid IN (" . implode(',', $modids) . ");",
                    [
                        $this->id
                    ]);
                foreach ($rosters as $roster) {
                    #error_log("Chat {$this->id} " . var_export($roster, TRUE));
                    #error_log("{$roster['userid']} mailed {$roster['lastmsgemailed']} vs $lastmessage");
                    if ($roster['lastmsgemailed'] >= $lastmessage || $roster['lastmsgseen'] >= $lastmessage) {
                        $modseen = TRUE;
                    }
                }

                if (!$modseen) {
                    # We will need to mail them.  First add any remaining mods into the roster so that we can record
                    # what we do.
                    foreach ($mods as $mod) {
                        $sql = "INSERT IGNORE INTO chat_roster (chatid, userid, status) VALUES (?, ?, 'Offline');";
                        $this->dbhm->preExec($sql, [ $this->id, $mod['userid']]);
                    }

                    # Now return info to trigger mails to all mods.
                    $rosters = $this->dbhr->preQuery("SELECT * FROM chat_roster WHERE chatid = ? AND userid IN (" . implode(',', $modids) . ");",
                        [
                            $this->id
                        ]);
                    foreach ($rosters as $roster) {
                        $maxseen = presdef('lastmsgseen', $roster, 0);
                        $maxmailed = presdef('lastemailed', $roster, 0);
                        $max = max($maxseen, $maxmailed);

                        $ret[] = [
                            'userid' => $roster['userid'],
                            'lastmsgseen' => $roster['lastmsgseen'],
                            'lastmsgemailed' => $roster['lastmsgemailed'],
                            'last' => $max,
                            'role' => User::ROLE_MODERATOR
                        ];
                    }
                }
            }
        }

        return($ret);
    }

    public function notifyByEmail($chatid = NULL, $chattype, $age = 300) {
        # We want to find chatrooms with messages which haven't been seen by people who should have seen them.
        # These could either be a group chatroom, or a conversation.  There aren't too many of the former, but there
        # could be a large number of the latter.  However we don't want to keep nagging people forever - so we are
        # only interested in rooms containing a message which was posted recently and which has not been seen by all
        # members - which is a much smaller set.
        $start = date('Y-m-d', strtotime("midnight 2 weeks ago"));
        $chatq = $chatid ? " AND chatid = $chatid " : '';
        $sql = "SELECT DISTINCT chatid, chat_rooms.groupid FROM chat_messages INNER JOIN chat_rooms ON chat_messages.chatid = chat_rooms.id WHERE date >= ? AND seenbyall = 0 AND chattype = ? $chatq;";
        #error_log("$sql, $start, $chattype");
        $chats = $this->dbhr->preQuery($sql, [ $start, $chattype ]);
        $notified = 0;

        foreach ($chats as $chat) {
            # Different members of the chat might have seen different messages.
            #error_log("Check chat {$chat['chatid']}");
            $r = new ChatRoom($this->dbhr, $this->dbhm, $chat['chatid']);
            $chatatts = $r->getPublic();
            $lastseen = $r->lastSeenByAll();
            #error_log("Last seen by all $lastseen");
            $notseenby = $r->getMembersNotSeen($lastseen ? $lastseen : 0, $chatatts['lastmsg'], $age);
            #error_log("Members not seen " . var_export($notseenby, TRUE));

            foreach ($notseenby as $member) {
                #error_log("Not seen {$member['userid']}");
                # Now we have a member who has not seen all of the messages in this chat.  Find the other one.
                $other = $member['userid'] == $chatatts['user1']['id'] ? $chatatts['user2']['id'] : $chatatts['user1']['id'];
                $otheru = User::get($this->dbhr, $this->dbhm, $other);
                $thisu = User::get($this->dbhr, $this->dbhm, $member['userid']);
                
                # Now collect a summary of what they've missed.
                $unseenmsgs = $this->dbhr->preQuery("SELECT * FROM chat_messages WHERE chatid = ? AND id > ? AND reviewrequired = 0 AND reviewrejected = 0 ORDER BY id ASC;",
                    [
                        $chat['chatid'],
                        $member['last']
                    ]);

                #error_log("Unseen " . var_export($unseenmsgs, TRUE));
                if (count($unseenmsgs) > 0) {
                    $textsummary = '';
                    $htmlsummary = '';
                    $lastmsgemailed = 0;
                    foreach ($unseenmsgs as $unseenmsg) {
                        if (pres('message', $unseenmsg)) {
                            $thisone = $unseenmsg['message'];
                            $textsummary .= $thisone . "\r\n";
                            $htmlsummary .= nl2br($thisone) . "<br>";
                            $lastmsgemailed = max($lastmsgemailed, $unseenmsg['id']);
                        }
                    }

                    if ($textsummary != '') {
                        # As a subject, we should use the last referenced message in this chat.
                        $sql = "SELECT subject FROM messages INNER JOIN chat_messages ON chat_messages.refmsgid = messages.id WHERE chatid = ? ORDER BY chat_messages.id DESC LIMIT 1;";
                        #error_log($sql . $chat['chatid']);
                        $subjs = $this->dbhr->preQuery($sql, [
                            $chat['chatid']
                        ]);
                        #error_log(var_export($subjs, TRUE));

                        switch ($chattype) {
                            case ChatRoom::TYPE_USER2USER:
                                $subject = count($subjs) == 0 ? "You have a new message" : "Re: {$subjs[0]['subject']}";
                                $fromname = $otheru->getName();
                                $site = USER_SITE;
                                break;
                            case ChatRoom::TYPE_USER2MOD:
                                # We might either be notifying a user, or the mods.
                                $g = Group::get($this->dbhr, $this->dbhm, $chat['groupid']);
                                if ($member['role'] == User::ROLE_MEMBER) {
                                    $subject = "You have a message from the " . $g->getPublic()['namedisplay'] . " volunteers";
                                    $fromname = $g->getPublic()['namedisplay'];
                                    $site = USER_SITE;
                                } else {
                                    $subject = "Member message on " . $g->getPrivate('nameshort') . " from " . $otheru->getName() . " (" . $otheru->getEmailPreferred() . ")";
                                    $fromname = $otheru->getName();
                                    $site = MOD_SITE;
                                }
                                break;
//                            case ChatRoom::TYPE_MOD2MOD:
//                                $subject = "New messages in Mod Chat";
//                                $site = MOD_SITE;
//                                break;
                        }

                        # Construct the SMTP message.
                        # - The text bodypart is just the user text.  This means that people who aren't showing HTML won't see
                        #   all the wrapping.  It also means that the kinds of preview notification popups you get on mail
                        #   clients will show something interesting.
                        # - The HTML bodypart will show the user text, but in a way that is designed to encourage people to
                        #   click and reply on the web rather than by email.  This reduces the problems we have with quoting,
                        #   and encourages people to use the (better) web interface, while still allowing email replies for
                        #   those users who prefer it.  Because we put the text they're replying to inside a visual wrapping,
                        #   it's less likely that they will interleave their response inside it - they will probably reply at
                        #   the top or end.  This makes it easier for us, when processing their replies, to spot the text they
                        #   added.
                        $url = $thisu->loginLink($site, $member['userid'], '/chat/' . $chat['chatid']);

                        switch ($chattype) {
                            case ChatRoom::TYPE_USER2USER:
                                $html = chat_notify($site, $chatatts['chattype'] == ChatRoom::TYPE_MOD2MOD  ? MODLOGO : USERLOGO, $fromname, $thisu->getId(), $url,
                                    $htmlsummary, $thisu->getUnsubLink($site, $member['userid']));
                                break;
                            case ChatRoom::TYPE_USER2MOD:
                                if ($member['role'] == User::ROLE_MEMBER) {
                                    $html = chat_notify($site, $chatatts['chattype'] == ChatRoom::TYPE_MOD2MOD  ? MODLOGO : USERLOGO, $fromname, $thisu->getId(), $url,
                                        $htmlsummary, $thisu->getUnsubLink($site, $member['userid']));
                                } else {
                                    $html = chat_notify_mod($site, MODLOGO, $fromname, $url, $htmlsummary, SUPPORT_ADDR);
                                }
                                break;
                        }

                        # We ask them to reply to an email address which will direct us back to this chat.
                        $replyto = 'notify-' . $chat['chatid'] . '-' . $member['userid'] . '@' . USER_DOMAIN;
                        $to = $thisu->getEmailPreferred();

                        # ModTools users should never get notified
                        if ($to && strpos($to, '@' . MOD_SITE) === FALSE) {
                            error_log("Notify chat #{$chat['chatid']} $to for {$member['userid']} $subject");
                            try {
                                #$to = 'log@ehibbert.org.uk';
                                $message = $this->constructMessage($thisu, $member['userid'], $thisu->getName(), $to, $fromname, $replyto, $subject, $textsummary, $html);
                                $this->mailer($message);

                                $this->dbhm->preExec("UPDATE chat_roster SET lastemailed = NOW(), lastmsgemailed = ? WHERE userid = ? AND chatid = ?;", [
                                    $lastmsgemailed,
                                    $member['userid'],
                                    $chat['chatid']
                                ]);

                                $notified++;
                            } catch (Exception $e) {
                                error_log("Send to {$member['userid']} failed with " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }

        return($notified);
    }

    public function chaseupMods($id = NULL, $age = 566400) {
        $notreplied = [];

        # Chase up recent User2Mod chats where there has been no mod input.
        $mysqltime = date ("Y-m-d", strtotime("Midnight 7 days ago"));
        $idq = $id ? " AND chat_rooms.id = $id " : '';
        $sql = "SELECT DISTINCT chat_rooms.id FROM chat_rooms INNER JOIN chat_messages ON chat_rooms.id = chat_messages.chatid WHERE chat_messages.date >= '$mysqltime' AND chat_rooms.chattype = 'User2Mod' $idq;";
        $chats = $this->dbhr->preQuery($sql);
        
        foreach ($chats as $chat) {
            $c = new ChatRoom($this->dbhr, $this->dbhm, $chat['id']);
            list ($msgs, $users) = $c->getMessages();

            # If we have only one user in here then it must tbe the one who started the query.
            if (count($users) == 1) {
                foreach ($users as $uid => $user) {
                    $u = new User($this->dbhr, $this->dbhm, $uid);
                    $msgs = array_reverse($msgs);
                    $last = $msgs[0];
                    $timeago = strtotime($last['date']);

                    $groupid = $c->getPrivate('groupid');
                    $role = $u->getRoleForGroup($groupid);

                    # Don't chaseup for non-member or mod/owner queries.
                    if ($role == User::ROLE_MEMBER && time() - $timeago >= $age) {
                        $g = new Group($this->dbhr, $this->dbhm, $groupid);

                        if ($g->getPrivate('type') == Group::GROUP_FREEGLE) {
                            error_log("{$chat['id']} on " . $g->getPrivate('nameshort') . " to " . $u->getName() . " (" . $u->getEmailPreferred() . ") last message {$last['date']} total " . count($msgs));

                            if (!array_key_exists($groupid, $notreplied)) {
                                $notreplied[$groupid] = [];

                                # Construct a message.
                                $url = 'https://' . MOD_SITE . '/chat/' . $chat['id'];
                                $subject = "Member message on " . $g->getPrivate('nameshort') . " from " . $u->getName() . " (" . $u->getEmailPreferred() . ")";
                                $fromname = $u->getName();

                                $textsummary = '';
                                $htmlsummary = '';
                                $msgs = array_reverse($msgs);

                                foreach ($msgs as $unseenmsg) {
                                    if (pres('message', $unseenmsg)) {
                                        $thisone = $unseenmsg['message'];
                                        $textsummary .= $thisone . "\r\n";
                                        $htmlsummary .= nl2br($thisone) . "<br>";
                                    }
                                }

                                $html = chat_chaseup_mod(MOD_SITE, MODLOGO, $fromname, $url, $htmlsummary);

                                # Get the mods.
                                $mods = $g->getMods();

                                foreach ($mods as $modid) {
                                    $thisu = User::get($this->dbhr, $this->dbhm, $modid);
                                    # We ask them to reply to an email address which will direct us back to this chat.
                                    $replyto = 'notify-' . $chat['id'] . '-' . $uid . '@' . USER_DOMAIN;
                                    $to = $thisu->getEmailPreferred();
                                    $message = Swift_Message::newInstance()
                                        ->setSubject($subject)
                                        ->setFrom([NOREPLY_ADDR => $fromname])
                                        ->setTo([$to => $thisu->getName()])
                                        ->setReplyTo($replyto)
                                        ->setBody($textsummary);
                                    $message->addPart($html, 'text/html');
                                    $this->mailer($message);
                                }
                            }

                            $notreplied[$groupid][] = $c;
                        }
                    }
                }
            }
        }

        foreach ($notreplied as $groupid => $chatlist) {
            $g = new Group($this->dbhr, $this->dbhm, $groupid);
            error_log("#$groupid " . $g->getPrivate('nameshort') . " " . count($chatlist));
        }
        
        return($chats);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM chat_rooms WHERE id = ?;", [$this->id]);
        return($rc);
    }
}