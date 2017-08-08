<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/Address.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/chat/ChatRoom.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

class ChatMessage extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'chatid', 'userid', 'date', 'message', 'system', 'refmsgid', 'type', 'seenbyall', 'mailedtoall', 'reviewrequired', 'reviewedby', 'reviewrejected', 'spamscore', 'reportreason', 'refchatid', 'imageid');
    var $settableatts = array('name');

    const TYPE_DEFAULT = 'Default';
    const TYPE_MODMAIL = 'ModMail';
    const TYPE_SYSTEM = 'System';
    const TYPE_INTERESTED = 'Interested';
    const TYPE_PROMISED = 'Promised';
    const TYPE_RENEGED = 'Reneged';
    const TYPE_REPORTEDUSER = 'ReportedUser';
    const TYPE_COMPLETED = 'Completed';
    const TYPE_IMAGE = 'Image';
    const TYPE_ADDRESS = 'Address';

    const ACTION_APPROVE = 'Approve';
    const ACTION_REJECT = 'Reject';

    /** @var  $log Log */
    private $log;

    # Use matching based on https://gist.github.com/gruber/249502, but changed:
    # - to only look for http/https, otherwise here:http isn't caught
    # See also in Newsfeed.
    private $urlPattern = '#(?i)\b(((?:(?:http|https):(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))|(\.com\/))#m';

    # ...but this matches some bad character patterns.
    private $urlBad = [ '%', '{', ';', '#', ':' ];

    private $spamwords = NULL;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'chat_messages', 'chatmessage', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    private function getSpamWords() {
        if (!$this->spamwords) {
            $this->spamwords = $this->dbhr->preQuery("SELECT * FROM spam_keywords;");
        }
    }

    public function whitelistURLs($message) {
        if (preg_match_all($this->urlPattern, $message, $matches)) {
            $me = whoAmI($this->dbhr, $this->dbhm);
            $myid = $me ? $me->getId() : NULL;

            foreach ($matches as $val) {
                foreach ($val as $url) {
                    $bad = FALSE;
                    $url2 = str_replace('http:', '', $url);
                    $url2 = str_replace('https:', '', $url2);
                    foreach ($this->urlBad as $badone) {
                        if (strpos($url2, $badone) !== FALSE) {
                            $bad = TRUE;
                        }
                    }

                    #error_log("Whitelist $url bad $bad");
                    if (!$bad && strlen($url) > 0) {
                        $url = substr($url, strpos($url, '://') + 3);
                        $p = strpos($url, '/');
                        $domain = $p ? substr($url, 0, $p) : $url;
                        $this->dbhm->preExec("INSERT INTO spam_whitelist_links (userid, domain) VALUES (?, ?) ON DUPLICATE KEY UPDATE count = count + 1;", [
                            $myid,
                            $domain
                        ]);
                    }
                }
            }
        }
    }

    public function checkReview($message) {
        # Spammer trick is to encode the dot in URLs.
        $message = str_replace('&#12290;', '.', $message);

        $check = FALSE;

        if (stripos($message, '<script') !== FALSE) {
            # Looks dodgy.
            $check = TRUE;
        }

        # Check for URLs.
        if (preg_match_all($this->urlPattern, $message, $matches)) {
            # A link.  Some domains are ok - where they have been whitelisted several times (to reduce bad whitelists).
            $ourdomains = $this->dbhr->preQuery("SELECT domain FROM spam_whitelist_links WHERE count >= 3 AND LENGTH(domain) > 5 AND domain NOT LIKE '%linkedin%';");

            $valid = 0;
            $count = 0;
            $badurl = NULL;

            foreach ($matches as $val) {
                foreach ($val as $url) {
                    $bad = FALSE;
                    $url2 = str_replace('http:', '', $url);
                    $url2 = str_replace('https:', '', $url2);
                    foreach ($this->urlBad as $badone) {
                        if (strpos($url2, $badone) !== FALSE) {
                            $bad = TRUE;
                        }
                    }

                    if (!$bad && strlen($url) > 0) {
                        $url = substr($url, strpos($url, '://') + 3);
                        $count++;
                        $trusted = FALSE;

                        foreach ($ourdomains as $domain) {
                            if (stripos($url, $domain['domain']) === 0) {
                                # One of our domains.
                                $valid++;
                                $trusted = TRUE;
                            }
                        }

                        $badurl = $trusted ? $badurl : $url;
//                        if (!$trusted) {
//                            error_log("Bad url $url");
//                        }
                    }
                }
            }

            if ($valid < $count) {
                # At least one URL which we don't trust.
                $check = TRUE;
            }
        }

        # Check keywords
        $this->getSpamWords();
        foreach ($this->spamwords as $word) {
            if ($word['action'] == 'Review' &&
                preg_match('/\b' . preg_quote($word['word']) . '\b/', $message) &&
                (!$word['exclude'] || !preg_match('/' . $word['exclude'] . '/i', $message))) {
                #error_log("Spam keyword {$word['word']}");
                $check = TRUE;
            }
        }

        if (strpos($message, '$') !== FALSE || strpos($message, '£') !== FALSE) {
            $check = TRUE;
        }

        $s = new Spam($this->dbhr, $this->dbhm);

        if ($s->checkReferToSpammer($message)) {
            $check = TRUE;
        }

        return($check);
    }

    public function checkSpam($message) {
        $spam = FALSE;

        # Check keywords which are known as spam.
        $this->getSpamWords();
        foreach ($this->spamwords as $word) {
            if (strlen(trim($word['word'])) > 0) {
                $exp = '/\b' . preg_quote($word['word']) . '\b/';
                if ($word['action'] == 'Spam' &&
                    preg_match($exp, $message) &&
                    (!$word['exclude'] || !preg_match('/' . $word['exclude'] . '/i', $message))) {
                    $spam = TRUE;
                }
            }
        }

        # Check whether any URLs are in Spamhaus DBL black list.
        if (preg_match_all($this->urlPattern, $message, $matches)) {
            foreach ($matches as $val) {
                foreach ($val as $url) {
                    $bad = FALSE;
                    $url2 = str_replace('http:', '', $url);
                    $url2 = str_replace('https:', '', $url2);
                    foreach ($this->urlBad as $badone) {
                        if (strpos($url2, $badone) !== FALSE) {
                            $bad = TRUE;
                        }
                    }

                    if (!$bad && strlen($url) > 0) {
                        $url = substr($url, strpos($url, '://') + 3);
                        if (checkSpamhaus("http://$url")) {
                            $spam = TRUE;
                        }
                    }
                }
            }
        }

        return($spam);
    }

    public function create($chatid, $userid, $message, $type = ChatMessage::TYPE_DEFAULT, $refmsgid = NULL, $platform = TRUE, $spamscore = NULL, $reportreason = NULL, $refchatid = NULL, $imageid = NULL, $facebookid = NULL) {
        try {
            $review = 0;
            $spam = 0;
            $u = new User($this->dbhr, $this->dbhm, $userid);

            # Mods may need to refer to spam keywords in replies.
            if (!$u->isModerator()) {
                $review = $this->checkReview($message);
                $spam = $this->checkSpam($message) || $this->checkSpam($u->getName());

                # If we decided it was spam then it doesn't need reviewing.
                $review = $spam ? 0 : $review;
            }

            # Even if it's spam, we still create the message, so that if we later decide that it wasn't spam after all
            # it's still around to unblock.
            $rc = $this->dbhm->preExec("INSERT INTO chat_messages (chatid, userid, message, type, refmsgid, platform, reviewrequired, reviewrejected, spamscore, reportreason, refchatid, imageid, facebookid) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", [
                $chatid,
                $userid,
                $message,
                $type,
                $refmsgid,
                $platform,
                $review,
                $spam,
                $spamscore,
                $reportreason,
                $refchatid,
                $imageid,
                $facebookid
            ]);

            $id = $this->dbhm->lastInsertId();

            # We have ourselves seen this message.
            $this->dbhm->preExec("UPDATE chat_roster SET lastmsgseen = ? WHERE chatid = ? AND userid = ? AND (lastmsgseen IS NULL OR lastmsgseen < ?);",
                [
                    $id,
                    $chatid,
                    $userid,
                    $id
                ]);

            $r = new ChatRoom($this->dbhr, $this->dbhm, $chatid);
            $r->updateMessageCounts();
            $chattype = $r->getPrivate('chattype');

            if ($chattype == ChatRoom::TYPE_USER2USER || $chattype == ChatRoom::TYPE_USER2MOD) {
                # If anyone has closed this chat so that it no longer appears in their list, we want to open it again.
                #
                # This is rare, so rather than do an UPDATE which would always be a bit expensive even if we have
                # nothing to do, we do a SELECT to see if there are any.
                $closeds = $this->dbhr->preQuery("SELECT id FROM chat_roster WHERE chatid = ? AND status = ?;", [
                    $chatid,
                    ChatRoom::STATUS_CLOSED
                ], FALSE, FALSE);

                foreach ($closeds as $closed) {
                    $this->dbhm->preExec("UPDATE chat_roster SET status = ? WHERE id = ?;", [
                        ChatRoom::STATUS_OFFLINE,
                        $closed['id']
                    ]);
                }
            }

            if (!$spam && !$review) {
                $r->pokeMembers();
                $r->notifyMembers($u->getName(), $message, $userid);

                if ($r->getPrivate('synctofacebook') == ChatRoom::FACEBOOK_SYNC_REPLIED_ON_FACEBOOK) {
                    # We have had a reply from Facebook, which caused us to flag this conversation.
                    # This is now the first reply from the other user.  So we want to post a link on Facebook which
                    # will allow the user on there to read the message we've just created.  Set the state to
                    # make this happen in the background.
                    $r->setPrivate('synctofacebook', ChatRoom::FACEBOOK_SYNC_REPLIED_ON_PLATFORM);
                }
            }
        } catch (Exception $e) {
            error_log("Failed to create chat " . $e->getMessage() . " at " . $e->getFile() . " line " . $e->getLine());
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhm, $this->dbhm, $id, 'chat_messages', 'chatmessage', $this->publicatts);
            return($id);
        } else {
            return(NULL);
        }
    }

    public function getPublic() {
        $ret = $this->getAtts($this->publicatts);

        if (pres('refmsgid', $ret)) {
            # There is a message (in the sense of an item rather than a chat message) attached to this chat message.
            $m = new Message($this->dbhr, $this->dbhm , $ret['refmsgid']);
            $ret['refmsg'] = $m->getPublic(FALSE, FALSE);
            unset($ret['refmsgid']);
            unset($ret['refmsg']['textbody']);
            unset($ret['refmsg']['htmlbody']);
            unset($ret['refmsg']['message']);
        }

        if (pres('imageid', $ret)) {
            # There is an image attached
            $a = new Attachment($this->dbhr, $this->dbhm, $ret['imageid'], Attachment::TYPE_CHAT_MESSAGE);
            $ret['image'] = [
                'id' => $ret['imageid'],
                'path' => $a->getPath(FALSE),
                'paththumb' => $a->getPath(TRUE)
            ];
            unset($ret['imageid']);
        }

        if ($ret['type'] == ChatMessage::TYPE_ADDRESS) {
            $id = intval($ret['message']);
            $ret['message'] = NULL;
            $a = new Address($this->dbhr, $this->dbhm, $id);
            $ret['address'] = $a->getPublic();
        }

        # Strip any remaining quoted text in replies.
        # TODO But shouldn't this have happened?
        $ret['message'] = trim(preg_replace('/\|.*$/m', "", $ret['message']));
        $ret['message'] = trim(preg_replace('/\>.*$/m', "", $ret['message']));
        $ret['message'] = trim(preg_replace('/\#yiv.*$/m', "", $ret['message']));

        return($ret);
    }

    public function approve($id) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        # We can only approve if we can see this message for review.
        $sql = "SELECT chat_messages.* FROM chat_messages INNER JOIN chat_rooms ON reviewrequired = 1 AND chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_messages.userid = chat_rooms.user1 THEN chat_rooms.user2 ELSE chat_rooms.user1 END) AND memberships.groupid IN (SELECT groupid FROM memberships WHERE memberships.userid = ? AND memberships.role IN ('Owner', 'Moderator')) AND chat_messages.id = ?;";
        $msgs = $this->dbhr->preQuery($sql, [ $myid, $id ]);

        foreach ($msgs as $msg) {
            $this->dbhm->preExec("UPDATE chat_messages SET reviewrequired = 0, reviewedby = ? WHERE id = ?;", [
                $myid,
                $id
            ]);

            # Whitelist any URLs - they can't be indicative of spam.
            $this->whitelistURLs($msg['message']);

            # This is like a new message now, so alert them.
            $r = new ChatRoom($this->dbhr, $this->dbhm, $msg['chatid']);
            $r->updateMessageCounts();
            $u = User::get($this->dbhr, $this->dbhm, $msg['userid']);
            $r->pokeMembers();
            $r->notifyMembers($u->getName(), $msg['message'], $msg['userid']);
        }
    }

    public function reject($id) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        # We can only reject if we can see this message for review.
        $sql = "SELECT chat_messages.id, chat_messages.chatid FROM chat_messages INNER JOIN chat_rooms ON reviewrequired = 1 AND chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_messages.userid = chat_rooms.user1 THEN chat_rooms.user2 ELSE chat_rooms.user1 END) AND memberships.groupid IN (SELECT groupid FROM memberships WHERE memberships.userid = ? AND memberships.role IN ('Owner', 'Moderator')) AND chat_messages.id = ?;";
        $msgs = $this->dbhr->preQuery($sql, [ $myid, $id ]);

        foreach ($msgs as $msg) {
            $this->dbhm->preExec("UPDATE chat_messages SET reviewrequired = 0, reviewedby = ?, reviewrejected = 1 WHERE id = ?;", [
                $myid,
                $id
            ]);

            $r = new ChatRoom($this->dbhr, $this->dbhm, $msg['chatid']);
            $r->updateMessageCounts();
        }
    }

    public function getReviewCount(User $me) {
        # For chats, we should see the messages which require review, and where we are a mod on one of the groups
        # that the recipient of the message (i.e. the chat member who isn't the one who sent it) is on.
        #
        # For some of these groups we may be set not to show messages - so we need to honour that.
        $show = [];
        $dontshow = [];

        $groupids = $me->getModeratorships();
        foreach ($groupids as $groupid) {
            if ($me->activeModForGroup($groupid)) {
                $show[] = $groupid;
            } else {
                $dontshow[] = $groupid;
            }
        }

        $showq = implode(',', $show);
        $dontshowq = implode(',', $dontshow);

        # We want the messages for review for any group where we are a mod and the recipient of the chat message is
        # a member, where the group wants us to do this.  Put a backstop time on it to avoid getting too many or
        # an inefficient query.
        # TODO This uses INSTR to check a json-encoded field.  In MySQL 5.7 we can do better.
        $mysqltime = date ("Y-m-d", strtotime("Midnight 31 days ago"));
        $showcount = count($show) > 0 ? $this->dbhr->preQuery("SELECT COUNT(DISTINCT chat_messages.id) AS count FROM chat_messages INNER JOIN chat_rooms ON reviewrequired = 1 AND chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_messages.userid = chat_rooms.user1 THEN chat_rooms.user2 ELSE chat_rooms.user1 END) AND memberships.groupid IN ($showq) INNER JOIN groups ON memberships.groupid = groups.id AND ((groups.type = 'Freegle' AND groups.settings IS NULL) OR INSTR(groups.settings, '\"chatreview\":1') != 0) WHERE chat_messages.date > '$mysqltime';")[0]['count'] : 0;
        $dontshowcount = count($dontshow) > 0 ? $this->dbhr->preQuery("SELECT COUNT(DISTINCT chat_messages.id) AS count FROM chat_messages INNER JOIN chat_rooms ON reviewrequired = 1 AND chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_messages.userid = chat_rooms.user1 THEN chat_rooms.user2 ELSE chat_rooms.user1 END) AND memberships.groupid IN ($dontshowq) INNER JOIN groups ON memberships.groupid = groups.id AND ((groups.type = 'Freegle' AND groups.settings IS NULL) OR INSTR(groups.settings, '\"chatreview\":1') != 0) WHERE chat_messages.date > '$mysqltime';")[0]['count'] : 0;

        return([
            'showgroups' => $showq,
            'dontshowgroups' => $dontshowq,
            'chatreview' => $showcount,
            'chatreviewother' => $dontshowcount
        ]);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM chat_messages WHERE id = ?;", [$this->id]);
        return($rc);
    }
}