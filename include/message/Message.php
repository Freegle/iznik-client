<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/misc/plugin.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/user/Search.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/include/misc/Search.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');
require_once(IZNIK_BASE . '/mailtemplates/autorepost.php');
require_once(IZNIK_BASE . '/mailtemplates/chaseup.php');

use GeoIp2\Database\Reader;
use Oefenweb\DamerauLevenshtein\DamerauLevenshtein;

class Message
{
    const TYPE_OFFER = 'Offer';
    const TYPE_TAKEN = 'Taken';
    const TYPE_WANTED = 'Wanted';
    const TYPE_RECEIVED = 'Received';
    const TYPE_ADMIN = 'Admin';
    const TYPE_OTHER = 'Other';

    const OUTCOME_TAKEN = 'Taken';
    const OUTCOME_RECEIVED = 'Received';
    const OUTCOME_WITHDRAWN = 'Withdrawn';

    // Bounce checks.
    private $bounce_subjects = [
        "Mail delivery failed",
        "Delivery Status Notification",
        "Undelivered Mail Returned to Sender",
        "Local delivery error",
        "Returned mail",
        "delivery failure",
        "Delivery has failed",
        "Please redirect your e-mail",
        "Email delivery failure",
        "Undeliverable",
        "Auto-response",
        "Inactive account",
        "Change of email",
        "Unable to process your message",
        "Has decided to leave the company",
        "No longer a valid",
        "does not exist",
        "new email address",
        "Malformed recipient",
        "spamarrest.com",
        "(Automatic Response)",
        "Automatic reply",
        "email address closure",
        "invalid address",
        "User unknown",
        'Retiring this e-mail address',
        "Could not send message",
        "Unknown user"
    ];
    
    private $bounce_bodies = [
        "I'm afraid I wasn't able to deliver your message to the following addresses.",
        "Delivery to the following recipients failed.",
        "was not delivered to",
        "550 No such user",
        "update your records",
        "has now left",
        "please note his new address",
        "Sorry, we were unable to deliver your message",
        "this email address is no longer in use"
    ];
    
    // Autoreply checks.
    private $autoreply_subjects = [
        "Auto Response",
        "Autoresponder",
        "If your enquiry is urgent",
        "Thankyou for your enquiry",
        "Thanks for your email",
        "Thanks for contacting",
        "Thank you for your enquiry",
        "Many thanks for your",
        "Automatic reply",
        "Mail Receipt",
        "Automated reply",
        "Auto-Reply",
        "Out of Office",
        "annual leave",
        "on holiday",
        "vacation reply"
    ];

    private $autoreply_bodies = [
        "I aim to respond within",
        "reply as soon as possible",
        'with clients right now',
        "Automated response",
        "Please note his new address",
        "THIS IS AN AUTO-RESPONSE MESSAGE",
        "out of the office",
        "Thank you so much for your email enquiry",
        "I am away",
        "I am currently away",
        "Thanks for your email enquiry",
        "don't check this very often"
    ];
    
    static public function checkType($type) {
        switch($type) {
            case Message::TYPE_OFFER:
            case Message::TYPE_TAKEN:
            case Message::TYPE_WANTED:
            case Message::TYPE_RECEIVED:
            case Message::TYPE_ADMIN:
            case Message::TYPE_OTHER:
                $ret = $type;
                break;
            default:
                $ret = NULL;
        }
        
        return($ret);
    }
    
    static public function checkTypes($types) {
        $ret = NULL;

        if ($types) {
            $ret = [];

            foreach ($types as $type) {
                $thistype = Message::checkType($type);

                if ($thistype) {
                    $ret[] = "'$thistype'";
                }
            }
        }

        return($ret);
    }

    /**
     * @return null
     */
    public function getGroupid()
    {
        return $this->groupid;
    }

    /**
     * @return mixed
     */
    public function getYahooapprove()
    {
        return $this->yahooapprove;
    }

    public function setYahooPendingId($groupid, $id) {
        # Don't set for deleted messages, otherwise there's a timing window where we can end up with a deleted
        # message with an id that blocks inserts of subequent messages.
        $sql = "UPDATE messages_groups SET yahoopendingid = ? WHERE msgid = {$this->id} AND groupid = ? AND deleted = 0;";
        $rc = $this->dbhm->preExec($sql, [ $id, $groupid ]);

        if ($rc) {
            $this->yahoopendingid = $id;
        }
    }

    public function setYahooApprovedId($groupid, $id) {
        # Don't set for deleted messages, otherwise there's a timing window where we can end up with a deleted
        # message with an id that blocks inserts of subequent messages.
        $sql = "UPDATE messages_groups SET yahooapprovedid = ? WHERE msgid = {$this->id} AND groupid = ? AND deleted = 0;";
        $rc = $this->dbhm->preExec($sql, [ $id, $groupid ]);

        if ($rc) {
            $this->yahooapprovedid = $id;
        }
    }

    public function setPrivate($att, $val, $always = FALSE) {
        if ($this->$att != $val || $always) {
            $rc = $this->dbhm->preExec("UPDATE messages SET $att = ? WHERE id = {$this->id};", [$val]);
            if ($rc) {
                $this->$att = $val;
            }
        }
    }

    public function getPrivate($att) {
        return($this->$att);
    }

    public function setFOP($fop) {
        $this->dbhm->preExec("INSERT INTO messages_deadlines (msgid, fop) VALUES (?,?) ON DUPLICATE KEY UPDATE fop = ?;", [
            $this->id,
            $fop ? 1 : 0,
            $fop ? 1 : 0
        ]);
    }
    
    public function edit($subject, $textbody, $htmlbody) {
        if ($htmlbody && !$textbody) {
            # In the interests of accessibility, let's create a text version of the HTML
            $html = new \Html2Text\Html2Text($htmlbody);
            $textbody = $html->getText();
        }

        $me = whoAmI($this->dbhr, $this->dbhm);
        $text = ($subject ? "New subject $subject " : '');
        $text .= "Text body changed to len " . strlen($textbody);
        $text .= " HTML body changed to len " . strlen($htmlbody);

        # Make sure we have a text value, otherwise we might return a missing body.
        $textbody = strlen($textbody) == 0 ? ' ' : $textbody;

        $this->log->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => Log::SUBTYPE_EDIT,
            'msgid' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => $text
        ]);

        if ($subject) {
            # If the subject has been edited, then that edit is more important than any suggestion we might have
            # come up with.
            $this->setPrivate('subject', $subject);
            $this->setPrivate('suggestedsubject', $subject);
        }

        if ($textbody) {
            $this->setPrivate('textbody', $textbody);
        }

        if ($htmlbody) {
            $this->setPrivate('htmlbody', $htmlbody);
        }

        $sql = "UPDATE messages SET editedby = ?, editedat = NOW() WHERE id = ?;";
        $this->dbhm->preExec($sql, [
            $me ? $me->getId() : NULL,
            $this->id
        ]);

        # If we edit a message and then approve it by email, Yahoo breaks the message.  So prevent that happening by
        # removing the email approval info.
        $sql = "UPDATE messages_groups SET yahooapprove = NULL, yahooreject = NULL WHERE msgid = ?;";
        $this->dbhm->preExec($sql, [
            $this->id
        ]);
    }

    /**
     * @return mixed
     */
    public function getYahooreject()
    {
        return $this->yahooreject;
    }

    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;

    private $id, $source, $sourceheader, $message, $textbody, $htmlbody, $subject, $suggestedsubject, $fromname, $fromaddr,
        $replyto, $envelopefrom, $envelopeto, $messageid, $tnpostid, $fromip, $date,
        $fromhost, $type, $attachments, $yahoopendingid, $yahooapprovedid, $yahooreject, $yahooapprove, $attach_dir, $attach_files,
        $parser, $arrival, $spamreason, $spamtype, $fromuser, $fromcountry, $deleted, $heldby, $lat = NULL, $lng = NULL, $locationid = NULL,
        $s, $editedby, $editedat, $modmail, $senttoyahoo, $FOP;

    /**
     * @return mixed
     */
    public function getModmail()
    {
        return $this->modmail;
    }

    /**
     * @return mixed
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    # The groupid is only used for parsing and saving incoming messages; after that a message can be on multiple
    # groups as is handled via the messages_groups table.
    private $groupid = NULL;

    private $inlineimgs = [];

    /**
     * @return mixed
     */
    public function getSpamreason()
    {
        return $this->spamreason;
    }

    # Each message has some public attributes, which are visible to API users.
    #
    # Which attributes can be seen depends on the currently logged in user's role on the group.
    #
    # Other attributes are only visible within the server code.
    public $nonMemberAtts = [
        'id', 'subject', 'suggestedsubject', 'type', 'arrival', 'date', 'deleted', 'heldby', 'textbody', 'htmlbody', 'senttoyahoo', 'FOP', 'fromaddr'
    ];

    public $memberAtts = [
        'fromname', 'fromuser', 'modmail'
    ];

    public $moderatorAtts = [
        'source', 'sourceheader', 'envelopefrom', 'envelopeto', 'messageid', 'tnpostid',
        'fromip', 'fromcountry', 'message', 'spamreason', 'spamtype', 'replyto', 'editedby', 'editedat', 'locationid'
    ];

    public $ownerAtts = [
        # Add in a dup for UT coverage of loop below.
        'source'
    ];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->log = new Log($this->dbhr, $this->dbhm);
        $this->notif = new Notifications($this->dbhr, $this->dbhm);

        if ($id) {
            $msgs = $dbhr->preQuery("SELECT messages.*, messages_deadlines.FOP FROM messages LEFT JOIN messages_deadlines ON messages_deadlines.msgid = messages.id WHERE id = ?;", [$id]);
            foreach ($msgs as $msg) {
                $this->id = $id;

                # FOP defaults on for our messages.
                if ($msg['source'] == Message::PLATFORM && $msg['type'] == Message::TYPE_OFFER && $msg['FOP'] === NULL) {
                    $msg['FOP'] = 1;
                }

                foreach (array_merge($this->nonMemberAtts, $this->memberAtts, $this->moderatorAtts, $this->ownerAtts) as $attr) {
                    if (pres($attr, $msg)) {
                        $this->$attr = $msg[$attr];
                    }
                }
            }

            # We parse each time because sometimes we will ask for headers.  Note that if we're not in the initial parse/save of
            # the message we might be parsing from a modified version of the source.
            $this->parser = new PhpMimeMailParser\Parser();
            $this->parser->setText($this->message);
        }

        $start = strtotime("30 days ago");
        $this->s = new Search($dbhr, $dbhm, 'messages_index', 'msgid', 'arrival', 'words', 'groupid', $start);
    }

    /**
     * @param Search $search
     */
    public function setSearch($search)
    {
        $this->s = $search;
    }

    public function mailer($user, $modmail, $toname, $to, $bcc, $fromname, $from, $subject, $text) {
        try {
            #error_log(session_id() . " mail " . microtime(true));

            list ($transport, $mailer) = getMailer();
            
            $message = Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom([$from => $fromname])
                ->setTo([$to => $toname])
                ->setBody($text);

            # We add some headers so that if we receive this back, we can identify it as a mod mail.
            $headers = $message->getHeaders();

            if ($user) {
                $headers->addTextHeader('X-Iznik-From-User', $user->getId());
            }

            $headers->addTextHeader('X-Iznik-ModMail', $modmail);

            if ($bcc) {
                $message->setBcc(explode(',', $bcc));
            }

            $mailer->send($message);

            # Stop the transport, otherwise the message doesn't get sent until the UT script finishes.
            $transport->stop();

            #error_log(session_id() . " mailed " . microtime(true));
        } catch (Exception $e) {
            # Not much we can do - shouldn't really happen given the failover transport.
            // @codeCoverageIgnoreStart
            error_log("Send failed with " . $e->getMessage());
            // @codeCoverageIgnoreEnd
        }
    }

    public function getRoleForMessage($overrides = TRUE) {
        # Our role for a message is the highest role we have on any group that this message is on.  That means that
        # we have limited access to information on other groups of which we are not a moderator, but that is legitimate
        # if the message is on our group.
        #
        # We might also be a partner.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $role = pres('partner', $_SESSION) ? User::ROLE_MEMBER : User::ROLE_NONMEMBER;

        if ($me) {
            $sql = "SELECT role, messages_groups.collection FROM memberships
              INNER JOIN messages_groups ON messages_groups.msgid = ?
                  AND messages_groups.groupid = memberships.groupid
                  AND userid = ?;";
            $groups = $this->dbhr->preQuery($sql, [
                $this->id,
                $me->getId()
            ]);

            #error_log("$sql {$this->id}, " . $me->getId() . " " . var_export($groups, TRUE));

            foreach ($groups as $group) {
                switch ($group['role']) {
                    case User::ROLE_OWNER:
                        # Owner is highest.
                        $role = $group['role'];
                        break;
                    case User::ROLE_MODERATOR:
                        # Upgrade from member or non-member to mod.
                        $role = ($role == User::ROLE_MEMBER || $role == User::ROLE_NONMEMBER) ? User::ROLE_MODERATOR : $role;
                        break;
                    case User::ROLE_MEMBER:
                        # Just a member
                        $role = User::ROLE_MEMBER;
                        break;
                }

                if ($me->getId() == $this->fromuser) {
                    # It's our message.  We have full rights.
                    $role = User::ROLE_MODERATOR;
                }
            }

            if ($overrides) {
                switch ($me->getPrivate('systemrole')) {
                    case User::SYSTEMROLE_SUPPORT:
                        $role = User::ROLE_MODERATOR;
                        break;
                    case User::SYSTEMROLE_ADMIN:
                        $role = User::ROLE_OWNER;
                        break;
                }
            }
        }

        if ($role == User::ROLE_NONMEMBER) {
            # We can potentially upgrade our role if this is one of our drafts.
            $drafts = $this->dbhr->preQuery("SELECT * FROM messages_drafts WHERE msgid = ? AND session = ? OR (userid = ? AND userid IS NOT NULL);", [
                $this->id,
                session_id(),
                $me ? $me->getId() : NULL
            ]);

            foreach ($drafts as $draft) {
                $role = User::ROLE_MODERATOR;
            }
        }

        return($role);
    }
    
    public function stripGumf() {
        # We have the same function in views/user/message.js; keep thenm in sync.
        $text = $this->getTextbody();

        if ($text) {
            // console.log("Strip photo", text);
            // Strip photo links - we should have those as attachments.
            $text = preg_replace('/You can see a photo[\s\S]*?jpg/', '', $text);
            $text = preg_replace('/Check out the pictures[\s\S]*?https:\/\/trashnothing[\s\S]*?pics\/\d*/', '', $text);
            $text = preg_replace('/You can see photos here[\s\S]*jpg/m', '', $text);
            $text = preg_replace('/https:\/\/direct.*jpg/m', '', $text);
            $text = preg_replace('/Photos\:[\s\S]*?jpg/', '', $text);

            // FOPs
            $text = preg_replace('/Fair Offer Policy applies \(see https:\/\/[\s\S]*\)/', '', $text);
            $text = preg_replace('/Fair Offer Policy:[\s\S]*?reply./', '', $text);

            // App footer
            $text = preg_replace('/Freegle app.*[0-9]$/m', '', $text);

            // Footers
            $text = preg_replace('/--[\s\S]*Get Freegling[\s\S]*book/m', '', $text);
            $text = preg_replace('/--[\s\S]*Get Freegling[\s\S]*org[\s\S]*?<\/a>/m', '', $text);
            $text = preg_replace('/This message was sent via Freegle Direct[\s\S]*/m', '', $text);
            $text = preg_replace('/\[Non-text portions of this message have been removed\]/m', '', $text);
            $text = preg_replace('/^--$[\s\S]*/m', '', $text);

            // Redundant line breaks.
            $text = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $text);

            // Duff text added by Yahoo Mail app.
            $text = str_replace('blockquote, div.yahoo_quoted { margin-left: 0 !important; border-left:1px #715FFA solid !important; padding-left:1ex !important; background-color:white !important; }', '', $text);

            $text = trim($text);
        }
        
        return($text ? $text : '');
    }

    public function getPublic($messagehistory = TRUE, $related = TRUE, $seeall = FALSE) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;
        $ret = [];
        $role = $this->getRoleForMessage();
        $ret['myrole'] = $role;

        foreach ($this->nonMemberAtts as $att) {
            $ret[$att] = $this->$att;
        }

        if ($role == User::ROLE_MEMBER || $role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER || $seeall) {
            foreach ($this->memberAtts as $att) {
                $ret[$att] = $this->$att;
            }
        }

        if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER || $seeall) {
            foreach ($this->moderatorAtts as $att) {
                $ret[$att] = $this->$att;
            }
        }

        if ($role == User::ROLE_OWNER || $seeall) {
            foreach ($this->ownerAtts as $att) {
                $ret[$att] = $this->$att;
            }
        }

        # URL people can follow to get to the message on our site.
        $ret['url'] = 'https://' . USER_SITE . '/message/' . $this->id;

        # Location. We can always see any area and top-level postcode.  If we're a mod or this is our message
        # we can see the precise location.
        if ($this->locationid) {
            $l = new Location($this->dbhr, $this->dbhm, $this->locationid);
            $areaid = $l->getPrivate('areaid');
            if ($areaid) {
                # This location is quite specific.  Return the area it's in.
                $a = new Location($this->dbhr, $this->dbhm, $areaid);
                $ret['area'] = $a->getPublic();
            } else {
                # This location isn't in an area; it is one.  Return i.
                $ret['area'] = $l->getPublic();
            }

            $pcid = $l->getPrivate('postcodeid');
            if ($pcid) {
                $p = new Location($this->dbhr, $this->dbhm, $pcid);
                $ret['postcode'] = $p->getPublic();
            }

            if ($seeall || $role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER || ($myid && $this->fromuser == $myid)) {
                $ret['location'] = $l->getPublic();
            }
        }

        $ret['mine'] = $myid && $this->fromuser == $myid;

        # Remove any group subject tag.
        $ret['subject'] = preg_replace('/^\[.*?\]\s*/', '', $ret['subject']);
        $ret['subject'] = preg_replace('/\[.*Attachment.*\]\s*/', '', $ret['subject']);

        # Get the item.  Although it's an extra DB call, we use this in creating structured data for SEO.
        $items = $this->dbhr->preQuery("SELECT items.id, items.name FROM messages_items INNER JOIN items ON messages_items.itemid = items.id WHERE msgid = ?;", [ $this->id ]);
        if (count($items) > 0) {
            $ret['item'] = $items[0];
        } else if (preg_match("/(.+)\:(.+)\((.+)\)/", $ret['subject'], $matches)) {
            # See if we can find it.
            $item = trim($matches[2]);
            $itemid = NULL;
            $items = $this->dbhr->preQuery("SELECT items.id FROM items WHERE name LIKE ?;", [ $item ]);
            $itemid = count($items) == 0 ? NULL : $items[0]['id'];
            $ret['item'] = [
                'id' => $itemid,
                'name' => $item
            ];
        }

        # Add any groups that this message is on.
        $ret['groups'] = [];
        $sql = "SELECT *, TIMESTAMPDIFF(HOUR, arrival, NOW()) AS hoursago FROM messages_groups WHERE msgid = ? AND deleted = 0;";
        $ret['groups'] = $this->dbhr->preQuery($sql, [ $this->id ] );

        foreach ($ret['groups'] as &$group) {
            $group['arrival'] = ISODate($group['arrival']);
            #error_log("{$this->id} approved by {$group['approvedby']}");

            if (pres('approvedby', $group)) {
                $u = User::get($this->dbhr, $this->dbhm, $group['approvedby']);
                $ctx = NULL;
                $group['approvedby'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE);
            }

            $g = Group::get($this->dbhr, $this->dbhm, $group['groupid']);
            $keywords = $g->getSetting('keywords', $g->defaultSettings['keywords']);
            $ret['keyword'] = presdef(strtolower($this->type), $keywords, $this->type);

            if ($ret['mine']) {
                # Can we repost?
                $ret['canrepost'] = FALSE;

                $reposts = $g->getSetting('reposts', ['offer' => 2, 'wanted' => 14, 'max' => 10, 'chaseups' => 2]);
                $interval = $this->type == Message::TYPE_OFFER ? $reposts['offer'] : $reposts['wanted'];
                $arrival = strtotime($group['arrival']);
                $ret['canrepostat'] = ISODate('@' . ($arrival + $interval * 3600 * 24));

                if ($group['hoursago'] > $interval * 24) {
                    $ret['canrepost'] = TRUE;
                }
            }
        }

        # Can see replies if:
        # - we want everything
        # - we're on ModTools and we're a mod for this message
        # - it's our message
        if ($seeall || (MODTOOLS && ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER)) || ($myid && $this->fromuser == $myid)) {
            # Add replies, as long as they're not awaiting review or rejected.
            $sql = "SELECT DISTINCT t.* FROM (SELECT id, userid, chatid, MAX(date) AS lastdate FROM chat_messages WHERE refmsgid = ? AND reviewrejected = 0 AND reviewrequired = 0 AND userid != ? GROUP BY userid, chatid) t ORDER BY lastdate DESC;";
            $replies = $this->dbhr->preQuery($sql, [$this->id, $this->fromuser]);
            $ret['replies'] = [];
            foreach ($replies as $reply) {
                $ctx = NULL;
                if ($reply['userid']) {
                    $u = User::get($this->dbhr, $this->dbhm, $reply['userid']);
                    $thisone = [
                        'id' => $reply['id'],
                        'user' => $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE),
                        'chatid' => $reply['chatid']
                    ];

                    # Add the last reply date and a snippet.
                    $lastreplies = $this->dbhr->preQuery("SELECT * FROM chat_messages WHERE userid = ? AND chatid = ? ORDER BY id DESC LIMIT 1;", [
                        $reply['userid'],
                        $reply['chatid']
                    ]);

                    foreach ($lastreplies as $lastreply) {
                        $thisone['lastdate'] = ISODate($lastreply['date']);
                        $thisone['snippet'] = substr($lastreply['message'], 0, 30);
                    }

                    $ret['replies'][] = $thisone;;
                }
            }

            # Whether or not we will auto-repost depends on whether there are replies.
            $ret['willautorepost'] = count($ret['replies']) == 0;

            $ret['promisecount'] = 0;

            if ($this->type == Message::TYPE_OFFER) {
                # Add any promises, i.e. one or more people we've said can have this.
                $sql = "SELECT * FROM messages_promises WHERE msgid = ? ORDER BY id DESC;";
                $ret['promises'] = $this->dbhr->preQuery($sql, [$this->id]);
                $ret['promisecount'] = count($ret['promises']);

                foreach ($ret['replies'] as &$reply) {
                    foreach ($ret['promises'] as $promise) {
                        $reply['promised'] = presdef('promised', $reply, FALSE) || ($promise['userid'] == $reply['user']['id']);
                    }
                }
            }
        }

        # Add any outcomes.  No need to expand the user as any user in an outcome should also be in a reply.
        $sql = "SELECT * FROM messages_outcomes WHERE msgid = ? ORDER BY id DESC;";
        $ret['outcomes'] = $this->dbhr->preQuery($sql, [ $this->id ]);

        # We can only see the details of the outcome if we have access.
        foreach ($ret['outcomes'] as &$outcome) {
            if (!($seeall || ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) || ($myid && $this->fromuser == $myid))) {
                $outcome['userid'] = NULL;
                $outcome['happiness'] = NULL;
                $outcome['comments'] = NULL;
            }

            $outcome['timestamp'] = ISODate($outcome['timestamp']);
        }

        if ($role == User::ROLE_NONMEMBER) {
            # For non-members we want to strip out any potential phone numbers or email addresses.
            $ret['textbody'] = preg_replace('/[0-9]{4,}/', 'xxx', $ret['textbody']);
            $ret['textbody'] = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', 'xxx@xxx.com', $ret['textbody']);

            # We can't do this in HTML, so just zap it.
            $ret['htmlbody'] = NULL;
        }

        # Add derived attributes.
        $ret['arrival'] = ISODate($ret['arrival']);
        $ret['date'] = ISODate($ret['date']);
        $ret['daysago'] = floor((time() - strtotime($ret['date'])) / 86400);
        $ret['snippet'] = pres('textbody', $ret) ? substr($ret['textbody'], 0, 60) : null;

        # We have a flag for FOP - but legacy posting methods might put it in the body.
        $ret['FOP'] = (pres('textbody', $ret) && (strpos($ret['textbody'], 'Fair Offer Policy') !== FALSE) || $ret['FOP']) ? 1 : 0;

        if (pres('fromcountry', $ret)) {
            $ret['fromcountry'] = code_to_country($ret['fromcountry']);
        }

        $ret['publishconsent'] = FALSE;

        if ($this->fromuser) {
            # We know who sent this.  We may be able to return this (depending on the role we have for the message
            # and hence the attributes we have already filled in).  We also want to know if we have consent
            # to republish it.
            $u = User::get($this->dbhr, $this->dbhm, $this->fromuser);

            if (pres('fromuser', $ret)) {
                # Get the user details, relative to the groups this message appears on.
                $ret['fromuser'] = $u->getPublic($this->getGroups(), $messagehistory, FALSE);

                if ($role == User::ROLE_OWNER || $role == User::ROLE_MODERATOR) {
                    # We can see their emails.
                    $ret['fromuser']['emails'] = $u->getEmails();
                }

                filterResult($ret['fromuser']);
            }

            $ret['publishconsent'] = $u->getPrivate('publishconsent');
        }

        if ($related) {
            # Add any related messages
            $ret['related'] = [];
            $sql = "SELECT * FROM messages_related WHERE id1 = ? OR id2 = ?;";
            $rels = $this->dbhr->preQuery($sql, [ $this->id, $this->id ]);
            $relids = [];
            foreach ($rels as $rel) {
                $id = $rel['id1'] == $this->id ? $rel['id2'] : $rel['id1'];

                if (!array_key_exists($id, $relids)) {
                    $m = new Message($this->dbhr, $this->dbhm, $id);
                    $ret['related'][] = $m->getPublic(FALSE, FALSE);
                    $relids[$id] = TRUE;
                }
            }
        }

        if (pres('heldby', $ret)) {
            $u = User::get($this->dbhr, $this->dbhm, $ret['heldby']);
            $ret['heldby'] = $u->getPublic();
            filterResult($ret['heldby']);
        }

        # Add any attachments - visible to non-members.
        $ret['attachments'] = [];
        $atts = $this->getAttachments();
        $atthash = [];

        foreach ($atts as $att) {
            # We suppress return of duplicate attachments by using the image hash.  This helps in the case where
            # the same photo is (for example) included in the mail both as an inline attachment and as a link
            # in the text.
            $hash = $att->getHash();
            if (!$hash || !pres($hash, $atthash)) {
                /** @var $att Attachment */
                $ret['attachments'][] = $att->getPublic();
                $atthash[$hash] = TRUE;
            }
        }

        return($ret);
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getFromIP()
    {
        return $this->fromip;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return mixed
     */
    public function getSourceheader()
    {
        return $this->sourceheader;
    }

    /**
     * @param mixed $fromip
     */
    public function setFromIP($fromip)
    {
        $name = NULL;

        if ($fromip) {
            # If the call returns a hostname which is the same as the IP, then it's
            # not resolvable.
            $name = gethostbyaddr($fromip);
            $name = ($name == $fromip) ? NULL : $name;

            if ($name) {
                $this->fromhost = $name;
            }
        }

        if ($fromip) {
            $this->fromip = $fromip;
            $this->dbhm->preExec("UPDATE messages SET fromip = ? WHERE id = ? AND fromip IS NULL;",
                [$fromip, $this->id]);
            $this->dbhm->preExec("UPDATE messages_history SET fromip = ?, fromhost = ? WHERE msgid = ? AND fromip IS NULL;",
                [$fromip, $name, $this->id]);
        }
    }

    /**
     * @return mixed
     */
    public function getFromhost()
    {
        if (!$this->fromhost && $this->fromip) {
            # If the call returns a hostname which is the same as the IP, then it's
            # not resolvable.
            $name = gethostbyaddr($this->fromip);
            $name = ($name == $this->fromip) ? NULL : $name;
            $this->fromhost = $name;
        }

        return $this->fromhost;
    }

    const EMAIL = 'Email';
    const YAHOO_APPROVED = 'Yahoo Approved';
    const YAHOO_PENDING = 'Yahoo Pending';
    const YAHOO_SYSTEM = 'Yahoo System';
    const PLATFORM = 'Platform'; // Us

    /**
     * @return null
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getMessageID()
    {
        return $this->messageid;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getTnpostid()
    {
        return $this->tnpostid;
    }

    /**
     * @return mixed
     */
    public function getEnvelopefrom()
    {
        return $this->envelopefrom;
    }

    /**
     * @return mixed
     */
    public function getEnvelopeto()
    {
        return $this->envelopeto;
    }

    /**
     * @return mixed
     */
    public function getFromname()
    {
        return $this->fromname;
    }

    /**
     * @return mixed
     */
    public function getFromaddr()
    {
        return $this->fromaddr;
    }

    /**
     * @return mixed
     */
    public function getTextbody()
    {
        return $this->textbody;
    }

    /**
     * @return mixed
     */
    public function getHtmlbody()
    {
        return $this->htmlbody;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return array
     */
    public function getInlineimgs()
    {
        return $this->inlineimgs;
    }

    /**
     * @return PhpMimeMailParser\Attachment[]
     */
    public function getParsedAttachments()
    {
        return $this->attachments;
    }

    # Get attachments which have been saved
    public function getAttachments() {
        $a = new Attachment($this->dbhr, $this->dbhm);
        $atts = $a->getById($this->getID());
        return($atts);
    }

    private static function keywords() {
        # We try various mis-spellings, and Welsh.  This is not to suggest that Welsh is a spelling error.
        return([
            Message::TYPE_OFFER => [
                'ofer', 'offr', 'offrer', 'ffered', 'offfered', 'offrered', 'offered', 'offeer', 'cynnig', 'offred',
                'offer', 'offering', 'reoffer', 're offer', 're-offer', 'reoffered', 're offered', 're-offered',
                'offfer', 'offeed', 'available'],
            Message::TYPE_TAKEN => ['collected', 'take', 'stc', 'gone', 'withdrawn', 'ta ke n', 'promised',
                'cymeryd', 'cymerwyd', 'takln', 'taken', 'cymryd'],
            Message::TYPE_WANTED => ['wnted', 'requested', 'rquested', 'request', 'would like', 'want',
                'anted', 'wated', 'need', 'needed', 'wamted', 'require', 'required', 'watnted', 'wented',
                'sought', 'seeking', 'eisiau', 'wedi eisiau', 'eisiau', 'wnated', 'wanted', 'looking', 'waned'],
            Message::TYPE_RECEIVED => ['recieved', 'reiceved', 'receved', 'rcd', 'rec\'d', 'recevied',
                'receive', 'derbynewid', 'derbyniwyd', 'received', 'recivered'],
            Message::TYPE_ADMIN => ['admin', 'sn']
        ]);
    }

    public static function determineType($subj) {
        $type = Message::TYPE_OTHER;
        $pos = PHP_INT_MAX;

        foreach (Message::keywords() as $keyword => $vals) {
            foreach ($vals as $val) {
                if (preg_match('/\b(' . preg_quote($val) . ')\b/i', $subj, $matches, PREG_OFFSET_CAPTURE)) {
                    if ($matches[1][1] < $pos) {
                        # We want the match earliest in the string - Offerton etc.
                        $type = $keyword;
                        $pos = $matches[1][1];
                    }
                }
            }
        }

        return($type);
    }

    public function getPrunedSubject() {
        $subj = $this->getSubject();

        if (preg_match('/(.*)\(.*\)/', $subj, $matches)) {
            # Strip possible location - useful for reuse groups
            $subj = $matches[1];
        }
        if (preg_match('/\[.*\](.*)/', $subj, $matches)) {
            # Strip possible group name
            $subj = $matches[1];
        }

        $subj = trim($subj);

        # Remove any odd characters.
        $subj = quoted_printable_encode($subj);

        return($subj);
    }

    public function createDraft() {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;
        $sess = session_id();

        $rc = $this->dbhm->preExec("INSERT INTO messages (source, sourceheader, date, fromip, message) VALUES(?,?, NOW(), ?, '');", [
            Message::PLATFORM,
            Message::PLATFORM,
            presdef('REMOTE_ADDR', $_SERVER, NULL)
        ]);
        $id = $rc ? $this->dbhm->lastInsertId() : NULL;

        if ($id) {
            $rc = $this->dbhm->preExec("INSERT INTO messages_drafts (msgid, userid, session) VALUES (?, ?, ?);", [ $id, $myid, $sess ]);
            $id = $rc ? $id : NULL;
        }

        return($id);
    }

    private function removeAttachDir() {
        if (count($this->attachments) == 0) {
            # No attachments - tidy up temp dir.
            rrmdir($this->attach_dir);
            $this->attach_dir = NULL;
        }
    }
    
    # Parse a raw SMTP message.
    public function parse($source, $envelopefrom, $envelopeto, $msg, $groupid = NULL)
    {
        $this->message = $msg;
        $this->groupid = $groupid;
        $this->source = $source;

        $Parser = new PhpMimeMailParser\Parser();
        $this->parser = $Parser;
        $Parser->setText($msg);

        # We save the attachments to a temp directory.  This is tidied up on destruction or save.
        $this->attach_dir = tmpdir();
        $this->attach_files = $Parser->saveAttachments($this->attach_dir . DIRECTORY_SEPARATOR);
        $this->attachments = $Parser->getAttachments();
        $this->yahooapprove = NULL;
        $this->yahooreject = NULL;

        if ($source == Message::YAHOO_PENDING) {
            # This is an APPROVE mail; we need to extract the included copy of the original message.
            $this->yahooapprove = $Parser->getHeader('reply-to');
            if (preg_match('/^(.*-reject-.*yahoogroups.*?)($| |=)/im', $msg, $matches)) {
                $this->yahooreject = trim($matches[1]);
            }

            $atts = $this->getParsedAttachments();
            if (count($atts) >= 1 && $atts[0]->getContentType() == 'message/rfc822') {
                $attachedmsg = $atts[0]->getContent();

                # Remove the old attachments as we're overwriting them.
                $this->removeAttachDir();

                $Parser->setText($attachedmsg);
                $this->attach_files = $Parser->saveAttachments($this->attach_dir);
                $this->attachments = $Parser->getAttachments();
            }
        }

        $this->removeAttachDir();

        # Get IP
        $ip = $this->getHeader('x-freegle-ip');
        $ip = $ip ? $ip : $this->getHeader('x-trash-nothing-user-ip');
        $ip = $ip ? $ip : $this->getHeader('x-yahoo-post-ip');
        $ip = $ip ? $ip : $this->getHeader('x-originating-ip');
        $ip = preg_replace('/[\[\]]/', '', $ip);
        $this->fromip = $ip;

        # See if we can find a group this is intended for.  Can't trust the To header, as the client adds it,
        # and we might also be CC'd or BCC'd.
        $groupname = NULL;
        $to = $this->getApparentlyTo();

        if (count($to) == 0) {
            # ...but if we can't find it, it'll do.
            $to = $this->getTo();
        }

        $rejected = $this->getHeader('x-egroups-rejected-by');

        if (!$rejected) {
            # Rejected messages can look a bit like messages to the group, but they're not.
            foreach ($to as $t) {
                # Check it's to a group (and not the owner).
                if (preg_match('/(.*)@yahoogroups\.co.*/', $t['address'], $matches) &&
                    strpos($t['address'], '-owner@') === FALSE) {
                    # Yahoo group.
                    $groupname = $matches[1];
                    #error_log("Got $groupname from {$t['address']}");
                } else if (preg_match('/(.*)@' . GROUP_DOMAIN . '/', $t['address'], $matches) &&
                    strpos($t['address'], '-volunteers@') === FALSE &&
                    strpos($t['address'], '-auto@') === FALSE) {
                    # Native group.
                    $groupname = $matches[1];
                    #error_log("Got $groupname from {$t['address']}");
                }
            }
        }

        if ($groupname) {
            if (!$this->groupid) {
                # Check if it's a group we host.
                $g = Group::get($this->dbhr, $this->dbhm);
                $this->groupid = $g->findByShortName($groupname);
            }
        }

        if (($source == Message::YAHOO_PENDING || $source == Message::YAHOO_APPROVED) && !$this->groupid) {
            # This is a message from Yahoo, but not for a group we host.  We don't want it.
            $this->removeAttachDir();
            return (FALSE);
        }
        
        $this->envelopefrom = $envelopefrom;
        $this->envelopeto = $envelopeto;
        $this->yahoopendingid = NULL;
        $this->yahooapprovedid = NULL;

        # Get Yahoo pending message id
        if (preg_match('/pending\?view=1&msg=(\d*)/im', $msg, $matches)) {
            $this->yahoopendingid = $matches[1];
        }

        # Get Yahoo approved message id
        $newmanid = $Parser->getHeader('x-yahoo-newman-id');
        if ($newmanid && preg_match('/.*\-m(\d*)/', $newmanid, $matches)) {
            $this->yahooapprovedid = $matches[1];
        }

        # Yahoo posts messages from the group address, but with a header showing the
        # original from address.
        $originalfrom = $Parser->getHeader('x-original-from');

        if ($originalfrom) {
            $from = mailparse_rfc822_parse_addresses($originalfrom);
        } else {
            $from = mailparse_rfc822_parse_addresses($Parser->getHeader('from'));
        }

        $this->fromname = count($from) > 0 ? $from[0]['display'] : NULL;
        $this->fromaddr = count($from) > 0 ? $from[0]['address'] : NULL;

        if (!$this->fromaddr) {
            # We have failed to parse out this message.
            $this->removeAttachDir();
            return (FALSE);
        }

        $this->date = gmdate("Y-m-d H:i:s", strtotime($Parser->getHeader('date')));

        $this->sourceheader = $Parser->getHeader('x-freegle-source');
        $this->sourceheader = ($this->sourceheader == 'Unknown' ? NULL : $this->sourceheader);

        # Store Reply-To only if different from fromaddr.
        $rh = $this->getReplyTo();
        $rh = $rh ? $rh[0]['address'] : NULL;
        $this->replyto = ($rh && strtolower($rh) != strtolower($this->fromaddr)) ? $rh : NULL;

        if (!$this->sourceheader) {
            $this->sourceheader = $Parser->getHeader('x-trash-nothing-source');
            if ($this->sourceheader) {
                $this->sourceheader = "TN-" . $this->sourceheader;
            }
        }

        if (!$this->sourceheader && $Parser->getHeader('x-mailer') == 'Yahoo Groups Message Poster') {
            $this->sourceheader = 'Yahoo-Web';
        }

        if (!$this->sourceheader && (strpos($Parser->getHeader('x-mailer'), 'Freegle Message Maker') !== FALSE)) {
            $this->sourceheader = 'MessageMaker';
        }

        if (!$this->sourceheader) {
            if (stripos($this->fromaddr, 'ilovefreegle.org') !== FALSE) {
                $this->sourceheader = 'Platform';
            } else {
                $this->sourceheader = 'Yahoo-Email';
            }
        }

        $this->subject = $Parser->getHeader('subject');
        $this->messageid = $Parser->getHeader('message-id');
        $this->messageid = str_replace('<', '', $this->messageid);
        $this->messageid = str_replace('>', '', $this->messageid);
        $this->tnpostid = $Parser->getHeader('x-trash-nothing-post-id');

        $this->textbody = $Parser->getMessageBody('text');
        $this->htmlbody = $Parser->getMessageBody('html');

        if ($this->htmlbody) {
            # The HTML body might contain images as img tags, rather than actual attachments.  Extract these too.
            $doc = new DOMDocument();
            @$doc->loadHTML($this->htmlbody);
            $imgs = $doc->getElementsByTagName('img');

            /* @var DOMNodeList $imgs */
            foreach ($imgs as $img) {
                $src = $img->getAttribute('src');

                # We only want to get images from http or https to avoid the security risk of fetching a local file.
                #
                # Wait for 120 seconds to fetch.  We don't want to wait forever, but we see occasional timeouts from Yahoo
                # at 60 seconds.
                #
                # We don't want Yahoo's megaphone images - they're just generic footer images.  Likewise Avast.
                if ((stripos($src, 'http://') === 0 || stripos($src, 'https://') === 0) &&
                    (stripos($src, 'https://s.yimg.com/ru/static/images/yg/img/megaphone') === FALSE) &&
                    (stripos($src, 'https://ipmcdn.avast.com') === FALSE)) {
                    #error_log("Get inline image $src");
                    $ctx = stream_context_create(array('http' =>
                        array(
                            'timeout' => 120
                        )
                    ));

                    $data = @file_get_contents($src, false, $ctx);

                    if ($data) {
                        # Try to convert to an image.  If it's not an image, this will fail.
                        $img = new Image($data);

                        if ($img->img) {
                            $newdata = $img->getData(100);

                            # Ignore small images - Yahoo adds small ones as (presumably) a tracking mechanism, and also their
                            # logo.
                            if ($newdata && $img->width() > 50 && $img->height() > 50) {
                                $this->inlineimgs[] = $newdata;
                            }
                        }
                    }
                }
            }
        }

        # Trash Nothing sends attachments too, but just as links - get those.
        #
        # - links to flic.kr, for groups which for some reason don't like images hosted on TN
        # - links to TN itself
        if (preg_match_all('/(http:\/\/flic\.kr.*)$/m', $this->textbody, $matches)) {
            $urls = [];
            foreach ($matches as $val) {
                foreach ($val as $url) {
                    $urls[] = $url;
                }
            }

            $urls = array_unique($urls);
            foreach ($urls as $url) {
                $ctx = stream_context_create(array('http' =>
                    array(
                        'timeout' => 120
                    )
                ));

                $data = @file_get_contents($url, false, $ctx);

                if ($data) {
                    # Now get the link to the actual image.  DOMDocument chokes on the HTML so do it the dirty way.
                    if (preg_match('#<meta property="og:image" content="(.*)"  data-dynamic="true">#', $data, $matches)) {
                        $imgurl = $matches[1];
                        $ctx = stream_context_create(array('http' =>
                            array(
                                'timeout' => 120
                            )
                        ));

                        $data = @file_get_contents($imgurl, false, $ctx);

                        if ($data) {
                            # Try to convert to an image.  If it's not an image, this will fail.
                            $img = new Image($data);

                            if ($img->img) {
                                $newdata = $img->getData(100);

                                if ($newdata && $img->width() > 50 && $img->height() > 50) {
                                    $this->inlineimgs[] = $newdata;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (preg_match_all('/(https:\/\/trashnothing\.com\/pics\/.*)$/m', $this->textbody, $matches)) {
            $urls = [];
            foreach ($matches as $val) {
                foreach ($val as $url) {
                    $urls[] = $url;
                }
            }

            $urls = array_unique($urls);
            foreach ($urls as $url) {
                $ctx = stream_context_create(array('http' =>
                    array(
                        'timeout' => 120
                    )
                ));

                $data = @file_get_contents($url, false, $ctx);

                if ($data) {
                    # Now get the link to the actual images.
                    $doc = new DOMDocument();
                    @$doc->loadHTML($data);
                    $imgs = $doc->getElementsByTagName('img');

                    /* @var DOMNodeList $imgs */
                    foreach ($imgs as $img) {
                        $src = $img->getAttribute('src');
                        if (strpos($src, '/img/') !== FALSE || strpos($src, '/tn-photos/')) {
                            $ctx = stream_context_create(array('http' =>
                                array(
                                    'timeout' => 120
                                )
                            ));

                            $data = @file_get_contents($src, false, $ctx);

                            if ($data) {
                                # Try to convert to an image.  If it's not an image, this will fail.
                                $img = new Image($data);

                                if ($img->img) {
                                    $newdata = $img->getData(100);

                                    if ($newdata && $img->width() > 50 && $img->height() > 50) {
                                        $this->inlineimgs[] = $newdata;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        # If this is a reuse group, we need to determine the type.
        $g = Group::get($this->dbhr, $this->dbhm, $this->groupid);
        if ($g->getPrivate('type') == Group::GROUP_FREEGLE ||
            $g->getPrivate('type') == Group::GROUP_REUSE
        ) {
            $this->type = $this->determineType($this->subject);
        } else {
            $this->type = Message::TYPE_OTHER;
        }

        if ($source == Message::YAHOO_PENDING || $source == Message::YAHOO_APPROVED  || $source == Message::EMAIL) {
            # Make sure we have a user for the sender.
            $u = User::get($this->dbhr, $this->dbhm);

            # If there is a Yahoo uid in here - which there isn't always - we might be able to find them that way.
            #
            # This is important as well as checking the email address as users can send from the owner address (which
            # we do not allow to be attached to a specific user, as it can be shared by many).
            $iznikid = NULL;
            $userid = NULL;
            $yahoouid = NULL;
            $emailid = NULL;
            $this->modmail = FALSE;

            $iznikid = $Parser->getHeader('x-iznik-from-user');
            if ($iznikid) {
                # We know who claims to have sent this.  There's a slight exploit here where someone could spoof
                # the modmail setting and get a more prominent display.  I may regret writing this comment.
                $userid = $iznikid;
                $this->modmail = filter_var($Parser->getHeader('x-iznik-modmail'), FILTER_VALIDATE_BOOLEAN);
            }

            if (!$userid) {
                # They might have posted from Yahoo.
                $gp = $Parser->getHeader('x-yahoo-group-post');
                if ($gp && preg_match('/u=(.*);/', $gp, $matches)) {
                    $yahoouid = $matches[1];
                    $userid = $u->findByYahooUserId($yahoouid);
                }
            }

            if (!$userid) {
                # Or we might have their email.
                $userid = $u->findByEmail($this->fromaddr);
            }

            if (!$userid) {
                # We don't know them.  Add.
                #
                # We don't have a first and last name, so use what we have. If the friendly name is set to an
                # email address, take the first part.
                $name = $this->fromname;
                if (preg_match('/(.*)@/', $name, $matches)) {
                    $name = $matches[1];
                }

                $userid = $u->create(NULL, NULL, $name, "Incoming message #{$this->id} from {$this->fromaddr} on $groupname");

                # Use the m handle to make sure we find it later.
                $this->dbhr = $this->dbhm;
            }

            if ($userid) {
                # We have a user.
                $u = User::get($this->dbhm, $this->dbhm, $userid);

                # We might not have this yahoo user id associated with this user.
                if ($yahoouid) {
                    $u->setPrivate('yahooUserId', $yahoouid);
                }

                # We might not have this email associated with this user.
                $emailid = $u->addEmail($this->fromaddr, 0, FALSE);

                if ($emailid && ($source == Message::YAHOO_PENDING || $source == Message::YAHOO_APPROVED)) {
                    # Make sure we have a membership for the originator of this message; they were a member
                    # at the time they sent this.  If they have since left we'll pick that up later via a sync.
                    if (!$u->isApprovedMember($this->groupid)) {
                        $u->addMembership($this->groupid, User::ROLE_MEMBER, $emailid, MembershipCollection::APPROVED, NULL, NULL, FALSE);
                    }
                }
            }

            $this->fromuser = $userid;
        }

        # Attachments now safely stored in the DB
        $this->removeAttachDir();
        
        return(TRUE);
    }

    public function pruneMessage() {
        # We are only interested in image attachments; those are what we hive off into the attachments table,
        # and what we display.  They bulk up the message source considerably, which chews up disk space.  Worse,
        # we might have message attachments which are not even image attachments, just for messages we are
        # moderating on groups.
        #
        # So we remove all attachment data within the message.  We do this with a handrolled lame parser, as we
        # don't have a full MIME reassembler.
        $current = $this->message;
        #error_log("Start prune len " . strlen($current));

        # Might have wrong LF format.
        $current = preg_replace('~\R~u', "\r\n", $current);
        $p = 0;

        do {
            $found = FALSE;
            $p = stripos($current, 'Content-Type:', $p);
            #error_log("Found content type at $p");

            if ($p) {
                $crpos = strpos($current, "\r\n", $p);
                $ct = strtolower(substr($current, $p, $crpos - $p));
                #error_log($ct);

                $found = TRUE;

                # We don't want to prune a multipart, only the bottom level parts.
                if (stripos($ct, "multipart") === FALSE) {
                    #error_log("Prune it");
                    # Find the boundary before it.
                    $boundpos = strrpos(substr($current, 0, $p), "\r\n--");

                    if ($boundpos) {
                        #error_log("Found bound");
                        $crpos = strpos($current, "\r\n", $boundpos + 2);
                        $boundary = substr($current, $boundpos + 2, $crpos - ($boundpos + 2));

                        # Find the end of the bodypart headers.
                        $breakpos = strpos($current, "\r\n\r\n", $boundpos);

                        # Find the end of the bodypart.
                        $nextboundpos = strpos($current, $boundary, $breakpos);

                        # Always prune image and HTML bodyparts - images are stored off as attachments, and HTML
                        # bodyparts are quite long and typically there's also a text one present.  Ideally we might
                        # keep HTML, but we need to control our disk space usage.
                        #
                        # For other bodyparts keep a max of 10K. Observant readers may wish to comment on this
                        # definition of K.
                        #error_log("$ct breakpos $breakpos nextboundpos $nextboundpos size " . ($nextboundpos - $breakpos) . " strpos " . strpos($ct, 'image/') . ", " . strpos($ct, 'text/html'));
                        if ($breakpos && $nextboundpos &&
                            (($nextboundpos - $breakpos > 10000) ||
                                (strpos($ct, 'image/') !== FALSE) ||
                                (strpos($ct, 'text/html') !== FALSE))) {
                            # Strip out the bodypart data and replace it with some short text.
                            $current = substr($current, 0, $breakpos + 2) .
                                "\r\n...Content of size " . ($nextboundpos - $breakpos + 2) . " removed...\r\n\r\n" .
                                substr($current, $nextboundpos);
                            #error_log($this->id . " Content of size " . ($nextboundpos - $breakpos + 2) . " removed...");
                        }
                    }
                }
            }

            $p++;
        } while ($found);

        #error_log("End prune len " . strlen($current));

        # Something went horribly wrong?
        # TODO Test.
        $current = (strlen($current) == 0) ? $this->message : $current;

        return($current);
    }

    private function saveAttachments($msgid) {
        if ($this->type != Message::TYPE_TAKEN && $this->type != Message::TYPE_RECEIVED) {
            # Don't want attachments for TAKEN/RECEIVED.  They can occur if people forward the original message.
            #
            # If we crash or fail at this point, we would have mislaid an attachment for a message.  That's not great, but the
            # perf cost of a transaction for incoming messages is significant, and we can live with it.
            $a = new Attachment($this->dbhr, $this->dbhm);

            foreach ($this->attachments as $att) {
                /** @var \PhpMimeMailParser\Attachment $att */
                $ct = $att->getContentType();
                $fn = $this->attach_dir . DIRECTORY_SEPARATOR . $att->getFilename();

                # Can't use LOAD_FILE as server may be remote.
                $data = file_get_contents($fn);

                # Scale the image if it's large.  Ideally we'd store the full size image, but images can be many meg, and
                # it chews up disk space.
                if (strlen($data) > 300000) {
                    $i = new Image($data);
                    if ($i->img) {
                        $w = $i->width();
                        $w = min(1024, $w);
                        $i->scale($w, NULL);
                        $data = $i->getData();
                        $ct = 'image/jpeg';
                    }
                }

                $a->create($msgid, $ct, $data);
            }

            foreach ($this->inlineimgs as $att) {
                $a->create($msgid, 'image/jpeg', $att);
            }
        }
    }

    # Save a parsed message to the DB
    public function save() {
        # Despite what the RFCs might say, it's possible that a message can appear on Yahoo without a Message-ID.  We
        # require unique message ids, so this causes us a problem.  Invent one.
        $this->messageid = $this->messageid ? $this->messageid : (microtime(TRUE). '@' . USER_DOMAIN);

        # We now manipulate the message id a bit.  This is because although in future we will support the same message
        # appearing on multiple groups, and therefore have a unique key on message id, we've not yet tested this.  IT
        # will probably require client changes, and there are issues about what to do when a message is sent to two
        # groups and edited differently on both.  Meanwhile we need to be able to handle messages which are sent to
        # multiple groups, which would otherwise overwrite each other.
        #
        # There is code that does this on message submission too, so that when the message comes back we recognise it.
        $this->messageid = $this->groupid ? ($this->messageid . "-" . $this->groupid) : $this->messageid;

        # See if we have a record of approval from Yahoo.
        $approvedby = NULL;
        $approval = $this->getHeader('x-egroups-approved-by');

        # Reduce the size of the message source
        $this->message = $this->pruneMessage();

        $oldid = NULL;

        # A message we are saving as approved may previously have been in the system:
        # - a message we receive as approved will usually have been here as pending
        # - a message we receive as pending may have been here as approved if it was approved elsewhere before it
        #   reached is.
        $already = FALSE;
        $this->id = NULL;
        $oldid = $this->checkEarlierCopies($approvedby);

        if ($oldid) {
            # Existing message.
            $this->id = $oldid;
            $already = TRUE;
        } else {
            # New message.  Trigger mapping and get subject suggestion.
            $this->suggestedsubject = $this->suggestSubject($this->groupid, $this->subject);

            # Save into the messages table.
            $sql = "INSERT INTO messages (date, source, sourceheader, message, fromuser, envelopefrom, envelopeto, fromname, fromaddr, replyto, fromip, subject, suggestedsubject, messageid, tnpostid, textbody, htmlbody, type, lat, lng, locationid) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $rc = $this->dbhm->preExec($sql, [
                $this->date,
                $this->source,
                $this->sourceheader,
                $this->message,
                $this->fromuser,
                $this->envelopefrom,
                $this->envelopeto,
                $this->fromname,
                $this->fromaddr,
                $this->replyto,
                $this->fromip,
                $this->subject,
                $this->suggestedsubject,
                $this->messageid,
                $this->tnpostid,
                $this->textbody,
                $this->htmlbody,
                $this->type,
                $this->lat,
                $this->lng,
                $this->locationid
            ]);

            if ($rc) {
                $this->id = $this->dbhm->lastInsertId();
                $this->saveAttachments($this->id);

                $l = new Log($this->dbhr, $this->dbhm);
                $l->log([
                    'type' => Log::TYPE_MESSAGE,
                    'subtype' => Log::SUBTYPE_RECEIVED,
                    'msgid' => $this->id,
                    'user' => $this->fromuser,
                    'text' => $this->messageid,
                    'groupid' => $this->groupid
                ]);

                # Now that we have a ID, record which messages are related to this one.
                $this->recordRelated();

                if ($this->groupid) {
                    # Save the group we're on.  If we crash or fail at this point we leave the message stranded, which is ok
                    # given the perf cost of a transaction.
                    $this->dbhm->preExec("INSERT INTO messages_groups (msgid, groupid, msgtype, yahoopendingid, yahooapprovedid, yahooreject, yahooapprove, collection, approvedby,arrival) VALUES (?,?,?,?,?,?,?,?,?,NOW());", [
                        $this->id,
                        $this->groupid,
                        $this->type,
                        $this->yahoopendingid,
                        $this->yahooapprovedid,
                        $this->yahooreject,
                        $this->yahooapprove,
                        MessageCollection::INCOMING,
                        $approvedby
                    ]);
                }

                # Also save into the history table, for spam checking.
                $sql = "INSERT INTO messages_history (groupid, source, fromuser, envelopefrom, envelopeto, fromname, fromaddr, fromip, subject, prunedsubject, messageid, msgid) VALUES(?,?,?,?,?,?,?,?,?,?,?,?);";
                $this->dbhm->preExec($sql, [
                    $this->groupid,
                    $this->source,
                    $this->fromuser,
                    $this->envelopefrom,
                    $this->envelopeto,
                    $this->fromname,
                    $this->fromaddr,
                    $this->fromip,
                    $this->subject,
                    $this->getPrunedSubject(),
                    $this->messageid,
                    $this->id
                ]);
            }
        }

        return([ $this->id, $already ]);
    }

    function recordFailure($reason) {
        $this->dbhm->preExec("UPDATE messages SET retrycount = LAST_INSERT_ID(retrycount),
          retrylastfailure = NOW() WHERE id = ?;", [$this->id]);
        $count = $this->dbhm->lastInsertId();

        $l = new Log($this->dbhr, $this->dbhm);
        $l->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => Log::SUBTYPE_FAILURE,
            'msgid' => $this->id,
            'text' => $reason
        ]);

        return($count);
    }

    public function getHeader($hdr) {
        return($this->parser->getHeader($hdr));
    }

    public function getTo() {
        return(mailparse_rfc822_parse_addresses($this->parser->getHeader('to')));
    }

    public function getApparentlyTo() {
        return(mailparse_rfc822_parse_addresses($this->parser->getHeader('x-apparently-to')));
    }

    public function getReplyTo() {
        $rt = mailparse_rfc822_parse_addresses($this->parser->getHeader('reply-to'));

        # Yahoo can save off the original Reply-To header field.
        $rt = $rt ? $rt : mailparse_rfc822_parse_addresses($this->parser->getHeader('x-original-reply-to'));
        return($rt);
    }

    public function getGroups($includedeleted = FALSE, $justids = TRUE) {
        $ret = [];
        $delq = $includedeleted ? "" : " AND deleted = 0";
        $sql = "SELECT " . ($justids ? 'groupid' : '*') . " FROM messages_groups WHERE msgid = ? $delq;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id ]);
        foreach ($groups as $group) {
            $ret[] = $justids ? $group['groupid'] : $group;
        }

        return($ret);
    }

    public function isPending($groupid) {
        $sql = "SELECT msgid FROM messages_groups WHERE msgid = ? AND groupid = ? AND collection = ? AND deleted = 0;";
        $groups = $this->dbhr->preQuery($sql, [
            $this->id,
            $groupid,
            MessageCollection::PENDING
        ]);

        return(count($groups) > 0);
    }

    public function isApproved($groupid) {
        $sql = "SELECT msgid FROM messages_groups WHERE msgid = ? AND groupid = ? AND collection = ? AND deleted = 0;";
        $groups = $this->dbhr->preQuery($sql, [
            $this->id,
            $groupid,
            MessageCollection::APPROVED
        ]);

        return(count($groups) > 0);
    }

    private function maybeMail($groupid, $subject, $body, $action) {
        if ($subject) {
            # We have a mail to send.
            $me = whoAmI($this->dbhr, $this->dbhm);
            $myid = $me->getId();

            $to = $this->getEnvelopefrom();
            $to = $to ? $to : $this->getFromaddr();

            $g = Group::get($this->dbhr, $this->dbhm, $groupid);
            $atts = $g->getPublic();

            # Find who to send it from.  If we have a config to use for this group then it will tell us.
            $name = $me->getName();
            $c = new ModConfig($this->dbhr, $this->dbhm);
            $cid = $c->getForGroup($me->getId(), $groupid);
            $c = new ModConfig($this->dbhr, $this->dbhm, $cid);
            $fromname = $c->getPrivate('fromname');

            $bcc = $c->getBcc($action);

            if ($bcc) {
                $bcc = str_replace('$groupname', $atts['nameshort'], $bcc);
            }

            if ($fromname == 'Groupname Moderator') {
                $name = '$groupname Moderator';
            }

            # We can do a simple substitution in the from name.
            $name = str_replace('$groupname', $atts['namedisplay'], $name);

            if (ourDomain($to)) {
                # This is a user who we host.  We can therefore send the message via chat.  This is better than
                # sending it by email and then parsing the email later to work out what we intended to send and
                # construct a chat message from it :-).
                $cconly = TRUE;
                $r = new ChatRoom($this->dbhr, $this->dbhm);
                $rid = $r->createUser2Mod($this->getFromuser(), $groupid);

                if ($rid) {
                    $m = new ChatMessage($this->dbhr, $this->dbhm);
                    $mid = $m->create($rid,
                        $myid,
                        "$subject\r\n\r\n$body",
                        ChatMessage::TYPE_MODMAIL,
                        $this->id,
                        FALSE,
                        NULL);

                    $this->mailer($me, TRUE, $this->getFromname(), $bcc, NULL, $name, $g->getModsEmail(), $subject, "(This is a BCC of a message sent to Freegle Direct user #" . $this->getFromuser() . " $to)\n\n" . $body);

                    # We, as a mod, have seen this message - update the roster to show that.  This avoids this message
                    # appearing as unread to us and other mods.
                    $r->updateRoster($myid, $mid, ChatRoom::STATUS_ONLINE);
                }
            } else {
                # For other users, we send the message out by mail.
                $this->mailer($me, TRUE, $this->getFromname(), $to, $bcc, $name, $g->getModsEmail(), $subject, $body);
            }
        }
    }

    public function reject($groupid, $subject, $body, $stdmsgid) {
        # No need for a transaction - if things go wrong, the message will remain in pending, which is the correct
        # behaviour.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $this->log->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => $subject ? Log::SUBTYPE_REJECTED : Log::SUBTYPE_DELETED,
            'msgid' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'user' => $this->fromuser,
            'groupid' => $groupid,
            'text' => $subject,
            'stdmsgid' => $stdmsgid
        ]);

        $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND groupid = ? AND deleted = 0;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);
        foreach ($groups as $group) {
            if ($group['yahooreject']) {
                # We can trigger rejection by email - do so.
                $this->mailer($me, TRUE, $group['yahooreject'], $group['yahooreject'], NULL, MODERATOR_EMAIL, MODERATOR_EMAIL, "My name is Iznik and I reject this message", "");
            }

            if ($group['yahoopendingid']) {
                # We can trigger rejection via the plugin - do so.
                $p = new Plugin($this->dbhr, $this->dbhm);
                $p->add($groupid, [
                    'type' => 'RejectPendingMessage',
                    'id' => $group['yahoopendingid']
                ]);
            }
        }

        # When rejecting, we put it in the appropriate collection, which means the user can potentially edit and
        # resend.
        if ($subject) {
            $sql = $subject ? "UPDATE messages_groups SET collection = ? WHERE msgid = ?;" : "UPDATE messages_groups SET deleted = 1 WHERE msgid = ?;";
            $this->dbhm->preExec($sql, [
                MessageCollection::REJECTED,
                $this->id
            ]);
        } else {
            $sql = $subject ? "UPDATE messages_groups SET collection = 'Rejected', rejectedat = NOW() WHERE msgid = ?;" : "UPDATE messages_groups SET deleted = 1 WHERE msgid = ?;";
            $this->dbhm->preExec($sql, [
                $this->id
            ]);
        }

        $this->notif->notifyGroupMods($groupid);

        $this->maybeMail($groupid, $subject, $body, 'Reject');
    }

    public function approve($groupid, $subject, $body, $stdmsgid, $yahooonly = FALSE) {
        # No need for a transaction - if things go wrong, the message will remain in pending, which is the correct
        # behaviour.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        if (!$yahooonly) {
            $this->log->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_APPROVED,
                'msgid' => $this->id,
                'user' => $this->fromuser,
                'byuser' => $myid,
                'groupid' => $groupid,
                'stdmsgid' => $stdmsgid,
                'text' => $subject
            ]);
        }

        $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND groupid = ? AND deleted = 0;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);
        foreach ($groups as $group) {
            if ($group['yahooapprove']) {
                # We can trigger approval by email - do so.
                $this->mailer($me, TRUE, $group['yahooapprove'], $group['yahooapprove'], NULL, MODERATOR_EMAIL, MODERATOR_EMAIL, "My name is Iznik and I reject this message", "");
            }

            if ($group['yahoopendingid']) {
                # We can trigger approval via the plugin - do so.
                $p = new Plugin($this->dbhr, $this->dbhm);
                $p->add($groupid, [
                    'type' => 'ApprovePendingMessage',
                    'id' => $group['yahoopendingid']
                ]);
            }
        }

        if (!$yahooonly) {
            $sql = "UPDATE messages_groups SET collection = ?, approvedby = ? WHERE msgid = ? AND groupid = ?;";
            $rc = $this->dbhm->preExec($sql, [
                MessageCollection::APPROVED,
                $myid,
                $this->id,
                $groupid
            ]);

            #error_log("Approve $rc from $sql, $myid, {$this->id}, $groupid");

            $this->notif->notifyGroupMods($groupid);

            $this->maybeMail($groupid, $subject, $body, 'Approve');
        }

        $this->index();
    }

    public function reply($groupid, $subject, $body, $stdmsgid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $this->log->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => Log::SUBTYPE_REPLIED,
            'msgid' => $this->id,
            'user' => $this->fromuser,
            'byuser' => $me ? $me->getId() : NULL,
            'groupid' => $groupid,
            'text' => $subject,
            'stdmsgid' => $stdmsgid
        ]);

        $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND groupid = ?;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);
        foreach ($groups as $group) {
            $this->maybeMail($groupid, $subject, $body, $group['collection'] == MessageCollection::APPROVED ? 'Leave Approved Message' : 'Leave');
        }
    }

    function hold() {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $sql = "UPDATE messages SET heldby = ? WHERE id = ?;";
        $rc = $this->dbhm->preExec($sql, [ $me->getId(), $this->id ]);

        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_HOLD,
                'msgid' => $this->id,
                'byuser' => $me ? $me->getId() : NULL
            ]);
        }
    }

    function release() {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $sql = "UPDATE messages SET heldby = NULL WHERE id = ?;";
        $rc = $this->dbhm->preExec($sql, [ $this->id ]);

        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_RELEASE,
                'msgid' => $this->id,
                'byuser' => $me ? $me->getId() : NULL
            ]);
        }
    }

    function delete($reason = NULL, $groupid = NULL, $subject = NULL, $body = NULL, $stdmsgid = NULL, $localonly = FALSE)
    {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $rc = true;

        if ($this->attach_dir) {
            rrmdir($this->attach_dir);
        }

        if ($this->id) {
            # Delete from a specific or all groups that it's on.
            $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND " . ($groupid ? " groupid = ?" : " ?") . ";";
            $groups = $this->dbhr->preQuery($sql,
                [
                    $this->id,
                    $groupid ? $groupid : 1
                ]);

            $logged = FALSE;

            foreach ($groups as $group) {
                $groupid = $group['groupid'];

                $this->log->log([
                    'type' => Log::TYPE_MESSAGE,
                    'subtype' => Log::SUBTYPE_DELETED,
                    'msgid' => $this->id,
                    'user' => $this->fromuser,
                    'byuser' => $me ? $me->getId() : NULL,
                    'text' => $reason,
                    'groupid' => $groupid,
                    'stdmsgid' => $stdmsgid
                ]);

                # The message has been allocated to a group; mark it as deleted.  We keep deleted messages for
                # PD.
                #
                # We must zap the Yahoo IDs as we have a unique index on them.
                $rc = $this->dbhm->preExec("UPDATE messages_groups SET deleted = 1, yahooapprovedid = NULL, yahoopendingid = NULL WHERE msgid = ? AND groupid = ?;", [
                    $this->id,
                    $groupid
                ]);

                if (!$localonly) {
                    # We might be deleting an approved message or spam.
                    if ($group['yahooapprovedid']) {
                        # We can trigger deleted via the plugin - do so.
                        $p = new Plugin($this->dbhr, $this->dbhm);
                        $p->add($groupid, [
                            'type' => 'DeleteApprovedMessage',
                            'id' => $group['yahooapprovedid']
                        ]);
                    } else {
                        # Or we might be deleting a pending or spam message, in which case it may also need rejecting on Yahoo.
                        if ($group['yahooreject']) {
                            # We can trigger rejection by email - do so.
                            $this->mailer($me, TRUE, $group['yahooreject'], $group['yahooreject'], NULL, MODERATOR_EMAIL, MODERATOR_EMAIL, "My name is Iznik and I reject this message", "");
                        }

                        if ($group['yahoopendingid']) {
                            # We can trigger rejection via the plugin - do so.
                            $p = new Plugin($this->dbhr, $this->dbhm);
                            $p->add($groupid, [
                                'type' => 'RejectPendingMessage',
                                'id' => $group['yahoopendingid']
                            ]);
                        }
                    }
                }

                if ($groupid) {
                    $this->notif->notifyGroupMods($groupid);
                    $this->maybeMail($groupid, $subject, $body, $group['collection'] == MessageCollection::APPROVED ? 'Delete Approved Message' : 'Delete');
                }
            }

            # If we have deleted this message from all groups, mark it as deleted in the messages table.
            $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND deleted = 0;";
            $groups = $this->dbhr->preQuery($sql, [ $this->id ]);

            if (count($groups) === 0) {
                # We must zap the messageid as we have a unique index on it
                $rc = $this->dbhm->preExec("UPDATE messages SET deleted = NOW(), messageid = NULL WHERE id = ?;", [ $this->id ]);

                # Remove from the search index.
                $this->s->delete($this->id);
            }
        }

        return($rc);
    }

    public function index() {
        $groups = $this->getGroups(FALSE, FALSE);
        foreach ($groups as $group) {
            # Add into the search index.
            $this->s->add($this->id, $this->subject, strtotime($group['arrival']), $group['groupid']);
        }
    }

    public function findEarlierCopy($groupid, $pendingid, $approvedid) {
        $sql = "SELECT msgid, collection FROM messages_groups WHERE groupid = ? AND " . ($pendingid ? 'yahoopendingid' : 'yahooapprovedid') . " = ?;";
        $msgs = $this->dbhr->preQuery($sql, [
            $groupid,
            $pendingid ? $pendingid : $approvedid
        ]);

        $msgid = count($msgs) == 0 ? NULL : $msgs[0]['msgid'];
        $collection = count($msgs) == 0 ? NULL : $msgs[0]['collection'];

        return([ $msgid, $collection ]);
    }

    public function checkEarlierCopies($approvedby) {
        # We don't need a transaction for this - transactions aren't great for scalability and worst case we
        # leave a spurious message around which a mod will handle.
        $ret = NULL;
        $sql = "SELECT * FROM messages WHERE messageid = ? ";
        if ($this->groupid) {
            # We might have a message already present which doesn't match on Message-ID (because Yahoo doesn't
            # always put it in) but matches on the approved/pending id.
            if ($this->yahooapprovedid) {
                $sql .= " OR id = (SELECT msgid FROM messages_groups WHERE groupid = {$this->groupid} AND yahooapprovedid = {$this->yahooapprovedid}) ";
            }
            if ($this->yahoopendingid) {
                $sql .= " OR id = (SELECT msgid FROM messages_groups WHERE groupid = {$this->groupid} AND yahoopendingid = {$this->yahoopendingid}) ";
            }
        }

        $msgs = $this->dbhr->preQuery($sql, [ $this->getMessageID() ]);
        #error_log($sql . $this->getMessageID());

        foreach ($msgs as $msg) {
            #error_log("In #{$this->id} found {$msg['id']} with " . $this->getMessageID());
            $ret = $msg['id'];
            $changed = '';
            $m = new Message($this->dbhr, $this->dbhm, $msg['id']);

            # We want the new message to have the spam type of the old message, because we check this to ensure
            # we don't move messages back from not spam to spam.
            $this->spamtype = $m->getPrivate('spamtype');
            
            # We want the old message to be on whatever group this message was sent to.
            #
            # We want to see the message on the group even if it's been deleted, otherwise we'll go ahead and try
            # to re-add it and get an exception.
            $oldgroups = $m->getGroups(TRUE);
            #error_log("Compare groups $this->groupid vs " . var_export($oldgroups, TRUE));
            if (!in_array($this->groupid, $oldgroups)) {
                // This code is here for future handling of the same message on multiple groups, but since we
                // currently make the message id per-group, we can't reach it.  Keep it for later use but don't
                // worry that we can't cover it.
                // @codeCoverageIgnoreStart
                /* @cov $collection */
                $collection = NULL;
                if ($this->getSource() == Message::YAHOO_PENDING) {
                    $collection = MessageCollection::PENDING;
                } else if ($this->getSource() == Message::YAHOO_APPROVED) {
                    $collection = MessageCollection::APPROVED;
                } else if ($this->getSource() == Message::EMAIL && $this->groupid) {
                    $collection = MessageCollection::INCOMING;
                }
                #error_log("Not on group, add to $collection");

                if ($collection) {
                    $this->dbhm->preExec("INSERT INTO messages_groups (msgid, groupid, msgtype, yahoopendingid, yahooapprovedid, yahooreject, yahooapprove, collection, approvedby, arrival) VALUES (?,?,?,?,?,?,?,?,?,NOW());", [
                        $msg['id'],
                        $this->groupid,
                        $m->getType(),
                        $this->yahoopendingid,
                        $this->yahooapprovedid,
                        $this->yahooreject,
                        $this->yahooapprove,
                        $collection,
                        $approvedby
                    ]);
                }
            } else {
                // @codeCoverageIgnoreEnd
                # Already on the group; pick up any new and better info.
                #error_log("Already on group, pick ");
                $gatts = $this->dbhr->preQuery("SELECT * FROM messages_groups WHERE msgid = ? AND groupid = ?;", [
                    $msg['id'],
                    $this->groupid
                ]);
                foreach ($gatts as $gatt) {
                    foreach (['yahooapprovedid', 'yahoopendingid'] as $newatt) {
                        #error_log("Compare old {$gatt[$newatt]} vs new {$this->$newatt}");
                        if (!$gatt[$newatt] || ($this->$newatt && $gatt[$newatt] != $this->$newatt)) {
                            #error_log("Update mesages_groups for $newatt");
                            $this->dbhm->preExec("UPDATE messages_groups SET $newatt = ? WHERE msgid = ? AND groupid = ?;", [
                                $this->$newatt,
                                $msg['id'],
                                $this->groupid
                            ]);
                        }
                    }
                }
            }

            # For pending messages which come back to us as approved, it might not be the same.
            # This can happen if a message is handled on another system, e.g. moderated directly on Yahoo.
            #
            # For approved messages which only reach us as pending later, we don't want to change the approved
            # version.
            if ($this->source == Message::YAHOO_APPROVED) {
                # Other atts which we want the latest version of.
                foreach (['date', 'subject', 'message', 'textbody', 'htmlbody'] as $att) {
                    $oldval = $m->getPrivate($att);
                    $newval = $this->getPrivate($att);

                    if (!$oldval || ($newval && $oldval != $newval)) {
                        $changed .= ' $att';
                        #error_log("Update messages for $att, value len " . strlen($oldval) . " vs " . strlen($newval));
                        $m->setPrivate($att, $newval);
                    }
                }

                # We might need a new suggested subject, and mapping.
                $m->setPrivate('suggestedsubject', NULL);
                $m->suggestedsubject = $m->suggestSubject($this->groupid, $this->subject);

                # We keep the old set of attachments, because they might be mentioned in (for example) the text
                # of the message.  This means that we don't support editing of the attachments on Yahoo.

                # We might have new approvedby info.
                $rc = $this->dbhm->preExec("UPDATE messages_groups SET approvedby = ? WHERE msgid = ? AND groupid = ? AND approvedby IS NULL;",
                    [
                        $approvedby,
                        $msg['id'],
                        $this->groupid
                    ]);

                $changed = $rc ? ' approvedby' : $changed;

                # This message might have moved from pending to approved.
                if ($m->getSource() == Message::YAHOO_PENDING && $this->getSource() == Message::YAHOO_APPROVED) {
                    $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Approved' WHERE msgid = ? AND groupid = ?;", [
                        $msg['id'],
                        $this->groupid
                    ]);
                    $changed = TRUE;
                }

                if ($changed != '') {
                    $me = whoAmI($this->dbhr, $this->dbhm);

                    $this->log->log([
                        'type' => Log::TYPE_MESSAGE,
                        'subtype' => Log::SUBTYPE_EDIT,
                        'msgid' => $msg['id'],
                        'byuser' => $me ? $me->getId() : NULL,
                        'text' => "Updated from new incoming message ($changed)"
                    ]);
                }
            }
        }

        return($ret);
    }

    /**
     * @return mixed
     */
    public function getFromuser()
    {
        return $this->fromuser;
    }

    public function findFromReply($userid) {
        # Unfortunately, it's fairly common for people replying by email to compose completely new
        # emails with subjects of their choice, or reply from Yahoo Groups which doesn't add
        # In-Reply-To headers.  So we just have to do the best we can using the email subject.  The Damerau–Levenshtein
        # distance does this for us - if we get a subject which is just "Re: " and the original, then that will come
        # top.  We can't do that in the DB, though, as we need to strip out some stuff.
        #
        # We only expect to be matching replies for reuse/Freegle groups, and it's not worth matching against any
        # old messages.
        $sql = "SELECT messages.id, subject, messages.date FROM messages INNER JOIN messages_groups ON messages_groups.msgid = messages.id AND fromuser = ? INNER JOIN groups ON groups.id = messages_groups.groupid AND groups.type IN ('Freegle', 'Reuse') AND DATEDIFF(NOW(), messages.arrival) < 90 LIMIT 1000;";
        $messages = $this->dbhr->preQuery($sql, [ $userid ]);

        $thissubj = Message::canonSubj($this->subject);

        # This is expected to be a reply - so remove the most common reply tag.
        $thissubj = preg_replace('/^Re\:/i', '', $thissubj);

        # Remove any punctuation and whitespace from the purported item.
        $thissubj = preg_replace('/\-|\,|\.| /', '', $thissubj);

        $mindist = PHP_INT_MAX;
        $match = FALSE;
        $matchmsg = NULL;

        foreach ($messages as $message) {
            $subj1 = $thissubj;
            $subj2 = Message::canonSubj($message['subject']);

            # Remove any punctuation and whitespace from the purported item.
            $subj2 = preg_replace('/\-|\,|\.| /', '', $subj2);

            # Find the distance.  We do this in PHP rather than in MySQL because we have done all this
            # munging on the subject.
            $d = new DamerauLevenshtein(strtolower($subj1), strtolower($subj2));
            $message['dist'] = $d->getSimilarity();
            $mindist = min($mindist, $message['dist']);

            #error_log("Compare subjects $subj1 vs $subj2 dist {$message['dist']} min $mindist lim " . (strlen($subj1) * 3 / 4));

            if ($message['dist'] <= $mindist && $message['dist'] <= strlen($subj1) * 3 / 4) {
                # This is the closest match, but not utterly different.
                #error_log("Closest");
                $match = TRUE;
                $matchmsg = $message;
            }
        }

        return(($match && $matchmsg['id']) ? $matchmsg['id'] : NULL);
    }
    
    public function stripQuoted() {
        # Try to get the text we care about by stripping out quoted text.  This can't be
        # perfect - quoting varies and it's a well-known hard problem.
        $htmlbody = $this->getHtmlbody();
        $textbody = $this->getTextbody();

        if ($htmlbody && !$textbody) {
            $html = new \Html2Text\Html2Text($htmlbody);
            $textbody = $html->getText();
            #error_log("Converted HTML text $textbody");
        }

        $textbody = trim(preg_replace('#(^\w.+:\n)?(^(>|\|).*(\n|$))+#mi', "", $textbody));

        # We might have a section like this, for example from eM Client, which could be top or bottom-quoted.
        #
        # ------ Original Message ------
        # From: "Edward Hibbert" <notify-5147-16226909@users.ilovefreegle.org>
        # To: log@ehibbert.org.uk
        # Sent: 14/05/2016 14:19:19
        # Subject: Re: [FreeglePlayground] Offer: chair (Hesketh Lane PR3)
        $p = strpos($textbody, '------ Original Message ------');

        if ($p !== FALSE) {
            $q = strpos($textbody, "\r\n\r\n", $p);
            $textbody = ($q !== FALSE) ? (substr($textbody, 0, $p) . substr($textbody, $q)) : substr($textbody, 0, $p);
        }

        # Or this similar one, which is top-quoted.
        #
        # ----Original message----
        $p = strpos($textbody, '----Original message----');
        $textbody = $p ? substr($textbody, 0, $p) : $textbody;

        # Or this, which is top-quoted.
        $p = strpos($textbody, '--------------------------------------------');
        $textbody = $p ? substr($textbody, 0, $p) : $textbody;

        # Or TN's
        #
        # _________________________________________________________________
        $p = strpos($textbody, '_________________________________________________________________');
        $textbody = $p ? substr($textbody, 0, $p) : $textbody;

        # Or this:
        #
        # -------- Original message --------
        $p = strpos($textbody, '-------- Original message --------');
        $textbody = $p ? substr($textbody, 0, $p) : $textbody;

        # Or this:
        # _____
        $p = strpos($textbody, '_____');
        $textbody = $p ? substr($textbody, 0, $p) : $textbody;

        # Or this:
        # _____
        $p = strpos($textbody, '-----Original Message-----');
        $textbody = $p ? substr($textbody, 0, $p) : $textbody;

        # Or Windows phones:
        #
        # ________________________________
        $p = strpos($textbody, '________________________________');
        $textbody = $p ? substr($textbody, 0, $p) : $textbody;

        # A reply from us.
        $p = strpos($textbody, "You can respond by just replying to this email");
        $textbody = $p ? substr($textbody, 0, $p) : $textbody;

        # Or we might have this, for example from GMail:
        #
        # On Sat, May 14, 2016 at 2:19 PM, Edward Hibbert <
        # notify-5147-16226909@users.ilovefreegle.org> wrote:
        if (preg_match('/(.*)On.*wrote\:$(.*)/ms', $textbody, $matches)) {
            $textbody = $matches[1] . $matches[2];
        }

        # Or we might have this, as a reply from a Yahoo Group message.
        if (preg_match('/(.*)^To\:.*yahoogroups.*$.*__,_._,___(.*)/ms', $textbody, $matches)) {
            $textbody = $matches[1] . $matches[2];
        }

        if (preg_match('/(.*)__,_._,___(.*)/ms', $textbody, $matches)) {
            $textbody = $matches[1];
        }

        # Or we might have some headers
        $textbody = preg_replace('/^From:.*?$/mi', '', $textbody);
        $textbody = preg_replace('/^Sent:.*?$/mi', '', $textbody);

        # Get rid of sigs
        $textbody = preg_replace('/^Get Outlook for Android.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from my iPad.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from my .*smartphone./ms', '', $textbody);
        $textbody = preg_replace('/^Sent from my iPhone.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from EE.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from my Samsung device.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from my Windows Phone.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from the trash nothing! Mobile App.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent on the go from.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from Yahoo Mail.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from Mail.*/ms', '', $textbody);
        $textbody = preg_replace('/^Sent from my BlackBerry.*/ms', '', $textbody);

        // Duff text added by Yahoo Mail app.
        $textbody = str_replace('blockquote, div.yahoo_quoted { margin-left: 0 !important; border-left:1px #715FFA solid !important; padding-left:1ex !important; background-color:white !important; }', '', $textbody);
        $textbody = preg_replace('/\#yiv.*\}\}/', '', $textbody);

        #error_log("Pruned text to $textbody");

        // We might have links to our own site.  Strip these in case they contain login information.
        $textbody = preg_replace('/https:\/\/' . USER_SITE . '\S*/', 'https://' . USER_SITE, $textbody);

        return(trim($textbody));
    }
    
    public static function canonSubj($subj, $lower = TRUE) {
        if ($lower) {
            $subj = strtolower($subj);
        }

        // Remove any group tag
        $subj = preg_replace('/^\[.*?\](.*)/', "$1", $subj);

        // Remove duplicate spaces
        $subj = preg_replace('/\s+/', ' ', $subj);

        $subj = trim($subj);

        return($subj);
    }

    public static function removeKeywords($type, $subj) {
        $keywords = Message::keywords();
        if (pres($type, $keywords)) {
            foreach ($keywords[$type] as $keyword) {
                $subj = preg_replace('/(^|\b)' . preg_quote($keyword) . '\b/i', '', $subj);
            }
        }

        return($subj);
    }

    public function recordRelated() {
        # Message A is related to message B if:
        # - they are from the same underlying sender (people may post via multiple routes)
        # - A is an OFFER and B a TAKEN, or A is a WANTED and B is a RECEIVED
        # - the TAKEN/RECEIVED is more recent than the OFFER/WANTED (because if it's earlier, it can't be a TAKEN for this OFFER)
        # - the OFFER/WANTED is more recent than any previous previous similar TAKEN/RECEIVED (because then we have a repost
        #   or similar items scenario, and the earlier TAKEN will be related to still earlier OFFERs
        #
        # We might explicitly flag a message using X-Iznik-Related-To
        switch ($this->type) {
            case Message::TYPE_OFFER: $type = Message::TYPE_TAKEN; $datedir = 1; break;
            case Message::TYPE_TAKEN: $type = Message::TYPE_OFFER; $datedir = -1; break;
            case Message::TYPE_WANTED: $type = Message::TYPE_RECEIVED; $datedir = 1; break;
            case Message::TYPE_RECEIVED: $type = Message::TYPE_WANTED; $datedir = -1; break;
            default: $type = NULL;
        }

        $found = 0;
        $loc = NULL;

        $thissubj = Message::canonSubj($this->subject);

        if (preg_match('/.*?\:.*\((.*)\)/', $thissubj, $matches)) {
            $loc = trim($matches[1]);
        }

        if (preg_match('/.*?\:(.*)\(.*\)/', $this->subject, $matches)) {
            # Standard format - extract the item.
            $thissubj = trim($matches[1]);
        } else {
            # Non-standard format.  Remove the keywords.
            $thissubj = Message::removeKeywords($this->type, $thissubj);
        }

        # Remove any punctuation and whitespace from the purported item.
        $thissubj = preg_replace('/\-|\,|\.| /', '', $thissubj);

        if ($type) {
            $sql = "SELECT id, subject, date FROM messages WHERE fromuser = ? AND type = ? AND DATEDIFF(NOW(), arrival) <= 31;";
            $messages = $this->dbhr->preQuery($sql, [ $this->fromuser, $type ]);
            #error_log($sql . var_export([ $thissubj, $thissubj, $this->fromuser, $type ], TRUE));
            $thistime = strtotime($this->date);

            $mindist = PHP_INT_MAX;
            $match = FALSE;
            $matchmsg = NULL;

            foreach ($messages as $message) {
                $messsubj = Message::canonSubj($message['subject']);
                #error_log("Compare {$message['date']} vs {$this->date}, " . strtotime($message['date']) . " vs $thistime");

                if ((($datedir == 1) && strtotime($message['date']) >= $thistime) ||
                    (($datedir == -1) && strtotime($message['date']) <= $thistime)) {
                    if (preg_match('/.*?\:(.*)\(.*\)/', $messsubj, $matches)) {
                        # Standard format = extract the item.
                        $subj2 = trim($matches[1]);
                    } else {
                        # Non-standard - remove keywords.
                        $subj2 = Message::removeKeywords($type, $messsubj);

                        # We might have identified a valid location in the original message which appears in a non-standard
                        # way.
                        $subj2 = $loc ? str_ireplace($loc, '', $subj2) : $subj2;
                    }

                    $subj1 = $thissubj;

                    # Remove any punctuation and whitespace from the purported item.
                    $subj2 = preg_replace('/\-|\,|\.| /', '', $subj2);

                    # Find the distance.  We do this in PHP rather than in MySQL because we have done all this
                    # munging on the subject to extract the relevant bit.
                    $d = new DamerauLevenshtein(strtolower($subj1), strtolower($subj2));
                    $message['dist'] = $d->getSimilarity();
                    $mindist = min($mindist, $message['dist']);

                    #error_log("Compare subjects $subj1 vs $subj2 dist {$message['dist']} min $mindist lim " . (strlen($subj1) * 3 / 4));

                    if (strtolower($subj1) == strtolower($subj2)) {
                        # Exact match
                        #error_log("Exact");
                        $match = TRUE;
                        $matchmsg = $message;
                    } else if ($message['dist'] <= $mindist && $message['dist'] <= strlen($subj1) * 3 / 4) {
                        # This is the closest match, but not utterly different.
                        #error_log("Closest");
                        $match = TRUE;
                        $matchmsg = $message;
                    }
                }
            }

            #error_log("Match $match message " . var_export($matchmsg, TRUE));

            if ($match && $matchmsg['id']) {
                # We seem to get a NULL returned in circumstances I don't quite understand but but which relate to
                # the use of DAMLEVLIM.
                #error_log("Best match {$matchmsg['subject']}");
                $sql = "INSERT IGNORE INTO messages_related (id1, id2) VALUES (?,?);";
                $this->dbhm->preExec($sql, [ $this->id, $matchmsg['id']] );

                if ($this->getSourceheader() != Message::PLATFORM &&
                    ($this->type == Message::TYPE_TAKEN || $this->type == Message::TYPE_RECEIVED)) {
                    # Also record an outcome on the original message.  We only need to do this when the message didn't
                    # come from our platform, because if it did that has already happened.  This also avoids the
                    # situation where we match against the wrong message because of the order messages arrive from Yahoo.
                    $this->dbhm->preExec("INSERT INTO messages_outcomes (msgid, outcome, happiness, userid, comments) VALUES (?,?,?,?,?);", [
                        $matchmsg['id'],
                        $this->type == Message::TYPE_TAKEN ? Message::OUTCOME_TAKEN : Message::OUTCOME_RECEIVED,
                        NULL,
                        NULL,
                        $this->getTextbody()
                    ]);
                }

                $found++;
            }
        }

        return($found);
    }

    public function spam($groupid) {
        # We mark is as spam on all groups, and delete it on the specific one in question.
        $this->dbhm->preExec("UPDATE messages_groups SET collection = ? WHERE msgid = ?;", [ MessageCollection::SPAM, $this->id ]);
        $this->delete("Deleted as spam", $groupid);

        # Record for training.
        $this->dbhm->preExec("REPLACE INTO messages_spamham (msgid, spamham) VALUES (?, ?);", [ $this->id , Spam::SPAM ]);
    }

    public function notSpam() {
        if ($this->spamtype == Spam::REASON_SUBJECT_USED_FOR_DIFFERENT_GROUPS) {
            # This subject is probably fine, then.
            $s = new Spam($this->dbhr, $this->dbhm);
            $s->notSpamSubject($this->getPrunedSubject());
        }

        # We leave the spamreason and type set in the message, because it can be useful for later PD.
        #
        # Record for training.
        $this->dbhm->preExec("REPLACE INTO messages_spamham (msgid, spamham) VALUES (?, ?);", [ $this->id , Spam::HAM ]);
    }

    public function suggestSubject($groupid, $subject) {
        $newsubj = $subject;
        $g = Group::get($this->dbhr, $this->dbhm, $groupid);

        # This method is used to improve subjects, and also to map - because we need to make sure we understand the
        # subject format before can map.
        $type = $this->determineType($subject);
        $keywords = $g->getSetting('keywords', []);

        switch ($type) {
            case Message::TYPE_OFFER:
            case Message::TYPE_TAKEN:
            case Message::TYPE_WANTED:
            case Message::TYPE_RECEIVED:
                # Remove any subject tag.
                $subject = preg_replace('/\[.*?\]\s*/', '', $subject);

                $pretag = $subject;

                # Strip any of the keywords.
                foreach ($this->keywords()[$type] as $keyword) {
                    $subject = preg_replace('/(^|\b)' . preg_quote($keyword) . '\b/i', '', $subject);
                }

                # Only proceed if we found the type tag.
                if ($subject != $pretag) {
                    # Shrink multiple spaces
                    $subject = preg_replace('/\s+/', ' ', $subject);
                    $subject = trim($subject);

                    # Find a location in the subject.  Only seek ) at end because if it's in the middle it's probably
                    # not a location.
                    $loc = NULL;
                    $l = new Location($this->dbhr, $this->dbhm);

                    if (preg_match('/(.*)\((.*)\)$/', $subject, $matches)) {
                        # Find the residue, which will be the item, and tidy it.
                        $residue = trim($matches[1]);

                        $aloc = $matches[2];

                        # Check if it's a good location.
                        #error_log("Check loc $aloc");
                        $locs = $l->search($aloc, $groupid, 1);
                        #error_log(var_export($locs, TRUE));

                        if (count($locs) == 1) {
                            # Take the name we found, which may be better than the one we have, if only in capitalisation.
                            $loc = $locs[0];
                        }
                    } else {
                        # The subject is not well-formed.  But we can try anyway.
                        #
                        # Look for an exact match for a known location in the subject.
                        $locs = $l->locsForGroup($groupid);
                        $bestpos = 0;
                        $bestlen = 0;
                        $loc = NULL;

                        foreach ($locs as $aloc) {
                            #error_log($aloc['name']);
                            $xp = '/\b' . preg_quote($aloc['name'],'/') . '\b/i';
                            #error_log($xp);
                            $p = preg_match($xp, $subject, $matches, PREG_OFFSET_CAPTURE);
                            #error_log("$subject matches as $p with $xp");
                            $p = $p ? $matches[0][1] : FALSE;
                            #error_log("p2 $p");

                            if ($p !== FALSE &&
                                (strlen($aloc['name']) > $bestlen ||
                                 (strlen($aloc['name']) == $bestlen && $p > $bestpos))) {
                                # The longer a location is, the more likely it is to be the correct one.  If we get a
                                # tie, then the further right it is, the more likely to be a location.
                                $loc = $aloc;
                                $bestpos = $p;
                                $bestlen = strlen($loc['name']);
                            }
                        }

                        $residue = preg_replace('/' . preg_quote($loc['name']) . '/i', '', $subject);
                    }

                    if ($loc) {
                        $punc = '\(|\)|\[|\]|\,|\.|\-|\{|\}|\:|\;| ';
                        $residue = preg_replace('/^(' . $punc . ')*/','', $residue);
                        $residue = preg_replace('/(' . $punc . '){2,}$/','', $residue);
                        $residue = trim($residue);

                        if ($residue == strtoupper($residue)) {
                            # All upper case.  Stop it being shouty.
                            $residue = strtolower($residue);
                        }

                        $typeval = presdef(strtolower($type), $keywords, strtoupper($type));
                        $newsubj = $typeval . ": $residue ({$loc['name']})";

                        $this->lat = $loc['lat'];
                        $this->lng = $loc['lng'];
                        $this->locationid = $loc['id'];

                        if ($this->fromuser) {
                            # Save off this as the last known location for this user.
                            $this->dbhm->preExec("UPDATE users SET lastlocation = ? WHERE id = ?;", [
                                $this->locationid,
                                $this->fromuser
                            ]);
                            User::clearCache($this->fromuser);
                        }
                    }
                }
               break;
        }

        return($newsubj);
    }

    public function replaceAttachments($atts) {
        # We have a list of attachments which may or may not currently be attached to the message we're interested in,
        # which might have other attachments which need zapping.
        $oldids = [];
        $oldatts = $this->dbhm->preQuery("SELECT id FROM messages_attachments WHERE msgid = ?;", [ $this->id ]);
        foreach ($oldatts as $oldatt) {
            $oldids[] = $oldatt['id'];
        }

        foreach ($atts as $attid) {
            $this->dbhm->preExec("UPDATE messages_attachments SET msgid = ? WHERE id = ?;", [ $this->id, $attid ]);
            $key = array_search($attid, $oldids);
            if ($key !== FALSE) {
                unset($oldids[$key]);
            }
        }

        foreach ($oldids as $oldid) {
            $this->dbhm->preExec("DELETE FROM messages_attachments WHERE id = ?;", [ $oldid ]);
        }
    }

    public function search($string, &$context, $limit = Search::Limit, $restrict = NULL, $groups = NULL, $locationid = NULL, $exactonly = FALSE) {
        $ret = $this->s->search($string, $context, $limit, $restrict, $groups, $exactonly);
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        if (count($ret) > 0 && $myid) {
            $maxid = $ret[0]['id'];
            $s = new UserSearch($this->dbhr, $this->dbhm);
            $s->create($myid, $maxid, $string, $locationid);
        }

        $this->dbhm->preExec("INSERT INTO search_history (userid, term, locationid, groups) VALUES (?, ?, ?, ?);", [
            $myid,
            $string,
            $locationid,
            $groups ? implode(',', $groups) : NULL
        ]);

        return($ret);
    }

    public function mailf($fromemail, $toemail, $hdrs, $body) {
        $rc = FALSE;
        $mailf = Mail::factory("mail", "-f " . $fromemail);
        if ($mailf->send($toemail, $hdrs, $body) === TRUE) {
            $rc = TRUE;
        }

        return($rc);
    }

    public function constructSubject($groupid) {
        # Construct the subject - do this now as it may get displayed to the user before we get the membership.
        $g = Group::get($this->dbhr, $this->dbhm, $groupid);
        $keywords = $g->getSetting('keywords', $g->defaultSettings['keywords']);

        $atts = $this->getPublic(FALSE, FALSE, TRUE);
        $items = $this->dbhr->preQuery("SELECT * FROM messages_items INNER JOIN items ON messages_items.itemid = items.id WHERE msgid = ?;", [ $this->id ]);

        if (pres('location', $atts) && count($items) > 0) {
            # Normally we should have an area and postcode to use, but as a fallback we use the area we have.
            if (pres('area', $atts) && pres('postcode', $atts)) {
                $includearea = $g->getSetting('includearea', TRUE);
                if ($includearea) {
                    # We want the area in the group, e.g. Edinburgh EH4.
                    $loc = $atts['area']['name'] . ' ' . $atts['postcode']['name'];
                } else {
                    # We have it, but don't want it, e.g. EH4.
                    $loc = $atts['postcode']['name'];
                }
            } else {
                $l = new Location($this->dbhr, $this->dbhm, $atts['location']['id']);
                $loc = $l->ensureVague();
            }

            $subject = presdef(strtolower($this->type), $keywords, $this->type) . ': ' . $items[0]['name'] . " ($loc)";
            $this->setPrivate('subject', $subject);
        }
    }

    public function queueForMembership(User $fromuser, $groupid) {
        # We would like to submit this message, but we can't do so because we don't have a membership on the Yahoo
        # group yet.  So fire off an application for one; when this gets processed, we will submit the
        # message.
        $ret = NULL;
        $this->setPrivate('fromuser', $fromuser->getId());

        # If this message is already on this group, that's fine.
        $rc = $this->dbhm->preExec("INSERT IGNORE INTO messages_groups (msgid, groupid, collection, arrival, msgtype) VALUES (?,?,?,NOW(),?);", [
            $this->id,
            $groupid,
            MessageCollection::QUEUED_YAHOO_USER,
            $this->getType()
        ]);

        if ($rc) {
            # We've stored the message; send a subscription.
            $ret = $fromuser->triggerYahooApplication($groupid);
        }
        
        return($ret);
    }

    public function addItem($itemid) {
        # Ignore duplicate msgid/itemid.
        $this->dbhm->preExec("INSERT IGNORE INTO messages_items (msgid, itemid) VALUES (?, ?);", [ $this->id, $itemid]);
    }

    public function submit(User $fromuser, $fromemail, $groupid) {
        $rc = FALSE;
        $this->setPrivate('fromuser', $fromuser->getId());

        # Submit a draft or repost a message. Either way, it currently has:
        #
        # - a locationid
        # - a type
        # - an item
        # - a subject
        # - a fromuser
        # - a textbody
        # - zero or more attachments
        #
        # We need to turn this into a full message:
        # - create a Message-ID
        # - other bits and pieces
        # - create a full MIME message
        # - send it
        # - remove it from the drafts table
        # - remove any previous outcomes.
        $atts = $this->getPublic(FALSE, FALSE, TRUE);

        if (pres('location', $atts)) {
            $messageid = $this->id . '@' . USER_DOMAIN;
            $this->setPrivate('messageid', $messageid);

            $this->setPrivate('fromaddr', $fromemail);
            $this->setPrivate('fromaddr', $fromemail);
            $this->setPrivate('fromname', $fromuser->getName());
            $this->setPrivate('lat', $atts['location']['lat']);
            $this->setPrivate('lng', $atts['location']['lng']);

            $g = Group::get($this->dbhr, $this->dbhm, $groupid);
            $this->setPrivate('envelopeto', $g->getGroupEmail());

            $this->dbhm->preExec("DELETE FROM messages_outcomes WHERE msgid = ?;", [ $this->id ]);
            $this->dbhm->preExec("DELETE FROM messages_outcomes_intended WHERE msgid = ?;", [ $this-> id ]);

            # The from IP and country.
            $ip = presdef('REMOTE_ADDR', $_SERVER, NULL);

            if ($ip) {
                $this->setPrivate('fromip', $ip);

                try {
                    $reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
                    $record = $reader->country($ip);
                    $this->setPrivate('fromcountry', $record->country->isoCode);
                } catch (Exception $e) {
                    # Failed to look it up.
                    error_log("Failed to look up $ip " . $e->getMessage());
                }
            }

            $txtbody = $this->textbody;
            $htmlbody = "<p>{$this->textbody}</p>";

            $atts = $this->getAttachments();

            if (count($atts) > 0) {
                # We have attachments.  Include them as image tags.
                $txtbody .= "\r\n\r\nYou can see photos here:\r\n\r\n";
                $htmlbody .= "<p>You can see photos here:</p><table><tbody><tr>";
                $count = 0;

                foreach ($atts as $att) {
                    $path = Attachment::getPath($att->getId());
                    $txtbody .= "$path\r\n";
                    $htmlbody .= '<td><a href="' . $path . '" target="_blank"><img width="200px" src="' . $path . '" /></a></td>';

                    $count++;

                    $htmlbody .= ($count % 3 == 0) ? '</tr><tr>' : '';
                }

                $htmlbody .= "</tr></tbody></table>";
            }

            $htmlbody = str_replace("\r\n", "<br>", $htmlbody);

            $this->setPrivate('textbody', $txtbody);
            $this->setPrivate('htmlbody', $htmlbody);

            # Strip possible group name.
            $subject = $this->subject;
            if (preg_match('/\[.*?\](.*)/', $subject, $matches)) {
                $subject = trim($matches[1]);
            }

            if ($g->getPrivate('onyahoo')) {
                # This group is on Yahoo so we need to send the email there.  Now construct the actual message to send.
                try {
                    list ($transport, $mailer) = getMailer();

                    $message = Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom([$fromemail => $fromuser->getName()])
                        ->setTo([$g->getGroupEmail()])
                        ->setDate(time())
                        ->setId($messageid)
                        ->setBody($txtbody)
                        ->addPart($htmlbody, 'text/html');

                    # We add some headers so that if we receive this back, we can identify it as a mod mail.
                    $headers = $message->getHeaders();
                    $headers->addTextHeader('X-Iznik-MsgId', $this->id);
                    $headers->addTextHeader('X-Iznik-From-User', $fromuser->getId());

                    # Store away the constructed message.
                    $this->setPrivate('message', $message->toString());

                    # Reset the message id we have in the DB to be per-group.  This is so that we recognise it when
                    # it comes back - see save() code above.
                    $this->setPrivate('messageid', "$messageid-$groupid");

                    $mailer->send($message);

                    # This message is now pending.  That means it will show up in ModTools; if it is approved before
                    # it reaches Yahoo and we get notified then we will handle that in submitYahooQueued.
                    $this->dbhm->preExec("UPDATE messages_groups SET senttoyahoo = 1, collection = ? WHERE msgid = ?;", [ MessageCollection::PENDING, $this->id]);

                    $rc = TRUE;
                } catch (Exception $e) {
                    error_log("Send failed with " . $e->getMessage());
                    $rc = FALSE;
                }
            } else {
                # No need to submit by email.
                $rc = TRUE;
            }

            # This message is now not a draft.
            $this->dbhm->preExec("DELETE FROM messages_drafts WHERE msgid = ?;", [ $this->id ]);

            # Record the posting, which is also used in producing the messagehistory.
            $this->dbhm->preExec("INSERT INTO messages_postings (msgid, groupid) VALUES (?,?);", [ $this->id, $groupid ]);
        }

        return($rc);
    }

    public function promise($userid) {
        # Promise this item to a user.
        $sql = "REPLACE INTO messages_promises (msgid, userid) VALUES (?, ?);";
        $this->dbhm->preExec($sql, [
            $this->id,
            $userid
        ]);
    }

    public function renege($userid) {
        # Unromise this item to a user.
        $sql = "DELETE FROM messages_promises WHERE msgid = ? AND userid = ?;";
        $this->dbhm->preExec($sql, [
            $this->id,
            $userid
        ]);
    }

    public function reverseSubject() {
        $subj = $this->getSubject();
        $type = $this->getType();

        # Remove any group tag at the start.
        if (preg_match('/^\[.*?\](.*)/', $subj, $matches)) {
            # Strip possible group name
            $subj = trim($matches[1]);
        }

        # Strip any attachments tag put in by Yahoo
        if (preg_match('/(.*)\[.*? Attachment.*\].*/', $subj, $matches)) {
            # Strip possible group name
            $subj = trim($matches[1]);
        }

        # Strip the relevant keywords.
        $keywords = Message::keywords()[$type];

        foreach ($keywords as $keyword) {
            if (preg_match('/^' . preg_quote($keyword) . '\b(.*)/i', $subj, $matches)) {
                $subj = $matches[1];
            }
            if (preg_match('/ ' . preg_quote($keyword) . '\:(.*)/i', $subj, $matches)) {
                $subj = $matches[1];
            }
        }

        # Now we have to add in the corresponding keyword.  The message should be on at least one group; if the
        # groups have different keywords then there's not much we can do.
        $groups = $this->getGroups();
        $key = strtoupper($type == Message::TYPE_OFFER ? Message::TYPE_TAKEN : Message::TYPE_RECEIVED);
        
        foreach ($groups as $groupid) {
            $g = Group::get($this->dbhr, $this->dbhm, $groupid);
            $defs = $g->getDefaults()['keywords'];
            $keywords = $g->getSetting('keywords', $defs);

            foreach ($keywords as $word => $val) {
                if (strtoupper($word) == $key) {
                    $key = $val;
                }
            }
            break;
        }

        $subj = substr($subj, 0, 1) == ':' ? $subj : ":$subj";
        $subj = $key . $subj;

        return($subj);
    }

    public function intendedOutcome($outcome) {
        $sql = "INSERT INTO messages_outcomes_intended (msgid, outcome) VALUES (?, ?);";
        $this->dbhm->preExec($sql, [
            $this->id,
            $outcome
        ]);
    }

    public function mark($outcome, $comment, $happiness, $userid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $this->dbhm->preExec("DELETE FROM messages_outcomes_intended WHERE msgid = ?;", [ $this-> id ]);
        $this->dbhm->preExec("INSERT INTO messages_outcomes (msgid, outcome, happiness, userid, comments) VALUES (?,?,?,?,?);", [
            $this->id,
            $outcome,
            $happiness,
            $userid,
            $comment
        ]);

        $this->log->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => Log::SUBTYPE_OUTCOME,
            'msgid' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => "$outcome $comment"
        ]);

        # This message may be on one or more Yahoo groups; if so we need to send a TAKEN.
        $subj = $this->reverseSubject();
        $u = User::get($this->dbhr, $this->dbhm, $this->fromuser);

        $groups = $this->getGroups();

        foreach ($groups as $groupid) {
            $g = Group::get($this->dbhr, $this->dbhm, $groupid);

            # Update the arrival time.  This is so that if anyone (TN, I'm looking at you) is using the API to retrieve
            # messages, it can tell that the message has had an outcome.
            $this->dbhm->preExec("UPDATE messages_groups SET arrival = NOW() WHERE msgid = ? AND groupid = ?;", [
                $this->id,
                $groupid
            ]);

            if ($g->getPrivate('onyahoo')) {
                # For Yahoo, we send a TAKEN/RECEIVED message.  Not for native.
                list ($eid, $email) = $u->getEmailForYahooGroup($groupid, TRUE, TRUE);
                $this->mailer(
                    $u,
                    FALSE,
                    $g->getGroupEmail(),
                    $g->getGroupEmail(),
                    NULL,
                    $u->getName(),
                    $email,
                    $subj,
                    ($happiness == User::HAPPY || User::FINE) ? $comment : ''
                );
            }
        }

        # Let anyone who was interested, and who didn't get it, know.
        $userq = $userid ? " AND userid != $userid " : "";
        $sql = "SELECT DISTINCT t.* FROM (SELECT chatid FROM chat_messages INNER JOIN chat_rooms ON chat_rooms.id = chat_messages.chatid AND chat_rooms.chattype = ? WHERE refmsgid = ? AND reviewrejected = 0 $userq AND userid IS NOT NULL GROUP BY userid, chatid) t;";
        $replies = $this->dbhr->preQuery($sql, [ ChatRoom::TYPE_USER2USER, $this->id ]);
        $cm = new ChatMessage($this->dbhr, $this->dbhm);

        foreach ($replies as $reply) {
            $cm->create($reply['chatid'], $this->getFromuser(), NULL, ChatMessage::TYPE_COMPLETED, $this->id);

            # Make sure this message is highlighted in chat/email.
            $r = new ChatRoom($this->dbhr, $this->dbhm, $reply['chatid']);
            $r->upToDate($this->getFromuser());
        }
    }

    public function withdraw($comment, $happiness) {
        $this->dbhm->preExec("DELETE FROM messages_outcomes_intended WHERE msgid = ?;", [ $this-> id ]);

        $this->dbhm->preExec("INSERT INTO messages_outcomes (msgid, outcome, happiness, comments) VALUES (?,?,?,?);", [
            $this->id,
            Message::OUTCOME_WITHDRAWN,
            $happiness,
            $comment
        ]);

        $me = whoAmI($this->dbhr, $this->dbhm);

        $this->log->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => Log::SUBTYPE_OUTCOME,
            'msgid' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => "Withdrawn: $comment"
        ]);
    }

    public function backToDraft() {
        # Convert a message back to a draft.
        $rollback = FALSE;
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        if ($this->dbhm->beginTransaction()) {
            $rollback = TRUE;

            if ($this->id) {
                # This might already be a draft, so ignore dups.
                $rc = $this->dbhm->preExec("INSERT IGNORE INTO messages_drafts (msgid, userid, session) VALUES (?, ?, ?);", [ $this->id, $myid, session_id() ]);

                if ($rc) {
                    $rc = $this->dbhm->preExec("DELETE FROM messages_groups WHERE msgid = ?;", [ $this->id ]);

                    if ($rc) {
                        $rc = $this->dbhm->commit();

                        if ($rc) {
                            $rollback = FALSE;
                        }
                    }
                }
            }
        }

        if ($rollback) {
            $this->dbhm->rollBack();
        }

        return(!$rollback);
    }

    public function autoRepostGroup($type, $mindate, $groupid = NULL) {
        $count = 0;
        $warncount = 0;
        $groupq = $groupid ? " AND id = $groupid " : "";

        # Randomise the order to give all groups a chance if the script gets killed or something.
        $groups = $this->dbhr->preQuery("SELECT id FROM groups WHERE type = ? $groupq ORDER BY RAND();", [ $type ]);

        foreach ($groups as $group) {
            $g = Group::get($this->dbhr, $this->dbhm, $group['id']);
            $reposts = $g->getSetting('reposts', [ 'offer' => 2, 'wanted' => 14, 'max' => 10, 'chaseups' => 2]);

            # We want approved messages which haven't got an outcome, i.e. aren't TAKEN/RECEIVED, which don't have
            # some other outcome (e.g. withdrawn), aren't promised, don't have any replies and which we originally sent.
            #
            # The replies part is because we can't really rely on members to let us know what happens to a message,
            # especially if they are not receiving emails reliably.  At least this way it avoids the case where a
            # message gets resent repeatedly and people keep replying and not getting a response.
            #
            # The sending user must also still be a member of the group.
            $sql = "SELECT messages_groups.msgid, messages_groups.groupid, TIMESTAMPDIFF(HOUR, messages_groups.arrival, NOW()) AS hoursago, autoreposts, lastautopostwarning, messages.type, messages.fromaddr FROM messages_groups INNER JOIN messages ON messages.id = messages_groups.msgid INNER JOIN memberships ON memberships.userid = messages.fromuser AND memberships.groupid = messages_groups.groupid LEFT OUTER JOIN messages_related ON id1 = messages.id OR id2 = messages.id LEFT OUTER JOIN messages_outcomes ON messages.id = messages_outcomes.msgid LEFT OUTER JOIN messages_promises ON messages_promises.msgid = messages.id LEFT OUTER JOIN chat_messages ON messages.id = chat_messages.refmsgid WHERE messages_groups.arrival > ? AND messages_groups.groupid = ? AND messages_groups.collection = 'Approved' AND messages_related.id1 IS NULL AND messages_outcomes.msgid IS NULL AND messages_promises.msgid IS NULL AND messages.type IN ('Offer', 'Wanted') AND sourceheader IN ('Platform', 'FDv2') AND messages.deleted IS NULL AND chat_messages.refmsgid IS NULL;";
            #error_log("$sql, $mindate, {$group['id']}");
            $messages = $this->dbhr->preQuery($sql, [
                $mindate,
                $group['id']
            ]);

            $now = time();

            foreach ($messages as $message) {
                if (ourDomain($message['fromaddr'])) {
                    if ($message['autoreposts'] < $reposts['max']) {
                        # We want to send a warning 24 hours before we repost.
                        $lastwarnago = $message['lastautopostwarning'] ? (strtotime($message['lastautopostwarning']) - $now) : NULL;
                        $interval = $message['type'] == Message::TYPE_OFFER ? $reposts['offer'] : $reposts['wanted'];

                        #error_log("Consider repost {$message['msgid']}, posted {$message['hoursago']} interval $interval lastwarning $lastwarnago");

                        # Reposts might be turned off.
                        if ($interval > 0) {
                            if ($message['hoursago'] <= $interval * 24 &&
                                $message['hoursago'] > ($interval - 1) * 24 &&
                                ($lastwarnago === NULL || $lastwarnago > 24)
                            ) {
                                    # We will be reposting within 24 hours, and we've either not sent a warning, or the last one was
                                    # an old one (probably from the previous repost).
                                    if (!$message['lastautopostwarning'] || ($lastwarnago > 24 * 60 * 60)) {
                                        # And we haven't sent a warning yet.
                                        $this->dbhm->preExec("UPDATE messages_groups SET lastautopostwarning = NOW() WHERE msgid = ?;", [$message['msgid']]);
                                        $warncount++;

                                        $m = new Message($this->dbhr, $this->dbhm, $message['msgid']);
                                        $u = new User($this->dbhr, $this->dbhm, $m->getFromuser());
                                        $g = new Group($this->dbhr, $this->dbhm, $message['groupid']);
                                        $gatts = $g->getPublic();

                                        if ($u->getId()) {
                                            $to = $u->getEmailPreferred();
                                            $subj = $m->getSubject();

                                            # Remove any group tag.
                                            $subj = trim(preg_replace('/^\[.*?\](.*)/', "$1", $subj));

                                            $completed = $u->loginLink(USER_SITE, $u->getId(), "/mypost/{$message['msgid']}/completed", User::SRC_REPOST_WARNING);
                                            $withdraw = $u->loginLink(USER_SITE, $u->getId(), "/mypost/{$message['msgid']}/withdraw", User::SRC_REPOST_WARNING);
                                            $othertype = $m->getType() == Message::TYPE_OFFER ? Message::OUTCOME_TAKEN : Message::OUTCOME_RECEIVED;
                                            $text = "We will automatically repost your message $subj soon, so that more people will see it.  If you don't want us to do that, please go to $completed to mark as $othertype or $withdraw to withdraw it.";
                                            $html = autorepost_warning(USER_SITE,
                                                USERLOGO,
                                                $subj,
                                                $u->getName(),
                                                $to,
                                                $othertype,
                                                $completed,
                                                $withdraw
                                            );

                                            list ($transport, $mailer) = getMailer();

                                            if (Swift_Validate::email($to)) {
                                                $message = Swift_Message::newInstance()
                                                    ->setSubject("Re: " . $subj)
                                                    ->setFrom([$g->getAutoEmail() => $gatts['namedisplay']])
                                                    ->setReplyTo([$g->getModsEmail() => $gatts['namedisplay']])
                                                    ->setTo($to)
                                                    ->setBody($text)
                                                    ->addPart($html, 'text/html');
                                                $mailer->send($message);
                                            }
                                        }
                                    }
                            } else if ($message['hoursago'] > $interval * 24) {
                                # We can autorepost this one.
                                $m = new Message($this->dbhr, $this->dbhm, $message['msgid']);
                                error_log($g->getPrivate('nameshort') . " #{$message['msgid']} " . $m->getFromaddr() . " " . $m->getSubject() . " repost due");
                                $m->autoRepost($message['autoreposts'] + 1, $reposts['max']);

                                $count++;
                            }
                        }
                    }
                }
            }
        }

        return([$count, $warncount]);
    }

    public function chaseUp($type, $mindate, $groupid = NULL) {
        $count = 0;
        $groupq = $groupid ? " AND id = $groupid " : "";

        # Randomise the order in case the script gets killed or something - gives all groups a chance.
        $groups = $this->dbhr->preQuery("SELECT id FROM groups WHERE type = ? $groupq ORDER BY RAND();", [ $type ]);

        foreach ($groups as $group) {
            $g = Group::get($this->dbhr, $this->dbhm, $group['id']);
            $reposts = $g->getSetting('reposts', [ 'offer' => 2, 'wanted' => 14, 'max' => 10, 'chaseups' => 2]);

            # We want approved messages which haven't got an outcome, i.e. aren't TAKEN/RECEIVED, which don't have
            # some other outcome (e.g. withdrawn), aren't promised, have any replies and which we originally sent.
            #
            # The sending user must also still be a member of the group.
            $sql = "SELECT messages_groups.msgid, messages_groups.groupid, TIMESTAMPDIFF(HOUR, messages_groups.arrival, NOW()) AS hoursago, lastchaseup, messages.type, messages.fromaddr FROM messages_groups INNER JOIN messages ON messages.id = messages_groups.msgid INNER JOIN memberships ON memberships.userid = messages.fromuser AND memberships.groupid = messages_groups.groupid LEFT OUTER JOIN messages_related ON id1 = messages.id OR id2 = messages.id LEFT OUTER JOIN messages_outcomes ON messages.id = messages_outcomes.msgid LEFT OUTER JOIN messages_promises ON messages_promises.msgid = messages.id INNER JOIN chat_messages ON messages.id = chat_messages.refmsgid WHERE messages_groups.arrival > ? AND messages_groups.groupid = ? AND messages_groups.collection = 'Approved' AND messages_related.id1 IS NULL AND messages_outcomes.msgid IS NULL AND messages_promises.msgid IS NULL AND messages.type IN ('Offer', 'Wanted') AND sourceheader IN ('Platform', 'FDv2') AND messages.deleted IS NULL;";
            #error_log("$sql, $mindate, {$group['id']}");
            $messages = $this->dbhr->preQuery($sql, [
                $mindate,
                $group['id']
            ]);

            $now = time();

            foreach ($messages as $message) {
                if (ourDomain($message['fromaddr'])) {
                    # Find the last reply.
                    $m = new Message($this->dbhr, $this->dbhm, $message['msgid']);

                    if ($m->canChaseup()) {
                        $sql = "SELECT MAX(date) AS latest FROM chat_messages WHERE chatid IN (SELECT chatid FROM chat_messages WHERE refmsgid = ?);";
                        $replies = $this->dbhr->preQuery($sql, [ $message['msgid'] ]);
                        $lastreply = $replies[0]['latest'];
                        $age = ($now - strtotime($lastreply)) / (60 * 60);
                        $interval = array_key_exists('chaseups', $reposts) ? $reposts['chaseups'] : 2;

                        if ($interval > 0 && $age > $interval * 24) {
                            # We can chase up.
                            $u = new User($this->dbhr, $this->dbhm, $m->getFromuser());
                            $g = new Group($this->dbhr, $this->dbhm, $message['groupid']);
                            $gatts = $g->getPublic();

                            if ($u->getId() && $m->canRepost()) {
                                error_log($g->getPrivate('nameshort') . " #{$message['msgid']} " . $m->getFromaddr() . " " . $m->getSubject() . " chaseup due");
                                $count++;
                                $this->dbhm->preExec("UPDATE messages_groups SET lastchaseup = NOW() WHERE msgid = ?;", [$message['msgid']]);

                                $to = $u->getEmailPreferred();
                                $subj = $m->getSubject();

                                # Remove any group tag.
                                $subj = trim(preg_replace('/^\[.*?\](.*)/', "$1", $subj));

                                $completed = $u->loginLink(USER_SITE, $u->getId(), "/mypost/{$message['msgid']}/completed", User::SRC_CHASEUP);
                                $withdraw = $u->loginLink(USER_SITE, $u->getId(), "/mypost/{$message['msgid']}/withdraw", User::SRC_CHASEUP);
                                $repost = $u->loginLink(USER_SITE, $u->getId(), "/mypost/{$message['msgid']}/repost", User::SRC_CHASEUP);
                                $othertype = $m->getType() == Message::TYPE_OFFER ? Message::OUTCOME_TAKEN : Message::OUTCOME_RECEIVED;
                                $text = "Can you let us know what happened with this?  Click $repost to post it again, or $completed to mark as $othertype, or $withdraw to withdraw it.  Thanks.";
                                $html = chaseup(USER_SITE,
                                    USERLOGO,
                                    $subj,
                                    $u->getName(),
                                    $to,
                                    $othertype,
                                    $repost,
                                    $completed,
                                    $withdraw
                                );

                                list ($transport, $mailer) = getMailer();

                                if (Swift_Validate::email($to)) {
                                    $message = Swift_Message::newInstance()
                                        ->setSubject("Re: " . $subj)
                                        ->setFrom([$g->getAutoEmail() => $gatts['namedisplay']])
                                        ->setReplyTo([$g->getModsEmail() => $gatts['namedisplay']])
                                        ->setTo($to)
                                        ->setBody($text)
                                        ->addPart($html, 'text/html');
                                    $mailer->send($message);
                                }
                            }
                        }
                    }
                }
            }
        }

        return($count);
    }

    public function processIntendedOutcomes($msgid = NULL) {
        $count = 0;

        # If someone responded to a chaseup mail, but didn't complete the process in half an hour, we do it for them.
        #
        # This is quite common, and helps get more activity even from members who are put to shame by goldfish.
        $msgq = $msgid ? " AND msgid = $msgid " : "";
        $intendeds = $this->dbhr->preQuery("SELECT * FROM messages_outcomes_intended WHERE TIMESTAMPDIFF(MINUTE, timestamp, NOW()) > 30 $msgq;");
        foreach ($intendeds as $intended) {
            $m = new Message($this->dbhr, $this->dbhm, $intended['msgid']);

            switch ($intended['outcome']) {
                case 'Taken':
                    $m->mark(Message::OUTCOME_TAKEN, NULL, NULL, NULL);
                    break;
                case 'Received':
                    $m->mark(Message::OUTCOME_RECEIVED, NULL, NULL, NULL);
                    break;
                case 'Withdrawn':
                    $m->withdraw(NULL, NULL);
                    break;
                case 'Repost':
                    $m->repost();
                    break;
            }

            $count++;
        }

        return($count);
    }

    public function canRepost() {
        $ret = FALSE;
        $groups = $this->dbhr->preQuery("SELECT groupid, TIMESTAMPDIFF(HOUR, arrival, NOW()) AS hoursago FROM messages_groups WHERE msgid = ?;", [ $this->id ]);

        foreach ($groups as $group) {
            $g = Group::get($this->dbhr, $this->dbhm, $group['groupid']);
            $reposts = $g->getSetting('reposts', ['offer' => 2, 'wanted' => 14, 'max' => 10, 'chaseups' => 2]);
            $interval = $this->getType() == Message::TYPE_OFFER ? $reposts['offer'] : $reposts['wanted'];

            if ($group['hoursago'] > $interval * 24) {
                $ret = TRUE;
            }
        }

        return($ret);
    }

    public function canChaseup() {
        $ret = FALSE;
        $groups = $this->dbhr->preQuery("SELECT groupid, lastchaseup FROM messages_groups WHERE msgid = ?;", [ $this->id ]);

        foreach ($groups as $group) {
            $g = Group::get($this->dbhr, $this->dbhm, $group['groupid']);
            $reposts = $g->getSetting('reposts', ['offer' => 2, 'wanted' => 14, 'max' => 10, 'chaseups' => 2]);
            $interval = $this->getType() == Message::TYPE_OFFER ? $reposts['offer'] : $reposts['wanted'];
            $interval = max($interval, (array_key_exists('chaseups', $reposts) ? $reposts['chaseups'] : 2) * 24);

            $ret = TRUE;

            if ($group['lastchaseup']) {
                $age = (time() - strtotime($group['lastchaseup'])) / (60 * 60);
                $ret = $age > $interval * 24;
            }
        }

        return($ret);
    }

    public function repost() {
        # Make sure we don't keep doing this.
        $this->dbhm->preExec("DELETE FROM messages_outcomes_intended WHERE msgid = ?;", [ $this-> id ]);

        # All we need to do to repost is update the arrival time - that will cause the message to appear on the site
        # near the top, and get mailed out again.
        $this->dbhm->preExec("UPDATE messages_groups SET arrival = NOW() WHERE msgid = ?;", [ $this->id ]);

        # ...and update the search index.
        $this->s->bump($this->id, time());

        # Record that we've done this.
        $groups = $this->getGroups();
        foreach ($groups as $groupid) {
            $sql = "INSERT INTO messages_postings (msgid, groupid, repost, autorepost) VALUES(?,?,?,?);";
            $this->dbhm->preExec($sql, [
                $this->id,
                $groupid,
                1,
                0
            ]);
        }
    }

    public function autoRepost($reposts, $max) {
        # All we need to do to repost is update the arrival time - that will cause the message to appear on the site
        # near the top, and get mailed out again.
        #
        # Don't resend to Yahoo - the complexities of trying to keep the single message we have in sync
        # with multiple copies on Yahoo are just too horrible to be worth trying to do.
        $this->dbhm->preExec("UPDATE messages_groups SET arrival = NOW(), autoreposts = autoreposts + 1 WHERE msgid = ?;", [ $this->id ]);

        # ...and update the search index.
        $this->s->bump($this->id, time());

        $this->log->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => Log::SUBTYPE_AUTO_REPOSTED,
            'msgid' => $this->id,
            'user' => $this->getFromuser(),
            'text' => "$reposts / $max"
        ]);

        # Record that we've done this.
        $groups = $this->getGroups();
        foreach ($groups as $groupid) {
            $sql = "INSERT INTO messages_postings (msgid, groupid, repost, autorepost) VALUES(?,?,?,?);";
            $this->dbhm->preExec($sql, [
                $this->id,
                $groupid,
                1,
                0
            ]);
        }
    }

    public function isBounce()
    {
        $bounce = FALSE;

        foreach ($this->bounce_subjects as $subj) {
            if (stripos($this->subject, $subj) !== FALSE) {
                $bounce = TRUE;
            }
        }

        if (!$bounce) {
            foreach ($this->bounce_bodies as $body) {
                if (stripos($this->message, $body) !== FALSE) {
                    $bounce = TRUE;
                }
            }
        }

        return ($bounce);
    }
    
    public function isAutoreply()
    {
        $autoreply = FALSE;

        foreach ($this->autoreply_subjects as $subj) {
            if (stripos($this->subject, $subj) !== FALSE) {
                $autoreply = TRUE;
            }
        }

        if (!$autoreply) {
            foreach ($this->autoreply_bodies as $body) {
                if (stripos($this->message, $body) !== FALSE) {
                    $autoreply = TRUE;
                }
            }
        }

        return ($autoreply);
    }
}