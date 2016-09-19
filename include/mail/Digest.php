<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/single.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/multiple.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/message.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/off.php');

class Digest
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;

    private $errorlog;

    const NEVER = 0;
    const IMMEDIATE = -1;
    const HOUR1 = 1;
    const HOUR2 = 2;
    const HOUR4 = 4;
    const HOUR8 = 8;
    const DAILY = 24;

    function __construct($dbhr, $dbhm, $id = NULL, $errorlog = FALSE)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($this->dbhr, $this->dbhm);
        $this->errorlog = $errorlog;
        
        $this->freqText = [



           Digest::NEVER => 'never',
            Digest::IMMEDIATE => 'immediately',
            Digest::HOUR1 => 'every hour',
            Digest::HOUR2 => 'every two hours',
            Digest::HOUR4 => 'every four hours',
            Digest::HOUR8 => 'every eight hours',
            Digest::DAILY => 'daily'
        ];
    }

    # Split out for UT to override
    public function sendOne($mailer, $message) {
        $mailer->send($message);
    }

    public function off($uid, $groupid) {
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->setMembershipAtt($groupid, 'emailfrequency', 0);
        $g = Group::get($this->dbhr, $this->dbhm, $groupid);

        # We can receive messages for emails from the old system where the group id is no longer valid.
        if ($g->getId() == $groupid) {
            $groupname = $g->getPublic()['namedisplay'];

            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_MAILOFF,
                'userid' => $uid,
                'groupid' => $groupid
            ]);

            $email = $u->getEmailPreferred();
            if ($email) {
                list ($transport, $mailer) = getMailer();
                $html = digest_off(USER_DOMAIN, USERLOGO, $groupname);

                $message = Swift_Message::newInstance()
                    ->setSubject("Email Change Confirmation")
                    ->setFrom([NOREPLY_ADDR => 'Do Not Reply'])
                    ->setReturnPath('bounce@direct.ilovefreegle.org')
                    ->setTo([ $email => $u->getName() ])
                    ->setBody("We've turned your emails off on $groupname.")
                    ->addPart($html, 'text/html');

                $this->sendOne($mailer, $message);
            }
        }
    }

    public function send($groupid, $frequency) {
        $g = Group::get($this->dbhr, $this->dbhm, $groupid);
        $gatts = $g->getPublic();
        $sent = 0;

        # TODO until we migrate over, we need to link to the old site, so we need the old group id.
        $fdgroupid = NULL;
        global $dbconfig;
        $dsn = "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname=republisher;charset=utf8";

        $dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => FALSE
        ));
        $sql = "SELECT groupid FROM groups WHERE groupname = " . $dbhold->quote($gatts['nameshort']) . ";";
        $fdgroups = $dbhold->query($sql);
        foreach ($fdgroups as $fdgroup) {
            $fdgroupid = $fdgroup['groupid'];
        }

        if ($fdgroupid) {
            if ($this->errorlog) { error_log("#$groupid " . $g->getPrivate('nameshort') . " send emails for $frequency"); }

            # Make sure we have a tracking entry.
            $sql = "INSERT IGNORE INTO groups_digests (groupid, frequency) VALUES (?, ?);";
            $this->dbhm->preExec($sql, [ $groupid, $frequency ]);

            $sql = "SELECT TIMESTAMPDIFF(MINUTE, started, NOW()) AS timeago, groups_digests.* FROM groups_digests WHERE  groupid = ? AND frequency = ? HAVING frequency = -1 OR timeago IS NULL OR timeago >= frequency * 60;";
            #error_log("Look for groups to process $sql, $groupid, $frequency");
            $tracks = $this->dbhr->preQuery($sql, [ $groupid, $frequency ]);

            foreach ($tracks as $track) {
                if ($this->errorlog) { error_log("Start group $groupid"); }
                $sql = "UPDATE groups_digests SET started = NOW() WHERE groupid = ? AND frequency = ?;";
                $this->dbhm->preExec($sql, [$groupid, $frequency]);

                # Find the cut-off time for the earliest message we want to include.  If we've not sent anything for this
                # group/frequency before then ensure we don't send anything older than a day.
                $oldest = " AND arrival >= '" . date("Y-m-d H:i:s", strtotime("24 hours ago")) . "'";

                # We record where we got up to using arrival.  We don't use msgid because the arrival gets reset when
                # we repost, but the msgid remains the same, and we want to send out messages which have been reposted
                # here.
                #
                # arrival is a high-precision timestamp, so it's effectively unique per message.
                $msgdtq = $track['msgdate'] ? " AND arrival > '{$track['msgdate']}' " : '';

                $sql = "SELECT msgid, arrival, yahooapprovedid FROM messages_groups WHERE groupid = ? AND collection = ? AND deleted = 0 $oldest $msgdtq ORDER BY arrival ASC;";
                $messages = $this->dbhr->preQuery($sql, [
                    $groupid,
                    MessageCollection::APPROVED,
                ]);

                $subjects = [];
                $available = [];
                $unavailable = [];
                $maxmsg = 0;
                $maxdate = NULL;

                foreach ($messages as $message) {
                    $maxmsg = max($message['msgid'], $maxmsg);
                    $maxdate = $message['arrival'];

                    $m = new Message($this->dbhr, $this->dbhm, $message['msgid']);
                    $subjects[$message['msgid']] = $m->getSubject();

                    $atts = $m->getPublic(FALSE, TRUE, TRUE);

                    # Strip out the clutter associated with various ways of posting.
                    $atts['textbody'] = $m->stripGumf();

                    # We need the approved ID on Yahoo for migration links.
                    # TODO remove in time.
                    $atts['yahooapprovedid'] = NULL;
                    $groups = $atts['groups'];
                    foreach ($groups as $group) {
                        if ($group['groupid'] == $groupid) {
                            $atts['yahooapprovedid'] = $group['yahooapprovedid'];
                        }
                    }

                    if ($atts['type'] == Message::TYPE_OFFER || $atts['type'] == Message::TYPE_WANTED) {
                        if (count($atts['related']) == 0) {
                            $available[] = $atts;
                        } else {
                            $unavailable[] = $atts;
                        }
                    }
                }

                # Build the array of message(s) to send.  If we are sending immediately this may have multiple,
                # otherwise it'll just be one.
                $tosend = [];

                if ($frequency == Digest::IMMEDIATE) {
                    foreach ($available as $msg) {
                        # For immediate messages, which we send out as they arrive, we can set them to reply to
                        # the original sender.  We include only the text body of the message, because we
                        # wrap it up inside our own HTML.
                        #
                        # Anything that is per-group is passed in as a parameter here.  Anything that is or might
                        # become per-user is in the template as a {{...}} substitution.
                        $msghtml = digest_message($msg, $msg['yahooapprovedid'], $fdgroupid);
                        $html = digest_single($msghtml,
                            USER_DOMAIN,
                            USERLOGO,
                            $gatts['namedisplay'],
                            $msg['subject'],
                            $msg['fromname'],
                            $msg['fromaddr']
                        );

                        # We have to send from the mods email, because of DMARC.
                        $tosend[] = [
                            'subject' => '[' . $gatts['namedisplay'] . "] {$msg['subject']}",
                            'from' => $g->getModsEmail(),
                            'fromname' => $msg['fromname'],
                            'replyto' => $msg['fromaddr'],
                            'replytoname' => $msg['fromname'],
                            'html' => $html,
                            'text' => $msg['textbody']
                        ];
                    }
                } else if (count($available) + count($unavailable) > 0) {
                    # Build up the HTML for the message(s) in it.  We add a teaser of items to make it more
                    # interesting.
                    $textsumm = '';
                    $availablehtml = '';
                    $availablesumm = '';
                    $count = count($available) > 0 ? count($available) : 1;
                    $subject = "[{$gatts['namedisplay']}] What's New ($count message" .
                        ($count == 1 ? ')' : 's)');
                    $subjinfo = '';

                    foreach ($available as $msg) {
                        $availablehtml .= $msghtml = digest_message($msg, $msg['yahooapprovedid'], $fdgroupid);
                        $textsumm .= $msg['subject'] . ":\r\nhttps://direct.ilovefreegle.org/login.php?action=mygroups&subaction=displaypost&msgid={$msg['id']}&groupid=$fdgroupid&digest=$fdgroupid\r\n\r\n";
                        $availablesumm .= $msg['subject'] . '<br />';

                        if (preg_match("/(.+)\:(.+)\((.+)\)/", $msg['subject'], $matches)) {
                            $item = trim($matches[2]);

                            if (strlen($item) < 25 && strlen($subjinfo) < 50) {
                                $subjinfo = $subjinfo == '' ? $item : "$subjinfo, $item";
                            }
                        }
                    }

                    if ($subjinfo) {
                        $subject .= " - $subjinfo...";
                    }

                    $unavailablehtml = '';

                    foreach ($unavailable as $msg) {
                        $unavailablehtml .= digest_message($msg, $msg['yahooapprovedid'], $fdgroupid);
                        $textsumm .= $msg['subject'] . " (post completed, no longer active)\r\n";
                    }

                    $html = digest_multiple($availablehtml,
                        $availablesumm,
                        $unavailablehtml,
                        USER_DOMAIN,
                        USERLOGO,
                        $gatts['namedisplay'],
                        $subject,
                        $gatts['namedisplay'],
                        $g->getModsEmail()
                    );

                    $tosend[] = [
                        'subject' => $subject,
                        'from' => $g->getModsEmail(),
                        'fromname' => $gatts['namedisplay'],
                        'replyto' => $g->getModsEmail(),
                        'replytoname' => $gatts['namedisplay'],
                        'html' => $html,
                        'text' => $textsumm
                    ];
                }

                if (count($tosend) > 0) {
                    # Now find the users we want to send to on this group for this frequency.  We build up an array of
                    # the substitutions we need.
                    # TODO This isn't that well indexed in the table.
                    $replacements = [];

                    $sql = "SELECT userid FROM memberships WHERE groupid = ? AND emailfrequency = ? ORDER BY userid ASC;";
                    $users = $this->dbhr->preQuery($sql,
                        [ $groupid, $frequency ]);

                    foreach ($users as $user) {
                        $u = User::get($this->dbhr, $this->dbhm, $user['userid']);
                        if ($this->errorlog) { error_log("Consider user {$user['userid']}"); }

                        # We are only interested in sending digests to users for whom we have a preferred address -
                        # otherwise where would we send them?
                        $email = $u->getEmailPreferred();
                        if ($this->errorlog) { error_log("Preferred $email"); }

                        if ($email && $u->sendOurMails($g)) {
                            # TODO These are the replacements for the mails sent before FDv2 is retired.  These will change.
                            $replacements[$email] = [
                                '{{toname}}' => $u->getName(),
                                '{{bounce}}' => "bounce-{$user['userid']}@" . USER_DOMAIN,
                                '{{unsubscribe}}' => 'https://direct.ilovefreegle.org/unsubscribe.php?email=' . urlencode($email),
                                '{{email}}' => $email,
                                '{{frequency}}' => $this->freqText[$frequency],
                                '{{noemail}}' => 'digestoff-' . $user['userid'] . "-$groupid@" . USER_DOMAIN,
                                '{{post}}' => "https://direct.ilovefreegle.org/login.php?action=post&groupid=$fdgroupid&digest=$fdgroupid",
                                '{{visit}}' => "https://direct.ilovefreegle.org/login.php?action=mygroups&subaction=displaygroup&groupid=$fdgroupid&digest=$fdgroupid"
                            ];
                        }
                    }

                    if (count($replacements) > 0) {
                        error_log("#$groupid {$gatts['nameshort']} " . count($tosend) . " messages max $maxmsg, $maxdate to " . count($replacements) . " users");
                        # Now send.  We use a failover transport so that if we fail to send, we'll queue it for later
                        # rather than lose it.
                        list ($transport, $mailer) = getMailer();
                        
                        # We're decorating using the information we collected earlier.  However the decorator doesn't
                        # cope with sending to multiple recipients properly (headers just get decorated with the first
                        # recipient) so we create a message for each recipient.
                        $decorator = new Swift_Plugins_DecoratorPlugin($replacements);
                        $mailer->registerPlugin($decorator);

                        # We don't want to send too many mails before we reconnect.  This plugin breaks it up.
                        $mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(900));

                        $_SERVER['SERVER_NAME'] = USER_DOMAIN;
                        foreach ($tosend as $msg) {
                            foreach ($replacements as $email => $rep) {
                                try {
                                    $message = Swift_Message::newInstance()
                                        ->setSubject($msg['subject'])
                                        ->setFrom([$msg['from'] => $msg['fromname']])
                                        ->setReturnPath($rep['bounce'])
                                        ->setReplyTo($msg['replyto'], $msg['replytoname'])
                                        ->setBody($msg['text'])
                                        ->addPart($msg['html'], 'text/html');

                                    $headers = $message->getHeaders();
                                    $headers->addTextHeader('List-Unsubscribe', '<mailto:{{noemail}}>, <{{unsubscribe}}>');

//                                    error_log("...$email {$msg['subject']}");
//                                    if (strpos($email, 'btinternet.com')) {
//                                        error_log("BT - cause fail");
//                                        $message->setTo([ 'log@ehibbert.org.uk' => $rep['{{toname}}'] ]);
//                                        #$message->setCc( [ 'investigation06@btinternet.com' => $rep['{{toname}}'] ]);
//                                        $this->sendOne($mailer, $message);
//                                        exit(0);
//                                    }
                                    $message->setTo([ $email => $rep['{{toname}}'] ]);
                                    $this->sendOne($mailer, $message);
                                    $sent++;
                                } catch (Exception $e) {
                                    error_log($email . " skipped with " . $e->getMessage());
                                }
                            }
                        }
                    }

                    if ($maxdate) {
                        # Record the message we got upto.
                        $sql = "UPDATE groups_digests SET msgid = ?, msgdate = ? WHERE groupid = ? AND frequency = ?;";
                        $this->dbhm->preExec($sql, [$maxmsg, $maxdate, $groupid, $frequency]);
                    }
                }

                $sql = "UPDATE groups_digests SET ended = NOW() WHERE groupid = ? AND frequency = ?;";
                $this->dbhm->preExec($sql, [$groupid, $frequency]);
            }
        }
 
        return($sent);
    }
}