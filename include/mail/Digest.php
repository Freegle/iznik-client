<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/single.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/multiple.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/message.php');

class Digest
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;

    const NEVER = 0;
    const IMMEDIATE = -1;
    const HOUR1 = 1;
    const HOUR2 = 2;
    const HOUR4 = 4;
    const HOUR8 = 8;
    const DAILY = 24;

    function __construct($dbhr, $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($this->dbhr, $this->dbhm);
        
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
    
    public function send($groupid, $frequency) {
        $g = new Group($this->dbhr, $this->dbhm);
        $gatts = $g->getPublic();

        if ($gatts['type'] == Group::GROUP_FREEGLE) {
            # We only send digests for Freegle groups.

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
                error_log($g->getPrivate('nameshort') . " send emails for $frequency");

                # Make sure we have a tracking entry.
                $sql = "INSERT IGNORE INTO groups_digests (groupid, frequency) VALUES (?, ?);";
                $this->dbhm->preExec($sql, [ $groupid, $frequency ]);

                $sql = "SELECT * FROM groups_digests WHERE groupid = ? AND frequency = ?;";
                $tracks = $this->dbhr->preQuery($sql, [ $groupid, $frequency ]);
                foreach ($tracks as $track) {
                    # Find the cut-off time for the earliest message we want to include.  If we've not sent anything for this
                    # group/frequency before then ensure we don't send anything older than a day.
                    $oldest = " AND messages.arrival >= '" . date("Y-m-d H:i:s", strtotime("24 hours ago")) . "'";
                    $msgidq = $track['msgid'] ? " AND msgid > {$track['msgid']} " : '';

                    error_log("Prepare messages");
                    $sql = "SELECT msgid, yahooapprovedid FROM messages_groups WHERE groupid = ? AND collection = ? AND deleted = 0 $oldest $msgidq ORDER BY msgid ASC;";
                    error_log($sql);
                    $messages = $this->dbhr->preQuery($sql, [
                        $groupid,
                        MessageCollection::APPROVED,
                    ]);

                    $subjects = [];
                    $available = [];
                    $unavailable = [];

                    foreach ($messages as $message) {
                        $m = new Message($this->dbhr, $this->dbhm, $message['msgid']);
                        $subjects[$message['msgid']] = $m->getSubject();

                        $atts = $m->getPublic(FALSE, TRUE);
                        if ($atts['type'] == Message::TYPE_OFFER || $atts['type'] == Message::TYPE_WANTED) {
                            if (count($atts['related']) == 0) {
                                $available[] = $atts;
                            } else {
                                $unavailable = $atts;
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
                            $msghtml = digest_message($msg);
                            $html = digest_single($msghtml, USER_DOMAIN, USERLOGO, $msg['subject'], $msg['fromname'], $msg['fromaddr'], "{{unsubscribe}}");

                            $tosend[] = [
                                'subject' => $msg['subject'],
                                'from' => $msg['fromaddr'],
                                'fromname' => $msg['fromname'],
                                'replyto' => $msg['fromaddr'],
                                'replytoname' => $msg['fromname'],
                                'html' => $html,
                                'text' => $msg['textbody']
                            ];
                        }
                    } else {
                        # TODO
                        # Build up the HTML for the message(s) in it.
                        $availablehtml = '';

                        foreach ($available as $msg) {
                            $availablehtml .= digest_message($msg);
                        }

                        $unavailablehtml = '';

                        foreach ($unavailable as $msg) {
                            $unavailablehtml .= digest_message($msg);
                        }

                        $html = digest_multiple($availablehtml, $unavailablehtml, USER_DOMAIN, USERLOGO, $msg['subject'], $msg['fromname'], $msg['fromaddr'], "{{unsubscribe}}");
                        // TODO Could we do something better?
                        $text = 'This is a digest of mails.  Please read the HTML version.  If you cannot read that, then please switch to getting emails immediately, which will allow you to see the text version of each mail.';

                        $tosend[] = [
                            'subject' => '', // TODO
                            'from' => $g->getModsEmail(),
                            'fromname' => $gatts['namedisplay'],
                            'replyto' => $g->getModsEmail(),
                            'replytoname' => $gatts['namedisplay'],
                            'html' => $html,
                            'text' => $text
                        ];
                    }

                    # We might have stopped partway through a previous run; if so, then userid will record where we were
                    # upto.
                    $upto = intval($track['userid']);
                    # TODO Save and honour this.

                    # Now find the users we want to send to on this group for this frequency.  We build up an array of
                    # the substitutions we need.
                    # TODO This isn't that well indexed in the table.
                    $replacements = [];

                    error_log("Find users");
                    $users = $this->dbhr->preQuery("SELECT userid FROM memberships WHERE groupid = ? AND emailallowed = 1 AND emailfrequency = ? ORDER BY userid ASC;");
                    error_log("Prepare users");
                    foreach ($users as $user) {
                        $u = new User($this->dbhr, $this->dbhm, $user['userid']);
                        $emails = $u->getEmails();

                        if (count($emails) > 0) {
                            $email = $emails[0];
                            error_log("...$email");

                            # TODO These are the replacements for the mails sent before FDv2 is retired.  These will change.
                            $replacements[$email] = [
                                '{{toname}}' => $u->getName(),
                                '{{unsubscribe}}' => 'https://direct.ilovefreegle.org/unsubscribe.php?email=' . urlencode($email),
                                '{{email}}' => $email,
                                '{{frequency}}' => $this->freqText[$frequency],
                                '{{noemail}}' => 'digestoff-' . $user['userid'] . "@" . USER_DOMAIN
                            ];
                        }
                    }

                    error_log("Prepared users");

                    if (count($replacements) > 0) {
                        # Now send.  We use a failover transport so that if we fail to send, we'll queue it for later
                        # rather than lose it.
                        $spool = new Swift_FileSpool(IZNIK_BASE . "/spool");
                        $spooltrans = Swift_SpoolTransport::newInstance($spool);
                        $smtptrans = Swift_SmtpTransport::newInstance("localhost");
                        $transport = Swift_FailoverTransport::newInstance([
                            $smtptrans,
                            $spooltrans
                        ]);

                        $mailer = Swift_Mailer::newInstance($transport);

                        # We're decorating using the information we collected earlier.
                        $decorator = new Swift_Plugins_DecoratorPlugin($replacements);
                        $mailer->registerPlugin($decorator);

                        foreach ($tosend as $msg) {
                            # Now send it
                            $message = Swift_Message::newInstance()
                                ->setSubject($msg['subject'])
                                ->setFrom([$msg['from'] => $msg['fromname']])
                                ->setReturnPath(NOREPLY_ADDR)
                                ->setReplyTo($msg['replyto'], $msg['replytoname'])
                                #->setTo([$to => $toname])
                                ->setTo(['log@ehibbert.org.uk' => '{{toname}}'])
                                ->setBody($msg['text'])
                                ->addPart($msg['html'], 'text/html');
                            $headers = $message->getHeaders();
                            $headers->addTextHeader('List-Unsubscribe', '<mailto:{{unsubscribe}}>');

                            $this->sendOne($mailer, $message);
                        }
                    }
                }
            }
        }
    }
}