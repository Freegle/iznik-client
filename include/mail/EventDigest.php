<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/events.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/event.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/eventsoff.php');

class EventDigest
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;

    private $errorlog;

    function __construct($dbhr, $dbhm, $errorlog = FALSE)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($this->dbhr, $this->dbhm);
        $this->errorlog = $errorlog;
    }

    # Split out for UT to override
    public function sendOne($mailer, $message) {
        $mailer->send($message);
    }

    public function off($uid, $groupid) {
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->setMembershipAtt($groupid, 'eventsallowed', 0);
        $g = Group::get($this->dbhr, $this->dbhm, $groupid);

        # We can receive messages for emails from the old system where the group id is no longer valid.
        if ($g->getId() == $groupid) {
            $groupname = $g->getPublic()['namedisplay'];

            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_EVENTSOFF,
                'userid' => $uid,
                'groupid' => $groupid
            ]);

            $email = $u->getEmailPreferred();
            if ($email) {
                list ($transport, $mailer) = getMailer();
                $html = events_off(USER_DOMAIN, USERLOGO, $groupname);

                $message = Swift_Message::newInstance()
                    ->setSubject("Email Change Confirmation")
                    ->setFrom([NOREPLY_ADDR => SITE_NAME])
                    ->setReturnPath('bounce@direct.ilovefreegle.org')
                    ->setTo([ $email => $u->getName() ])
                    ->setBody("We've turned your community event emails off on $groupname.")
                    ->addPart($html, 'text/html');

                $this->sendOne($mailer, $message);
            }
        }
    }

    public function send($groupid, $ccto = NULL) {
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
            if ($this->errorlog) { error_log("#$groupid " . $g->getPrivate('nameshort')); }

            # We want to send all events which start within the next month for this group.
            $sql = "SELECT DISTINCT communityevents.id FROM communityevents INNER JOIN communityevents_groups ON communityevents_groups.eventid = communityevents.id AND groupid = ? INNER JOIN communityevents_dates ON communityevents_dates.eventid = communityevents.id WHERE start >= NOW() AND DATEDIFF(NOW(), start) <= 30 ORDER BY communityevents_dates.start ASC;";
            #error_log("Look for groups to process $sql, $groupid");
            $events = $this->dbhr->preQuery($sql, [ $groupid ]);

            if ($this->errorlog) { error_log("Consider " . count($events) . " events"); }

            $textsumm = '';
            $htmlsumm = '';

            $tz1 = new DateTimeZone('UTC');
            $tz2 = new DateTimeZone('Europe/London');

            if (count($events) > 0) {
                foreach ($events as $event) {
                    if ($this->errorlog) { error_log("Start group $groupid"); }

                    $e = new CommunityEvent($this->dbhr, $this->dbhm, $event['id']);
                    $atts = $e->getPublic();

                    foreach ($atts['dates'] as $date) {
                        $htmlsumm .= digest_event($atts, $date['start'], $date['end']);

                        # Get a string representation of the date in UK time.
                        $datetime = new DateTime($date['start'], $tz1);
                        $datetime->setTimezone($tz2);
                        $datestr = $datetime->format('D, jS F g:ia');

                        $textsumm .= $atts['title'] . " starts $datestr at " . $atts['location'] . "\r\n";
                    }
                }

                $html = digest_events($htmlsumm,
                    USER_SITE,
                    USERLOGO,
                    $gatts['namedisplay']
                );

                $tosend = [
                    'subject' => '[' . $gatts['namedisplay'] . "] Community Event Roundup",
                    'from' => $g->getModsEmail(),
                    'fromname' => $gatts['namedisplay'],
                    'replyto' => $g->getModsEmail(),
                    'replytoname' => $gatts['namedisplay'],
                    'html' => $html,
                    'text' => $textsumm
                ];

                # Now find the users we want to send to on this group for this frequency.  We build up an array of
                # the substitutions we need.
                # TODO This isn't that well indexed in the table.
                $replacements = [];

                $sql = "SELECT userid FROM memberships WHERE groupid = ? AND eventsallowed = 1 ORDER BY userid ASC;";
                $users = $this->dbhr->preQuery($sql, [ $groupid, ]);

                if ($this->errorlog) { error_log("Consider " . count($users) . " users "); }
                foreach ($users as $user) {
                    $u = User::get($this->dbhr, $this->dbhm, $user['userid']);
                    if ($this->errorlog) {
                        error_log("Consider user {$user['userid']}");
                    }

                    # We are only interested in sending events to users for whom we have a preferred address -
                    # otherwise where would we send them?
                    $email = $u->getEmailPreferred();
                    if ($this->errorlog) { error_log("Preferred $email, send " . $u->sendOurMails($g)); }

                    if ($email && $u->sendOurMails($g)) {
                        # TODO These are the replacements for the mails sent before FDv2 is retired.  These will change.
                        if ($this->errorlog) { error_log("Send to them"); }
                        $replacements[$email] = [
                            '{{toname}}' => $u->getName(),
                            '{{unsubscribe}}' => 'https://direct.ilovefreegle.org/unsubscribe.php?email=' . urlencode($email),
                            '{{email}}' => $email,
                            '{{noemail}}' => 'eventsoff-' . $user['userid'] . "-$groupid@" . USER_DOMAIN,
                            '{{post}}' => "https://direct.ilovefreegle.org/login.php?action=post&groupid=$fdgroupid&digest=$fdgroupid",
                            '{{visit}}' => "https://direct.ilovefreegle.org/login.php?action=mygroups&subaction=displaygroup&groupid=$fdgroupid&digest=$fdgroupid"
                        ];
                    }
                }

                if (count($replacements) > 0) {
                    error_log("#$groupid {$gatts['nameshort']} to " . count($replacements) . " users");

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

                    foreach ($replacements as $email => $rep) {
                        $message = Swift_Message::newInstance()
                            ->setSubject($tosend['subject'])
                            ->setFrom([$tosend['from'] => $tosend['fromname']])
                            ->setReturnPath('bounce@direct.ilovefreegle.org')
                            ->setReplyTo($tosend['replyto'], $tosend['replytoname'])
                            ->setBody($tosend['text'])
                            ->addPart($tosend['html'], 'text/html');

                        $headers = $message->getHeaders();
                        $headers->addTextHeader('List-Unsubscribe', '<mailto:{{eventsoff}}>, <{{unsubscribe}}>');

                        try {
                            $message->setTo([ $email => $rep['{{toname}}'] ]);
                            #error_log("...$email");
                            $this->sendOne($mailer, $message);
                            $sent++;
                        } catch (Exception $e) {
                            error_log($email . " skipped with " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->dbhm->preExec("UPDATE groups SET lasteventsroundup = NOW() WHERE id = ?;", [ $groupid ]);
        Group::clearCache($groupid);

        return($sent);
    }
}