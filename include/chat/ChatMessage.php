<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/chat/ChatRoom.php');

class ChatMessage extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'chatid', 'userid', 'date', 'message', 'system', 'refmsgid', 'type', 'seenbyall', 'reviewrequired', 'reviewedby', 'reviewrejected', 'spamscore', 'reportreason', 'refchatid');
    var $settableatts = array('name');

    const TYPE_DEFAULT = 'Default';
    const TYPE_MODMAIL = 'ModMail';
    const TYPE_SYSTEM = 'System';
    const TYPE_INTERESTED = 'Interested';
    const TYPE_PROMISED = 'Promised';
    const TYPE_RENEGED = 'Reneged';
    const TYPE_REPORTEDUSER = 'ReportedUser';

    const ACTION_APPROVE = 'Approve';
    const ACTION_REJECT = 'Reject';

    /** @var  $log Log */
    private $log;

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

    public function checkReview($message) {
        $check = FALSE;

        if (stripos($message, '<script') !== FALSE) {
            # Looks dodgy.
            $check = TRUE;
        }

        # Check for URLs.  Use matching from https://gist.github.com/gruber/249502 .
        $ourdomains = explode(',', TRUSTED_LINKS);

        if (preg_match_all('#(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#m', $message, $matches)) {
            # A link.  Some domains are ok.
            $valid = 0;
            $count = 0;
            $badurl = NULL;

            foreach ($matches as $val) {
                foreach ($val as $url) {
                    if (strlen($url) > 0 && stripos($url, 'http') !== FALSE) {
                        #error_log("Check $url");
                        $url = substr($url, strpos($url, '://') + 3);
                        $count++;
                        $trusted = FALSE;

                        foreach ($ourdomains as $domain) {
                            #error_log("Check ours $domain vs $url pos " . stripos($url, $domain));
                            if (stripos($url, $domain) === 0) {
                                # One of our domains.
                                $valid++;
                                $trusted = TRUE;
                            }
                        }

                        $badurl = $trusted ? $badurl : $url;
                    }
                }
            }

            if ($valid < $count) {
                # At least one URL which we don't trust.
                error_log("...$badurl not trusted");
                $check = TRUE;
            }
        }

        return($check);
    }

    public function create($chatid, $userid, $message, $type = ChatMessage::TYPE_DEFAULT, $refmsgid = NULL, $platform = TRUE, $spamscore = NULL, $reportreason = NULL, $refchatid = NULL) {
        try {
            $review = $this->checkReview($message);
            $rc = $this->dbhm->preExec("INSERT INTO chat_messages (chatid, userid, message, type, refmsgid, platform, reviewrequired, spamscore, reportreason, refchatid) VALUES (?,?,?,?,?,?,?,?,?,?)", [
                $chatid,
                $userid,
                $message,
                $type,
                $refmsgid,
                $platform,
                $review,
                $spamscore,
                $reportreason,
                $refchatid
            ]);

            $id = $this->dbhm->lastInsertId();

            # We have ourselves seen this message.
            $this->dbhm->preExec("UPDATE chat_roster SET lastmsgseen = ? WHERE chatid = ? AND userid = ?;",
                [
                    $id,
                    $chatid,
                    $userid
                ]);

            $r = new ChatRoom($this->dbhr, $this->dbhm, $chatid);
            $r->pokeMembers();
        } catch (Exception $e) {
            error_log("Failed to create chat " . $e->getMessage());
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'chat_messages', 'chatmessage', $this->publicatts);
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

        return($ret);
    }

    public function approve($id) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        # We can only approve if we can see this message for review.
        $sql = "SELECT chat_messages.id, chat_messages.chatid FROM chat_messages INNER JOIN chat_rooms ON reviewrequired = 1 AND chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_messages.userid = chat_rooms.user1 THEN chat_rooms.user2 ELSE chat_rooms.user1 END) AND memberships.groupid IN (SELECT groupid FROM memberships WHERE memberships.userid = ? AND memberships.role IN ('Owner', 'Moderator')) AND chat_messages.id = ?;";
        $msgs = $this->dbhr->preQuery($sql, [ $myid, $id ]);

        foreach ($msgs as $msg) {
            $this->dbhm->preExec("UPDATE chat_messages SET reviewrequired = 0, reviewedby = ? WHERE id = ?;", [
                $myid,
                $id
            ]);

            # This is like a new message now, so alert them.
            $r = new ChatRoom($this->dbhr, $this->dbhm, $msg['chatid']);
            $r->pokeMembers();
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
        }
    }

    public function getReviewCount(User $me) {
        # For chats, we should see the messages which require review, and where we are a mod on one of the groups
        # that the recipient of the message (i.e. the chat member who isn't the one who sent it) is on.
        #
        # For some of these groups we may be set not to show messages - so we need to honour that.
        $show = [0];
        $dontshow = [0];

        $groupids = $me->getModeratorships();
        foreach ($groupids as $groupid) {
            $mysettings = $me->getGroupSettings($groupid);
            $showmessages = !array_key_exists('showmessages', $mysettings) || $mysettings['showmessages'];

            if ($showmessages) {
                $show[] = $groupid;
            } else {
                $dontshow[] = $groupid;
            }
        }

        $showq = implode(',', $show);
        $dontshowq = implode(',', $dontshow);

        $showcount = $this->dbhr->preQuery("SELECT COUNT(DISTINCT chat_messages.id) AS count FROM chat_messages INNER JOIN chat_rooms ON reviewrequired = 1 AND chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_messages.userid = chat_rooms.user1 THEN chat_rooms.user2 ELSE chat_rooms.user1 END) AND memberships.groupid IN ($showq);")[0]['count'];
        $dontshowcount = $this->dbhr->preQuery("SELECT COUNT(DISTINCT chat_messages.id) AS count FROM chat_messages INNER JOIN chat_rooms ON reviewrequired = 1 AND chat_rooms.id = chat_messages.chatid INNER JOIN memberships ON memberships.userid = (CASE WHEN chat_messages.userid = chat_rooms.user1 THEN chat_rooms.user2 ELSE chat_rooms.user1 END) AND memberships.groupid IN ($dontshowq);")[0]['count'];

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