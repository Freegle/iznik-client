<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/misc/plugin.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/message/Collection.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

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
        $rc = $this->dbhm->preExec("UPDATE messages SET $att = ? WHERE id = {$this->id};", [$val]);
        if ($rc) {
            $this->$att = $val;
        }
    }

    public function edit($subject, $textbody, $htmlbody) {
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
            $this->setPrivate('subject', $subject);
        }

        if ($textbody) {
            $this->setPrivate('textbody', $textbody);
        }

        if ($htmlbody) {
            $this->setPrivate('htmlbody', $htmlbody);
        }
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

    private $id, $source, $sourceheader, $message, $textbody, $htmlbody, $subject, $fromname, $fromaddr,
        $envelopefrom, $envelopeto, $messageid, $tnpostid, $fromip, $date,
        $fromhost, $type, $attachments, $yahoopendingid, $yahooapprovedid, $yahooreject, $yahooapprove, $attach_dir, $attach_files,
        $parser, $arrival, $spamreason, $spamtype, $fromuser, $fromcountry, $deleted, $heldby;

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
        'id', 'subject', 'type', 'arrival', 'date', 'deleted', 'heldby'
    ];

    public $memberAtts = [
        'textbody', 'htmlbody', 'fromname', 'fromuser'
    ];

    public $moderatorAtts = [
        'source', 'sourceheader', 'fromaddr', 'envelopeto', 'envelopefrom', 'messageid', 'tnpostid',
        'fromip', 'fromcountry', 'message', 'spamreason', 'spamtype'
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
    }

    # Default mailer is to use the standard PHP one, but this can be overridden in UT.
    private function mailer() {
        call_user_func_array('mail', func_get_args());
    }

    public function getRoleForMessage() {
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
        $ret['subject'] = preg_replace('/\[.*\]\s*/', '', $ret['subject']);

        # Add any groups that this message is on.
        $ret['groups'] = [];
        $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND deleted = 0;";
        $ret['groups'] = $this->dbhr->preQuery($sql, [ $this->id ] );

        foreach ($ret['groups'] as &$group) {
            $ret['suggestedsubject'] = $this->suggestSubject($group['groupid'], $this->subject);
            $group['arrival'] = ISODate($group['arrival']);
        }

        # Add derived attributes.
        $ret['arrival'] = ISODate($ret['arrival']);
        $ret['date'] = ISODate($ret['date']);
        $ret['daysago'] = floor((time() - strtotime($ret['date'])) / 86400);

        if (pres('fromcountry', $ret)) {
            $ret['fromcountry'] = code_to_country($ret['fromcountry']);
        }

        if (pres('fromuser', $ret)) {
            $u = new User($this->dbhr, $this->dbhm, $ret['fromuser']);

            # Get the user details, relative to the groups this message appears on.
            $ret['fromuser'] = $u->getPublic($this->getGroups(), $messagehistory, FALSE);
            filterResult($ret['fromuser']);
        }

        if ($related) {
            # Add any related messages
            $ret['related'] = [];
            $sql = "SELECT * FROM messages_related WHERE id1 = ? OR id2 = ?;";
            $rels = $this->dbhr->preQuery($sql, [ $this->id, $this->id ]);
            foreach ($rels as $rel) {
                $id = $rel['id1'] == $this->id ? $rel['id2'] : $rel['id1'];
                $m = new Message($this->dbhr, $this->dbhm, $id);
                $ret['related'][] = $m->getPublic(FALSE, FALSE);
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

        foreach (Message::keywords() as $keyword => $vals) {
            foreach ($vals as $val) {
                if (preg_match('/\b' . preg_quote($val) . '\b/i', $subj)) {
                    $type = $keyword;
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

        # Yahoo posts messages from the group address, but with a header showing the
        # original from address.
        $originalfrom = $Parser->getHeader('x-original-from');

        if ($originalfrom) {
            $from = mailparse_rfc822_parse_addresses($originalfrom);
        } else {
            $from = mailparse_rfc822_parse_addresses($Parser->getHeader('from'));
        }

        $this->fromname = $from[0]['display'];
        $this->fromaddr = $from[0]['address'];

        if (!$this->fromaddr) {
            # We have failed to parse out this message.
            return(FALSE);
        }

        $this->date = gmdate("Y-m-d H:i:s", strtotime($Parser->getHeader('date')));

        $this->sourceheader = $Parser->getHeader('x-freegle-source');
        $this->sourceheader = ($this->sourceheader == 'Unknown' ? NULL : $this->sourceheader);

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
            if (stripos($src, 'http://') === 0 || stripos($src, 'https://') === 0) {
                $ctx = stream_context_create(array('http'=>
                    array(
                        'timeout' => 30,  # Only wait for 30 seconds to fetch an image.
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

        # See if we can find a group this is intended for.
        $groupname = NULL;
        $to = $this->getTo();
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

                        if ($userid = $u->create(NULL, NULL, $name)) {
                            # If any of these fail, then we'll pick it up later when we do a sync with the source group,
                            # so no need for a transaction.
                            $u = new User($this->dbhr, $this->dbhm, $userid);
                            $u->addEmail($this->fromaddr, TRUE);
                            $l = new Log($this->dbhr, $this->dbhm);
                            $l->log([
                                'type' => Log::TYPE_USER,
                                'subtype' => Log::SUBTYPE_CREATED,
                                'msgid' => $this->id,
                                'user' => $userid,
                                'text' => 'First seen on incoming message',
                                'groupid' => $this->groupid
                            ]);

                            $u->addMembership($this->groupid);
                        }
                    }

                    # Now we have a user.  If there is a Yahoo uid in here - which there isn't always - add it to the
                    # user entry.
                    $gp = $Parser->getHeader('x-yahoo-group-post');
                    if ($gp && preg_match('/u=(.*);/', $gp, $matches)) {
                        // This is Yahoo's unique identifier for this user.
                        $u = new User($this->dbhr, $this->dbhm, $userid);

                        if ($u->getPrivate('yahooUserId') != $matches[1]) {
                            $u->setPrivate('yahooUserId', $matches[1]);
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
            if ($p) {
                $crpos = strpos($current, "\r\n", $p);
                $ct = substr($current, $p, $crpos - $p);

                $found = TRUE;

                # We don't want to prune a multipart, only the bottom level parts.
                if (stripos($ct, "multipart") === FALSE) {
                    # Find the boundary before it.
                    $boundpos = strrpos(substr($current, 0, $p), "\r\n--");

                    if ($boundpos) {
                        $crpos = strpos($current, "\r\n", $boundpos + 2);
                        $boundary = substr($current, $boundpos + 2, $crpos - ($boundpos + 2));

                        # Find the end of the bodypart headers.
                        $breakpos = strpos($current, "\r\n\r\n", $boundpos);

                        # Find the end of the bodypart.
                        $nextboundpos = strpos($current, $boundary, $breakpos);

                        # Keep a max of 10K.
                        #
                        # Observant readers may wish to comment on this definition of K.
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
        # A message we are saving as approved may previously have been in system, for example as pending.  When it
        # comes back to us, it might not be the same, so we should remove any old one first.
        #
        # This can happen if a message is handled on another system, e.g. moderated directly on Yahoo.
        #
        # We don't need a transaction for this - transactions aren't great for scalability and worst case we
        # leave a spurious message around which a mod will handle.
        $this->removeByMessageID($this->groupid);

        # Reduce the size of the message source
        $this->pruneMessage();

        # Save into the messages table.
        $sql = "INSERT INTO messages (date, source, sourceheader, message, fromuser, envelopefrom, envelopeto, fromname, fromaddr, subject, messageid, tnpostid, textbody, htmlbody, type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
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
            $this->subject,
            $this->messageid,
            $this->tnpostid,
            $this->textbody,
            $this->htmlbody,
            $this->type
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
            $this->dbhm->preExec("INSERT INTO messages_groups (msgid, groupid, yahoopendingid, yahooapprovedid, yahooreject, yahooapprove, collection) VALUES (?,?,?,?,?,?,?);", [
                $this->id,
                $this->groupid,
                $this->yahoopendingid,
                $this->yahooapprovedid,
                $this->yahooreject,
                $this->yahooapprove,
                Collection::INCOMING
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
        $sql = "INSERT INTO messages_history (groupid, source, fromuser, envelopefrom, envelopeto, fromname, fromaddr, subject, prunedsubject, messageid, msgid) VALUES(?,?,?,?,?,?,?,?,?,?,?);";
        $this->dbhm->preExec($sql, [
            $this->groupid,
            $this->source,
            $this->fromuser,
            $this->envelopefrom,
            $this->envelopeto,
            $this->fromname,
            $this->fromaddr,
            $this->subject,
            $this->getPrunedSubject(),
            $this->messageid,
            $this->id
        ]);

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
        $ret = false;
        $sql = "SELECT msgid FROM messages_groups WHERE msgid = ? AND groupid = ? AND collection = ? AND deleted = 0;";
        $groups = $this->dbhr->preQuery($sql, [
            $this->id,
            $groupid,
            Collection::PENDING
        ]);

        return(count($groups) > 0);
    }

    private function maybeMail($groupid, $subject, $body) {
        if ($subject) {
            # We have a rejection mail to send.
            $to = $this->getEnvelopefrom();
            $to = $to ? $to : $this->getFromaddr();
            $g = new Group($this->dbhr, $this->dbhm, $groupid);
            $atts = $g->getPublic();
            $me = whoAmI($this->dbhr, $this->dbhm);

            $name = $me->getName();

            # We can do a siple substitution in the from name.
            $name = str_replace('$groupname', $atts['namedisplay'], $name);

            $headers = "From: \"$name\" <" . $g->getModsEmail() . ">\r\n";

            $this->mailer(
                $to,
                $subject,
                $body,
                $headers,
                "-f" . $g->getModsEmail()
            );
        }
    }

    public function reject($groupid, $subject, $body) {
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
            'text' => $subject
        ]);

        $handled = false;

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
                $handled = true;
            }
        }

        $sql = "UPDATE messages_groups SET deleted = 1 WHERE msgid = ?;";
        $this->dbhm->preExec($sql, [
            $this->id
        ]);

        $this->maybeMail($groupid, $subject, $body);
    }

    public function approve($groupid, $subject, $body) {
        # No need for a transaction - if things go wrong, the message will remain in pending, which is the correct
        # behaviour.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $this->log->log([
            'type' => Log::TYPE_MESSAGE,
            'subtype' => Log::SUBTYPE_APPROVED,
            'msgid' => $this->id,
            'user' => $this->fromuser,
            'byuser' => $me ? $me->getId() : NULL,
            'groupid' => $groupid,
            'text' => $subject
        ]);

        $handled = false;

        $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND groupid = ? AND deleted = 0;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);
        foreach ($groups as $group) {
            if ($group['yahooapprove']) {
                # We can trigger approval by email - do so.
                $this->mailer($group['yahooapprove'], "My name is Iznik and I approve this message", "", NULL, '-f' . MODERATOR_EMAIL);
                $handled = true;
            }

            if ($group['yahoopendingid']) {
                # We can trigger approval via the plugin - do so.
                $p = new Plugin($this->dbhr, $this->dbhm);
                $p->add($groupid, [
                    'type' => 'ApprovePendingMessage',
                    'id' => $group['yahoopendingid']
                ]);
                $handled = true;
            }
        }

        if ($handled) {
            $sql = "UPDATE messages_groups SET collection = ? WHERE msgid = ?;";
            $this->dbhm->preExec($sql, [
                Collection::APPROVED,
                $this->id
            ]);

            $this->maybeMail($groupid, $subject, $body);
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

    function delete($reason = NULL, $groupid = NULL)
    {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $rc = true;

        if ($this->attach_dir) {
            rrmdir($this->attach_dir);
        }

        if ($this->id) {
            $this->log->log([
                'type' => Log::TYPE_MESSAGE,
                'subtype' => Log::SUBTYPE_DELETED,
                'msgid' => $this->id,
                'user' => $this->fromuser,
                'byuser' => $me ? $me->getId() : NULL,
                'text' => $reason,
                'groupid' => $groupid
            ]);

            # Delete from a specific or all groups that it's on.
            $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND " . ($groupid ? " groupid = ?" : " ?") . ";";
            $groups = $this->dbhr->preQuery($sql,
                [
                    $this->id,
                    $groupid ? $groupid : 1
                ]);

            foreach ($groups as $group) {
                $groupid = $group['groupid'];

                # The message has been allocated to a group; mark it as deleted.  We keep deleted messages for
                # PD.
                $rc = $this->dbhm->preExec("UPDATE messages_groups SET deleted = 1 WHERE msgid = ? AND groupid = ?;", [
                    $this->id,
                    $groupid
                ]);

                # We might be deleting a pending or spam message, in which case it may also need rejecting on Yahoo.
                $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND groupid = ? AND deleted = 0;";
                $groups = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);

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

            # If we have deleted this message from all groups, mark it as deleted in the messages table.
            $extant = FALSE;
            $sql = "SELECT * FROM messages_groups WHERE msgid = ? AND deleted = 0;";
            $groups = $this->dbhr->preQuery($sql, [ $this->id ]);
            foreach ($groups as $group) {
                error_log("Found extant on group {$group['id']}");
                $extant = TRUE;
            }

            if (!$extant) {
                $rc = $this->dbhm->preExec("UPDATE messages SET deleted = NOW() WHERE id = ?;", [ $this->id ]);
                error_log("Not extant, delete returned $rc");
            }
        }

        return($rc);
    }

    public function removeByMessageID($groupid) {
        # Try to find by message id.
        $msgid = $this->getMessageID();
        if ($msgid) {
            $this->dbhm->preExec("UPDATE messages_groups SET deleted = 1 WHERE msgid IN (SELECT id FROM messages WHERE messageid LIKE ?) AND messages_groups.groupid = ?;", [
                $msgid,
                $groupid
            ]);
        }

        # Also try to find by TN post id
        $tnpostid = $this->getTnpostid();
        if ($tnpostid) {
            $this->dbhm->preExec("UPDATE messages_groups SET deleted = 1 WHERE msgid IN (SELECT id FROM messages WHERE tnpostid LIKE ?) AND messages_groups.groupid = ?;;", [
                $tnpostid,
                $groupid
            ]);
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
            $sql = "SELECT id, subject, date, DAMLEVLIM(subject, ?, 50) AS dist, MIN(DAMLEVLIM(subject, ?, 50)) AS mindist FROM messages WHERE fromuser = ? AND type = ?;";
            $messages = $this->dbhr->preQuery($sql, [ $this->subject, $this->subject, $this->fromuser, $type ]);

            $thistime = strtotime($this->date);
            # Ignore the first word; probably a subject keyword.
            $subj1 = strtolower(preg_replace('/[A-Za-z]*(.*)/', "$1", $this->subject));

            foreach ($messages as $message) {
                #error_log("{$message['subject']} vs {$this->subject} dist {$message['dist']} vs {$message['mindist']}");
                #error_log("Compare {$message['date']} vs {$this->date}, " . strtotime($message['date']) . " vs $thistime");
                $match = FALSE;

                if ((($datedir == 1) && strtotime($message['date']) >= $thistime) ||
                    (($datedir == -1) && strtotime($message['date']) <= $thistime)) {
                    $subj2 = strtolower(preg_replace('/[A-Za-z]*(.*)/', "$1", $message['subject']));
                    #error_log("Compare subjects $subj1 vs $subj2 dist lim " . (strlen($subj1) * 3 / 4));

                    if ($subj1 == $subj2) {
                        # Exact match
                        $match = TRUE;
                    } else if ($message['dist'] == $message['mindist'] &&
                        $message['dist'] <= strlen($subj1) * 3 / 4) {
                        # This is the closest match, but not utterly different.
                        $match = TRUE;
                    }
                }

                if ($match) {
                    $sql = "INSERT INTO messages_related (id1, id2) VALUES (?,?);";
                    $this->dbhm->preExec($sql, [ $this->id, $message['id']] );
                    $found++;
                }
            }
        }

        return($found);
    }

    public function notSpam() {
        if ($this->spamtype == Spam::REASON_SUBJECT_USED_FOR_DIFFERENT_GROUPS) {
            # This subject is probably fine, then.
            $s = new Spam($this->dbhr, $this->dbhm);
            $s->notSpamSubject($this->getPrunedSubject());
        }

        # We leave the spamreason and type set in the message, because it can be useful for later PD.
    }

    public function suggestSubject($groupid, $subject) {
        $newsubj = $subject;

        # This method is used to improve subjects.
        $type = $this->determineType($subject);

        switch ($type) {
            case Message::TYPE_OFFER:
            case Message::TYPE_TAKEN:
            case Message::TYPE_WANTED:
            case Message::TYPE_RECEIVED:
                # Remove any subject tag.
                $subject = preg_replace('/\[.*\]\s*/', '', $subject);

                $pretag = $subject;

                # Strip any of the keywords.
                foreach ($this->keywords()[$type] as $keyword) {
                    $subject = preg_replace('/(^|\b)' . preg_quote($keyword) . '\b/i', '', $subject);
                }

                # Only proceed if we found the subject tag.
                if ($subject != $pretag) {
                    # Shrink multiple spaces
                    $subject = preg_replace('/\s+/', ' ', $subject);
                    $subject = trim($subject);

                    # Find a location in the subject.
                    if (preg_match('/(.*)\((.*)\)/', $subject, $matches)) {
                        # Find the residue, which will be the item, and tidy it.
                        $residue = trim($matches[1]);
                        $punc = '\(|\)|\[|\]|\,|\.|\-|\{|\}|\:|\;| ';
                        $residue = preg_replace('/^(' . $punc . '){2,}/','', $residue);
                        $residue = preg_replace('/(' . $punc . '){2,}$/','', $residue);

                        $loc = $matches[2];

                        # Check if it's a good location.
                        $l = new Location($this->dbhr, $this->dbhm);
                        $locs = $l->search($loc, $groupid, 1);

                        if (count($locs) == 1) {
                            # Take the name we found, which may be better than the one we have, if only in capitalisation.
                            $loc = $locs[0]['name'];
                        }

                        $newsubj = strtoupper($type) . ": $residue ($loc)";
                    }
                }
               break;
        }

        return($newsubj);
    }
}