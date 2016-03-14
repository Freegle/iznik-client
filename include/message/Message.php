<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/misc/plugin.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/include/misc/Search.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');

class Message
{
    const TYPE_OFFER = 'Offer';
    const TYPE_TAKEN = 'Taken';
    const TYPE_WANTED = 'Wanted';
    const TYPE_RECEIVED = 'Received';
    const TYPE_ADMIN = 'Admin';
    const TYPE_OTHER = 'Other';

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
        $sql = "UPDATE messages_groups SET yahoopendingid = ? WHERE msgid = {$this->id} AND groupid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $id, $groupid ]);
        $this->yahoopendingid = $id;
    }

    public function setYahooApprovedId($groupid, $id) {
        $sql = "UPDATE messages_groups SET yahooapprovedid = ? WHERE msgid = {$this->id} AND groupid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $id, $groupid ]);
        $this->yahooapprovedid = $id;
    }

    public function setPrivate($att, $val) {
        if ($this->$att != $val) {
            $rc = $this->dbhm->preExec("UPDATE messages SET $att = ? WHERE id = {$this->id};", [$val]);
            if ($rc) {
                $this->$att = $val;
            }
        }
    }

    public function getPrivate($att) {
        return($this->$att);
    }

    public function edit($subject, $textbody, $htmlbody) {
        if ($htmlbody && !$textbody) {
            # In the interests of accessibility, let's create a text version of the HTML
            $html = new \Html2Text\Html2Text($htmlbody);
            $textbody = $html->getText();
        }

        $me = whoAmI($this->dbhr, $this->dbhm);
        $text = ($subject ? "New subject $subject " : '');
        $text .= $textbody ? "Text body changed " : '';
        $text .= $htmlbody ? "HTML body changed " : '';

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
            $me->getId(),
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
        $s, $editedby, $editedat;

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
        'id', 'subject', 'suggestedsubject', 'type', 'arrival', 'date', 'deleted', 'heldby', 'lat', 'lng', 'locationid'
    ];

    public $memberAtts = [
        'textbody', 'htmlbody', 'fromname', 'fromuser'
    ];

    public $moderatorAtts = [
        'source', 'sourceheader', 'fromaddr', 'envelopeto', 'envelopefrom', 'messageid', 'tnpostid',
        'fromip', 'fromcountry', 'message', 'spamreason', 'spamtype', 'replyto', 'editedby', 'editedat'
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
            $msgs = $dbhr->preQuery("SELECT * FROM messages WHERE id = ?;", [$id]);
            foreach ($msgs as $msg) {
                $this->id = $id;

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

        $this->s = new Search($dbhr, $dbhm, 'messages_index', 'msgid', 'arrival', 'words', 'groupid');
    }

    /**
     * @param Search $search
     */
    public function setSearch($search)
    {
        $this->s = $search;
    }

    # Default mailer is to use the standard PHP one, but this can be overridden in UT.
    private function mailer() {
        call_user_func_array('mail', func_get_args());
    }

    public function getRoleForMessage($overrides = TRUE) {
        # Our role for a message is the highest role we have on any group that this message is on.  That means that
        # we have limited access to information on other groups of which we are not a moderator, but that is legitimate
        # if the message is on our group.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $role = User::ROLE_NONMEMBER;

        if ($me) {
            $sql = "SELECT role FROM memberships
              INNER JOIN messages_groups ON messages_groups.msgid = ?
                  AND messages_groups.groupid = memberships.groupid
                  AND userid = ?;";
            $groups = $this->dbhr->preQuery($sql, [
                $this->id,
                $me->getId()
            ]);

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

        return($role);
    }

    public function getPublic($messagehistory = TRUE, $related = TRUE) {
        $ret = [];
        $role = $this->getRoleForMessage();

        foreach ($this->nonMemberAtts as $att) {
            $ret[$att] = $this->$att;
        }

        if ($role == User::ROLE_MEMBER || $role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
            foreach ($this->memberAtts as $att) {
                $ret[$att] = $this->$att;
            }
        }

        if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
            foreach ($this->moderatorAtts as $att) {
                $ret[$att] = $this->$att;
            }
        }

        if ($role == User::ROLE_OWNER) {
            foreach ($this->ownerAtts as $att) {
                $ret[$att] = $this->$att;
            }
        }

        # Remove any group subject tag.
        $ret['subject'] = preg_replace('/\[.*?\]\s*/', '', $ret['subject']);
        $ret['subject'] = preg_replace('/\[.*Attachment.*\]\s*/', '', $ret['subject']);

        # Add any groups that this message is on.
        $ret['groups'] = [];
        $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND deleted = 0;";
        $ret['groups'] = $this->dbhr->preQuery($sql, [ $this->id ] );

        foreach ($ret['groups'] as &$group) {
            $ret['lat'] = $this->lat;
            $ret['lng'] = $this->lng;
            $ret['locationid'] = $this->locationid;

            $group['arrival'] = ISODate($group['arrival']);

            if (pres('approvedby', $group)) {
                $u = new User($this->dbhr, $this->dbhm, $group['approvedby']);
                $ctx = NULL;
                $group['approvedby'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE);
            }
        }

        # Add derived attributes.
        $ret['arrival'] = ISODate($ret['arrival']);
        $ret['date'] = ISODate($ret['date']);
        $ret['daysago'] = floor((time() - strtotime($ret['date'])) / 86400);

        if ($ret['locationid']) {
            $l = new Location($this->dbhr, $this->dbhm, $ret['locationid']);
            $ret['location'] = $l->getPublic();
        }

        if (pres('fromcountry', $ret)) {
            $ret['fromcountry'] = code_to_country($ret['fromcountry']);
        }

        if (pres('fromuser', $ret)) {
            $u = new User($this->dbhr, $this->dbhm, $ret['fromuser']);

            # Get the user details, relative to the groups this message appears on.
            $ret['fromuser'] = $u->getPublic($this->getGroups(), $messagehistory, FALSE);

            if ($role == User::ROLE_OWNER || $role == User::ROLE_MODERATOR) {
                # We can see their emails.
                $ret['fromuser']['emails'] = $u->getEmails();
            }

            filterResult($ret['fromuser']);
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
            $u = new User($this->dbhr, $this->dbhm, $ret['heldby']);
            $ret['heldby'] = $u->getPublic();
            filterResult($ret['heldby']);
        }

        # Add any attachments - visible to non-members.
        $ret['attachments'] = [];
        $atts = $this->getAttachments();

        foreach ($atts as $att) {
            /** @var $att Attachment */
            $ret['attachments'][] = $att->getPublic();
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
        $this->fromip = $fromip;
        $name = NULL;

        if ($fromip) {
            # If the call returns a hostname which is the same as the IP, then it's
            # not resolvable.
            $name = gethostbyaddr($fromip);
            $name = ($name == $fromip) ? NULL : $name;
            $this->fromhost = $name;
        }

        $this->dbhm->preExec("UPDATE messages SET fromip = ? WHERE id = ?;",
            [$fromip, $this->id]);
        $this->dbhm->preExec("UPDATE messages_history SET fromip = ?, fromhost = ? WHERE msgid = ?;",
            [$fromip, $name, $this->id]);
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
        $atts = Attachment::getById($this->dbhr, $this->dbhm, $this->getID());
        return($atts);
    }

    private function keywords() {
        # We try various mis-spellings, and Welsh.  This is not to suggest that Welsh is a spelling error.
        return([
            Message::TYPE_OFFER => [
                'ofer', 'offr', 'offrer', 'ffered', 'offfered', 'offrered', 'offered', 'offeer', 'cynnig', 'offred',
                'offer', 'offering', 'reoffer', 're offer', 're-offer', 'reoffered', 're offered', 're-offered',
                'offfer', 'offeed', 'available'],
            Message::TYPE_TAKEN => ['collected', 'take', 'stc', 'gone', 'withdrawn', 'ta ke n', 'promised',
                'cymeryd', 'cymerwyd', 'takln', 'taken'],
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
        $subj = @iconv('UTF-8', 'UTF-8//IGNORE', $subj);

        return($subj);
    }

    public function createDraft() {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;
        $sess = session_id();

        $rc = $this->dbhm->preExec("INSERT INTO messages VALUES();");
        $id = $rc ? $this->dbhm->lastInsertId() : NULL;

        if ($id) {
            $rc = $this->dbhm->preExec("INSERT INTO messages_drafts (msgid, user, session) VALUES (?, ?, ?);", [ $rc, $myid, $sess ]);
            $id = $rc ? $id : NULL;
        }

        return($id);
    }

    # Parse a raw SMTP message.
    public function parse($source, $envelopefrom, $envelopeto, $msg, $groupid = NULL)
    {
        $this->message = $msg;
        $this->groupid = $groupid;

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
            if (count($atts) >= 1 && $atts[0]->contentType == 'message/rfc822') {
                $attachedmsg = $atts[0]->getContent();
                $Parser->setText($attachedmsg);
                $this->attach_files = $Parser->saveAttachments($this->attach_dir);
                $this->attachments = $Parser->getAttachments();
            }
        }

        if (count($this->attachments) == 0) {
            # No attachments - tidy up temp dir.
            rrmdir($this->attach_dir);
        }

        $this->source = $source;
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

        # Get IP
        $ip = $this->getHeader('x-freegle-ip');
        $ip = $ip ? $ip : $this->getHeader('x-trash-nothing-user-ip');
        $ip = $ip ? $ip : $this->getHeader('x-yahoo-post-ip');
        $ip = $ip ? $ip : $this->getHeader('x-originating-ip');
        $ip = preg_replace('/[\[\]]/', '', $ip);
        $this->fromip = $ip;

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
            return(FALSE);
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
                $this->sourceheader = 'FDv2';
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

        # The HTML body might contain images as img tags, rather than actual attachments.  Extract these too.
        $doc = new DOMDocument();
        @$doc->loadHTML($this->htmlbody);
        $imgs = $doc->getElementsByTagName('img');

        foreach ($imgs as $img) {
            $src = $img->getAttribute('src');

            # We only want to get images from http or https to avoid the security risk of fetching a local file.
            #
            # Wait for 60 seconds to fetch.  We don't want to wait forever, but we see occasional timeouts from Yahoo
            # at 30 seconds.
            if (stripos($src, 'http://') === 0 || stripos($src, 'https://') === 0) {
                $ctx = stream_context_create(array('http'=>
                    array(
                        'timeout' => 60
                    )
                ));
                $data = file_get_contents($src, false, $ctx);

                # Try to convert to an image.  If it's not an image, this will fail.
                $img = new Image($data);
                $newdata = $img->getData(100);

                # Ignore small images - Yahoo adds small ones as (presumably) a tracking mechanism, and also their
                # logo.
                if ($newdata && $img->width() > 50 && $img->height() > 50) {
                    $this->inlineimgs[] = $newdata;
                }
            }
        }

        # See if we can find a group this is intended for.  Can't trust the To header, as the client adds it,
        # and we might also be CC'd or BCC'd.
        $groupname = NULL;
        $to = $this->getApparentlyTo();

        if (count($to) == 0) {
            # ...but if we can't find it, it'll do.
            $to = $this->getTo();
        }

        foreach ($to as $t) {
            if (preg_match('/(.*)@yahoogroups\.co.*/', $t['address'], $matches)) {
                $groupname = $matches[1];
            }
        }

        if ($groupname) {
            if (!$this->groupid) {
                # Check if it's a group we host.
                $g = new Group($this->dbhr, $this->dbhm);
                $this->groupid = $g->findByShortName($groupname);
            }

            if ($this->groupid) {
                # If this is a reuse group, we need to determine the type.
                $g = new Group($this->dbhr, $this->dbhm, $this->groupid);
                if ($g->getPrivate('type') == Group::GROUP_FREEGLE ||
                    $g->getPrivate('type') == Group::GROUP_REUSE
                ) {
                    $this->type = $this->determineType($this->subject);
                } else {
                    $this->type = Message::TYPE_OTHER;
                }

                if ($source == Message::YAHOO_PENDING || $source == Message::YAHOO_APPROVED) {
                    # Make sure we have a user and a membership for the originator of this message; they were a member
                    # at the time they sent this.  If they have since left we'll pick that up later via a sync.
                    $u = new User($this->dbhr, $this->dbhm);
                    $userid = $u->findByEmail($this->fromaddr);

                    if (!$userid) {
                        # We don't know them.  Add.
                        #
                        # We don't have a first and last name, so use what we have. If the friendly name is set to an
                        # email address, take the first part.
                        $name = $this->fromname;
                        if (preg_match('/(.*)@/', $name, $matches)) {
                            $name = $matches[1];
                        }

                        if ($userid = $u->create(NULL, NULL, $name, "Incoming message from {$this->fromaddr} on $groupname")) {
                            # If any of these fail, then we'll pick it up later when we do a sync with the source group,
                            # so no need for a transaction.
                            $u = new User($this->dbhr, $this->dbhm, $userid);
                            $emailid = $u->addEmail($this->fromaddr, TRUE);
                            $u->addMembership($this->groupid, User::ROLE_MEMBER, $emailid);
                        }
                    }

                    # Now we have a user.  If there is a Yahoo uid in here - which there isn't always - add it to the
                    # user entry.
                    $gp = $Parser->getHeader('x-yahoo-group-post');
                    if ($gp && preg_match('/u=(.*);/', $gp, $matches)) {
                        // This is Yahoo's unique identifier for this user.
                        $u = new User($this->dbhr, $this->dbhm, $userid);

                        if ($u->getPrivate('yahooUserId') != $matches[1]) {
                            # Check if there is a different user with this id already.
                            $otherid = $u->findByYahooUserId($matches[1]);
                            if ($otherid && $otherid !== $userid) {
                                # Yes there is - merge.
                                $u->merge($userid, $otherid, "Incoming Message - YahooUserId {$matches[1]} = $otherid, Email {$this->fromaddr} = $userid");
                            } else {
                                # No there's not - just update.
                                $u->setPrivate('yahooUserId', $matches[1]);
                            }
                        }
                    }

                    $this->fromuser = $userid;
                }
            }
        }

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

        # Might have wrong LF format.
        $current = preg_replace('~\R~u', "\r\n", $current);
        $p = 0;

        do {
            $found = FALSE;
            $p = stripos($current, 'Content-Type:', $p);
            #error_log("Found content type at $p");

            if ($p) {
                $crpos = strpos($current, "\r\n", $p);
                $ct = substr($current, $p, $crpos - $p);
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

                        # Keep a max of 10K.
                        #
                        # Observant readers may wish to comment on this definition of K.
                        #error_log("breakpos $breakpos nextboundpos $nextboundpos size " . ($nextboundpos - $breakpos));
                        if ($breakpos && $nextboundpos && $nextboundpos - $breakpos > 10000) {
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

        return($current);
    }

    # Save a parsed message to the DB
    public function save() {
        # A message we are saving as approved may previously have been in the system, for example as pending.  When it
        # comes back to us, it might not be the same, so we should remove any old one first.
        #
        # This can happen if a message is handled on another system, e.g. moderated directly on Yahoo.
        #
        # We don't need a transaction for this - transactions aren't great for scalability and worst case we
        # leave a spurious message around which a mod will handle.
        #
        # But we do want to preserve any information we had about who approved a message.
        $sql = "SELECT approvedby FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid WHERE messageid = ? AND fromaddr = ?;";
        $messages = $this->dbhr->preQuery($sql,  [
            $this->getMessageID(),
            $this->getFromaddr()
        ]);

        $approvedby = NULL;

        foreach ($messages as $message) {
            $approvedby = $message['approvedby'];
        }

        if (!$approvedby) {
            # See if we have a record of approval from Yahoo.
            $approval = $this->getHeader('x-egroups-approved-by');

            if ($approval && preg_match('/(.*) via/', $approval, $matches)) {
                # We've got an approval.  See if we can find the mod.
                $by = $matches[1];
                $u = new User($this->dbhr, $this->dbhm);
                $idid = $u->findByEmail($by);
                $approvedby =  $idid ? $idid : $u->findByEmail($by);
            }
        }

        # Now we can zap the old copy.
        $this->removeByMessageID($this->groupid);

        # Reduce the size of the message source
        $this->message = $this->pruneMessage();

        # Trigger mapping and get subject suggestion.
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

        $id = NULL;

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            $this->id = $id;

            $l = new Log($this->dbhr, $this->dbhm);
            $l->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_RECEIVED,
                'msgid' => $id,
                'user' => $this->fromuser,
                'text' => $this->messageid,
                'groupid' => $this->groupid
            ]);

            # Now that we have a ID, record which messages are related to this one.
            $this->recordRelated();
        }

        if ($this->groupid) {
            # Save the group we're on.  If we crash or fail at this point we leave the message stranded, which is ok
            # given the perf cost of a transaction.
            $this->dbhm->preExec("INSERT INTO messages_groups (msgid, groupid, yahoopendingid, yahooapprovedid, yahooreject, yahooapprove, collection, approvedby) VALUES (?,?,?,?,?,?,?,?);", [
                $this->id,
                $this->groupid,
                $this->yahoopendingid,
                $this->yahooapprovedid,
                $this->yahooreject,
                $this->yahooapprove,
                MessageCollection::INCOMING,
                $approvedby
            ]);
        }

        # Save the attachments.
        #
        # If we crash or fail at this point, we would have mislaid an attachment for a message.  That's not great, but the
        # perf cost of a transaction for incoming messages is significant, and we can live with it.
        foreach ($this->attachments as $att) {
            /** @var \PhpMimeMailParser\Attachment $att */
            $ct = $att->getContentType();
            $fn = $this->attach_dir . DIRECTORY_SEPARATOR . $att->getFilename();

            # Can't use LOAD_FILE as server may be remote.
            error_log("Get attachment $fn");
            $data = file_get_contents($fn);

            # Scale the image if it's large.  Ideally we'd store the full size image, but images can be many meg, and
            # it chews up disk space.
            if (strlen($data) > 300000) {
                $i = new Image($data);
                $w = $i->width();
                $w = min(1024, $w);
                $i->scale($w, NULL);
                $data = $i->getData();
                $ct = 'image/jpeg';
            }

            $sql = "INSERT INTO messages_attachments (msgid, contenttype, data) VALUES (?,?,?);";
            $this->dbhm->preExec($sql, [
                $this->id,
                $ct,
                $data
            ]);
        }

        foreach ($this->inlineimgs as $att) {
            $sql = "INSERT INTO messages_attachments (msgid, contenttype, data) VALUES (?,?,?);";
            $this->dbhm->preExec($sql, [
                $this->id,
                'image/jpeg',
                $att
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

        # Add into the search index.
        $this->s->add($this->id, $this->subject, strtotime($this->date), $this->groupid);

        return($id);
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

    public function getGroups() {
        $ret = [];
        $sql = "SELECT groupid FROM messages_groups WHERE msgid = ? AND deleted = 0;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id ]);
        foreach ($groups as $group) {
            $ret[] = $group['groupid'];
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
            $to = $this->getEnvelopefrom();
            $to = $to ? $to : $this->getFromaddr();
            $g = new Group($this->dbhr, $this->dbhm, $groupid);
            $atts = $g->getPublic();

            $me = whoAmI($this->dbhr, $this->dbhm);

            # Find who to send it from.  If we have a config to use for this group then it will tell us.
            $name = $me->getName();
            $c = new ModConfig($this->dbhr, $this->dbhm);
            $cid = $c->getForGroup($me->getId(), $groupid);
            $c = new ModConfig($this->dbhr, $this->dbhm, $cid);
            $fromname = $c->getPrivate('fromname');

            if ($fromname == 'Groupname Moderator') {
                $name = '$groupname Moderator';
            }

            # We can do a simple substitution in the from name.
            $name = str_replace('$groupname', $atts['namedisplay'], $name);

            $headers = "From: \"$name\" <" . $g->getModsEmail() . ">\r\n";

            $bcc = $c->getBcc($action);

            if ($bcc) {
                $bcc = str_replace('$groupname', $atts['nameshort'], $bcc);
                $headers .= "Bcc: $bcc\r\n";
            }

            $this->mailer(
                $to,
                $subject,
                $body,
                $headers,
                "-f" . $g->getModsEmail()
            );
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
                $this->mailer($group['yahooreject'], "My name is Iznik and I reject this message", "", NULL, '-f' . MODERATOR_EMAIL);
                $handled = true;
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

        $sql = "UPDATE messages_groups SET deleted = 1 WHERE msgid = ?;";
        $this->dbhm->preExec($sql, [
            $this->id
        ]);

        $this->notif->notifyGroupMods($groupid);

        $this->maybeMail($groupid, $subject, $body, 'Reject');
    }

    public function approve($groupid, $subject, $body, $stdmsgid) {
        # No need for a transaction - if things go wrong, the message will remain in pending, which is the correct
        # behaviour.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

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

        $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND groupid = ? AND deleted = 0;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);
        foreach ($groups as $group) {
            if ($group['yahooapprove']) {
                # We can trigger approval by email - do so.
                $this->mailer($group['yahooapprove'], "My name is Iznik and I approve this message", "", NULL, '-f' . MODERATOR_EMAIL);
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
                            $this->mailer($group['yahooreject'], "My name is Iznik and I reject this message", "", NULL, '-f' . MODERATOR_EMAIL);
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

    public function removeByMessageID() {
        $sql = "SELECT * FROM messages WHERE messageid = ?;";
        $msgs = $this->dbhr->preQuery($sql, [ $this->getMessageID() ]);
        foreach ($msgs as $msg) {
            $m = new Message($this->dbhr, $this->dbhm, $msg['id']);
            $m->delete('Received later copy of message with same Message-ID', NULL, NULL, NULL, NULL, TRUE);
        }
    }

    /**
     * @return mixed
     */
    public function getFromuser()
    {
        return $this->fromuser;
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

        if ($type) {
            # We get the Damerau-Levenshtein distance between the subjects, which we can use to
            # find the closest match if there isn't an exact one.
            $sql = "SELECT id, subject, date, DAMLEVLIM(subject, ?, 50) AS dist FROM messages WHERE fromuser = ? AND type = ?;";
            $messages = $this->dbhr->preQuery($sql, [ $this->subject, $this->fromuser, $type ]);
            #error_log($sql . var_export([ $this->subject, $this->subject, $this->fromuser, $type ], TRUE));

            $thistime = strtotime($this->date);
            # If we are using the standard subject line format, ignore all of the stuff that isn't the item.
            $subj1 = $this->subject;
            if (preg_match('/.*?\:(.*)\(.*\)/', $this->subject, $matches)) {
                $subj1 = trim($matches[1]);
            }
            $mindist = PHP_INT_MAX;
            $match = FALSE;
            $matchmsg = NULL;

            foreach ($messages as $message) {
                #error_log("{$message['subject']} vs {$this->subject} dist {$message['dist']}");
                #error_log("Compare {$message['date']} vs {$this->date}, " . strtotime($message['date']) . " vs $thistime");
                $mindist = min($mindist, $message['dist']);

                if ((($datedir == 1) && strtotime($message['date']) >= $thistime) ||
                    (($datedir == -1) && strtotime($message['date']) <= $thistime)) {
                    $subj2 = $message['subject'];
                    if (preg_match('/.*?\:(.*)\(.*\)/', $message['subject'], $matches)) {
                        $subj2 = trim($matches[1]);
                    }
                    #error_log("Compare subjects $subj1 vs $subj2 dist {$message['dist']} min $mindist lim " . (strlen($subj1) * 3 / 4));

                    if ($subj1 == $subj2) {
                        # Exact match
                        #error_log("Exact");
                        $match = TRUE;
                        $matchmsg = $message;
                    } else if ($message['dist'] <= $mindist &&
                        $message['dist'] <= strlen($subj1) * 3 / 4) {
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
        $g = new Group($this->dbhr, $this->dbhm, $groupid);
        $keywords = $g->getSetting('keywords', []);

        # This method is used to improve subjects, and also to map - because we need to make sure we understand the
        # subject format before can map.
        $type = $this->determineType($subject);

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
                        $locs = $l->search($aloc, $groupid, 1);

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
                        $residue = preg_replace('/^(' . $punc . '){2,}/','', $residue);
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

    public function search($string, &$context, $limit = Search::Limit, $restrict = NULL, $groups = NULL) {
        return($this->s->search($string, $context, $limit, $restrict, $groups));
    }
}