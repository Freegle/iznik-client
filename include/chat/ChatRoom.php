<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');
require_once(IZNIK_BASE . '/mailtemplates/chat_notify.php');

class ChatRoom extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'groupid', 'modonly', 'modtools', 'description', 'user1', 'user2');
    var $settableatts = array('name', 'description');

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
            ->setBody($text)
            ->addPart($html, 'text/html');
        $headers = $message->getHeaders();

        # TODO
        $message->addTo('log@ehibbert.org.uk');

        $headers->addTextHeader('List-Unsubscribe', $u->listUnsubscribe(USER_SITE, $id));

        return($message);
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
        $myid = $me ? $me->getId() : NULL;

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

        $lasts = $this->dbhr->preQuery("SELECT id, date, message FROM chat_messages WHERE chatid = ? ORDER BY id DESC LIMIT 1;", [ $this->id] );
        foreach ($lasts as $last) {
            $ret['lastmsg'] = $last['id'];
            $ret['lastdate'] = ISODate($last['date']);
            $ret['snippet'] = substr($last['message'], 0, 30);
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
    
    public function seenLastForUser($userid) {
        $sql = "UPDATE chat_roster SET lastmsgseen = (SELECT MAX(id) FROM chat_messages WHERE chatid = ?) WHERE userid = ? AND chatid = ?;";
        $this->dbhm->preExec($sql, [
            $this->id,
            $userid,
            $this->id
        ]);
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
            error_log("Poke {$rost['userid']} for {$this->id}");
            $n->poke($rost['userid'], $data);
            $count++;
        }

        return($count);
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
        $ret = [];
        if ($this->chatroom['user1']) {
            # This is a conversation between two users.  They're both in the roster so we can see what their last
            # seen message was and decide who to chase.
            #
            # There are restrictions on when we email:
            # - We don't chase before someone has been offline for five minutes, which saves people who are
            #   using the site getting a bunch of emails for chats they've already seen.
            # - We don't email if we've already emailed for this message, unless that was a day ago.  That means
            #   we remind people daily when they have unread messages.
            # - When we have a new message since our last email, we don't email more often than hourly, so that if
            #   someone keeps hammering away in chat we don't flood the recipient with emails.
            $sql = "SELECT TIMESTAMPDIFF(SECOND, date, NOW()) AS secondsago, chat_roster.* FROM chat_roster WHERE chatid = ? HAVING secondsago >= $age AND lastemailed IS NULL OR ((lastmsgemailed = ? AND TIMESTAMPDIFF(HOUR, lastemailed, NOW()) > 24) OR (lastmsgemailed < ? AND TIMESTAMPDIFF(HOUR, lastemailed, NOW()) > 1));";
            #error_log("$sql {$this->id}, $lastmessage");
            $users = $this->dbhr->preQuery($sql, [ $this->id, $lastmessage, $lastmessage ]);
            foreach ($users as $user) {
                if (!$user['lastmsgseen'] || $user['lastmsgseen'] < $lastseenbyall) {
                    # TODO
                    # At the moment, we only want to send chaseups to users who are testing the new site.  Check if
                    # they have a suitable email.
                    $u = new User($this->dbhr, $this->dbhm, $user['userid']);
                    $emails = $u->getEmails();
                    $newsite = FALSE;
                    foreach ($emails as $email) {
                        if (stripos($email['email'], USER_DOMAIN) !== FALSE) {
                            $newsite = TRUE;
                        }
                    }

                    if ($newsite) {
                        # We've not seen any messages, or seen some but not this one.
                        $ret[] = [ 'userid' => $user['userid'], 'lastmsgseen' => $user['lastmsgseen'] ];
                    }
                }
            }
        }

        return($ret);
    }

    public function notifyByEmail($chatid = NULL, $user = TRUE, $age = 300) {
        # We want to find chatrooms with messages which haven't been seen by people who should have seen them.
        # These could either be a group chatroom, or a conversation.  There aren't too many of the former, but there
        # could be a large number of the latter.  However we don't want to keep nagging people forever - so we are
        # only interested in rooms containing a message which was posted recently and which has not been seen by all
        # members - which is a much smaller set.
        $start = date('Y-m-d', strtotime("midnight 2 weeks ago"));
        $chatq = $chatid ? " AND chatid = $chatid " : '';
        $modq = $user ? 0 : 1;
        $sql = "SELECT DISTINCT chatid FROM chat_messages INNER JOIN chat_rooms ON chat_messages.chatid = chat_rooms.id WHERE date >= '$start' AND seenbyall = 0 AND modtools = $modq $chatq;";
        #error_log("$sql");
        $chats = $this->dbhr->preQuery($sql, [ $start ]);
        $notified = 0;

        list ($transport, $mailer) = getMailer();
        
        foreach ($chats as $chat) {
            # Different members of the chat might have seen different messages.
            error_log("Check chat {$chat['chatid']}");
            $r = new ChatRoom($this->dbhr, $this->dbhm, $chat['chatid']);
            $chatatts = $r->getPublic();
            $lastseen = $r->lastSeenByAll();
            $notseenby = $r->getMembersNotSeen($lastseen ? $lastseen : 0, $chatatts['lastmsg'], $age);

            foreach ($notseenby as $member) {
                #error_log("Not seen {$member['userid']}");
                # Now we have a member who has not seen all of the messages in this chat.  Find the other one.
                $other = $member['userid'] == $chatatts['user1']['id'] ? $chatatts['user2']['id'] : $chatatts['user1']['id'];
                $otheru = new User($this->dbhr, $this->dbhm, $other);
                $fromname = $otheru->getName();

                $thisu = new User($this->dbhr, $this->dbhm, $member['userid']);
                
                # Now collect a summary of what they've missed.
                $minmsg = $member['lastmsgseen'] ? $member['lastmsgseen'] : 0;
                $unseenmsgs = $this->dbhr->preQuery("SELECT * FROM chat_messages WHERE chatid = ? AND id > ? ORDER BY id ASC;",
                    [
                        $chat['chatid'],
                        $minmsg
                    ]);

                $textsummary = '';
                $htmlsummary = '';
                $lastmsgemailed = 0;
                foreach ($unseenmsgs as $unseenmsg) {
                    if (pres('message', $unseenmsg)) {
                        $thisone = $unseenmsg['message'];
                        $textsummary .= $thisone . "\r\n";
                        $htmlsummary .= $thisone . "<br>";
                        $lastmsgemailed = max($lastmsgemailed, $unseenmsg['id']);
                    }
                }

                # As a subject, we should use the last referenced message in this chat.
                $sql = "SELECT subject FROM messages INNER JOIN chat_messages ON chat_messages.refmsgid = messages.id WHERE chatid = ? AND chat_messages.type = ? ORDER BY chat_messages.id DESC LIMIT 1;";
                #error_log($sql . $chat['chatid']);
                $subjs = $this->dbhr->preQuery($sql, [
                    $chat['chatid'],
                    ChatMessage::TYPE_INTERESTED,
                ]);
                #error_log(var_export($subjs, TRUE));

                $subject = "You have a new message";
                foreach ($subjs as $subj) {
                    $subject = 'Re: ' . $subj['subject'];
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
                $site = $chatatts['modtools'] ? MOD_SITE : USER_SITE;
                $url = $thisu->loginLink($site, $member['userid'], '/chat/' . $chat['chatid']);
                $html = chat_notify($site, $chatatts['modtools'] ? MODLOGO : USERLOGO, $fromname, $url,
                    $htmlsummary, $thisu->getUnsubLink($site, $member['userid']));

                # We ask them to reply to an email address which will direct us back to this chat.
                $replyto = 'notify-' . $chat['chatid'] . '-' . $member['userid'] . '@' . USER_DOMAIN;
                $to = $thisu->getEmailPreferred();

                $message = $this->constructMessage($thisu, $member['userid'], $thisu->getName(), $to, $fromname, $replyto, $subject, $textsummary, $html);
                try {
                    error_log($to . " " . $subject);
                    $mailer->send($message);

                    $this->dbhm->preExec("UPDATE chat_roster SET lastemailed = NOW(), lastmsgemailed = ? WHERE userid = ? AND chatid = ?;", [
                        $lastmsgemailed,
                        $member['userid'],
                        $chat['chatid']
                    ]);
                } catch (Exception $e) {
                    error_log("Send failed with " . $e->getMessage());
                }

                $notified++;
            }
        }

        return($notified);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM chat_rooms WHERE id = ?;", [$this->id]);
        return($rc);
    }
}