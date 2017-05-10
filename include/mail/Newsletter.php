<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/newsletter.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/newsletterarticle.php');
require_once(IZNIK_BASE . '/mailtemplates/digest/newslettersoff.php');

class Newsletter extends Entity
{
    var $log, $newsletter;

    const TYPE_HEADER = 'Header';
    const TYPE_ARTICLE = 'Article';

    public $publicatts = [ 'id', 'groupid', 'subject', 'textbody', 'created', 'completed', 'uptouser' ];
    public $settableatts = [ 'groupid', 'subject', 'textbody' ];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'newsletters', 'newsletter', $this->publicatts);
        $this->log = new Log($this->dbhr, $this->dbhm);
    }

    public function create($groupid, $subject, $textbody) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO newsletters (`groupid`, `subject`, `textbody`) VALUES (?,?,?);", [
            $groupid, $subject, $textbody
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->fetch($this->dbhr, $this->dbhm, $id, 'newsletters', 'newsletter', $this->publicatts);
        }

        return($id);
    }

    public function addArticle($type, $position, $html, $photo) {
        $id = NULL;
        $rc = $this->dbhm->preExec("INSERT INTO newsletters_articles (newsletterid, type, position, html, photoid) VALUES (?,?,?,?,?);", [
            $this->id,
            $type,
            $position,
            $html,
            $photo
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
        }

        return($id);
    }

    # Split out for UT to override
    public function sendOne($mailer, $message) {
        $mailer->send($message);
    }

    public function off($uid) {
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->setPrivate('newslettersallowed', 0);

        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_NEWSLETTERSOFF,
            'user' => $uid
        ]);

        $email = $u->getEmailPreferred();
        if ($email) {
            list ($transport, $mailer) = getMailer();
            $html = newsletters_off(USER_SITE, USERLOGO);

            $message = Swift_Message::newInstance()
                ->setSubject("Email Change Confirmation")
                ->setFrom([NOREPLY_ADDR => SITE_NAME])
                ->setReturnPath($u->getBounce())
                ->setTo([ $email => $u->getName() ])
                ->setBody("We've turned your newsletters off.")
                ->addPart($html, 'text/html');

            $this->sendOne($mailer, $message);
        }
    }

    public function send($groupid, $uid = NULL, $grouptype = Group::GROUP_FREEGLE) {
        # This might be to a specific group or all groups, so the mail we construct varies a bit based on that.
        $g = NULL;
        $gatts = NULL;
        if ($groupid) {
            $g = Group::get($this->dbhr, $this->dbhm, $groupid);
            $gatts = $g->getPublic();
        }

        $sent = 0;
        $html = '';

        $articles = $this->dbhr->preQuery("SELECT * FROM newsletters_articles WHERE newsletterid = ? ORDER BY position ASC;", [ $this->id ]);

        foreach ($articles as &$article) {
            $photo = pres('photoid', $article);

            if ($photo) {
                $a = new Attachment($this->dbhr, $this->dbhm, $photo, Attachment::TYPE_NEWSLETTER);
                $article['photo'] = $a->getPublic();

                # We want a 250px wide image
                $article['photo']['path'] .= '?w=250';
            }

            $html .= newsletter_article($article);
        }

        $tosend = [
            'subject' => $this->newsletter['subject'],
            'from' => $g ? $g->getAutoEmail() : NOREPLY_ADDR,
            'replyto' => $g ? $g->getModsEmail() : NOREPLY_ADDR,
            'fromname' => $g ? $gatts['namedisplay'] : SITE_NAME,
            'html' => newsletter(USER_SITE, SITE_NAME, $html),
            'text' => $this->newsletter['textbody']
        ];

        # Now find the users that we want to send to:
        # - an override to a single user
        # - users on a group
        # - all users on a group type where the group hasn't disabled newsletters
        $startfrom = presdef('uptouser', $this->newsletter, 0);
        $sql = $uid ? "SELECT DISTINCT userid FROM memberships WHERE userid = $uid;" : ($groupid ? "SELECT DISTINCT userid FROM memberships INNER JOIN users ON users.id = memberships.userid WHERE groupid = $groupid AND newslettersallowed = 1 AND userid > $startfrom ORDER BY userid ASC;" : "SELECT DISTINCT userid FROM users INNER JOIN memberships ON memberships.userid = users.id INNER JOIN groups ON groups.id = memberships.groupid AND type = '$grouptype' WHERE LOCATE('\"newsletter\":0', groups.settings) = 0 AND newslettersallowed = 1 AND users.id > $startfrom ORDER BY users.id ASC;");
        $replacements = [];

        error_log("Query for users");
        $users = $this->dbhr->preQuery($sql);
        error_log("Queried, now scan " . count($users));
        $scan = 0;

        foreach ($users as $user) {
            $u = User::get($this->dbhr, $this->dbhm, $user['userid']);

            if (!$u->getPrivate('bouncing')) {
                # We are only interested in sending events to users for whom we have a preferred address -
                # otherwise where would we send them?
                $email = $u->getEmailPreferred();

                if ($email) {
                    # TODO These are the replacements for the mails sent before FDv2 is retired.  These will change.
                    $replacements[$email] = [
                        '{{id}}' => $user['userid'],
                        '{{toname}}' => $u->getName(),
                        '{{unsubscribe}}' => $u->loginLink(USER_SITE, $u->getId(), '/unsubscribe', User::SRC_NEWSLETTER),
                        '{{email}}' => $email,
                        '{{noemail}}' => 'newslettersoff-' . $user['userid'] . "@" . USER_DOMAIN
                    ];
                }
            }

            if ($scan % 1000 === 0) {
                $pc = round(100 * $scan / count($users));
                error_log("...$scan ($pc%)");
            }

            $scan++;
        }

        if (count($replacements) > 0) {
            # Now send.  We use a failover transport so that if we fail to send, we'll queue it for later
            # rather than lose it.
            /* @var Swift_MailTransport $transport
             * @var Swift_Mailer $mailer */
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
                $bounce = "bounce-{$rep['{{id}}']}-" . time() . "@" . USER_DOMAIN;
                $message = Swift_Message::newInstance()
                    ->setSubject($tosend['subject'])
                    ->setFrom([$tosend['from'] => $tosend['fromname']])
                    ->setReplyTo($tosend['replyto'], $tosend['fromname'])
                    ->setReturnPath($bounce)
                    ->setBody($tosend['text'])
                    ->addPart($tosend['html'], 'text/html');

                $headers = $message->getHeaders();
                $headers->addTextHeader('List-Unsubscribe', '<mailto:' . $rep['{{noemail}}'] . '>, <' . $rep['{{unsubscribe}}'] . '>');

                try {
                    $message->addTo($email);
                    $this->sendOne($mailer, $message);

                    if ($sent % 1000 === 0) {
                        $pc = round(100 * $sent / count($replacements));
                        error_log("...$sent ($pc%)");

                        if (!$uid) {
                            # Save where we're upto so that if we crash or restart we don't duplicate for too many
                            # users.
                            $this->dbhm->preExec("UPDATE newsletters SET uptouser = ? WHERE id = ?;", [
                                $rep['{{id}}'],
                                $this->id
                            ]);
                        }
                    }

                    $sent++;

                    if ($sent % 7 === 0) {
                        # This is set so that sending a newsletter takes several days, to avoid disrupting our
                        # normal mailing by flooding the system with these mails.
                        sleep(1);
                    }
                } catch (Exception $e) {
                    error_log($email . " skipped with " . $e->getMessage());
                }
            }
        }

        if (!$uid) {
            $this->dbhm->preExec("UPDATE newsletters SET completed = NOW() WHERE id = ?;", [ $this->id ]);
        }

        error_log("Returning $sent");
        return($sent);
    }
}