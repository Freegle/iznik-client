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

    public $publicatts = [ 'id', 'groupid', 'subject', 'textbody', 'created', 'completed', 'photoid'];
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

    public function off($uid, $groupid) {
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->setPrivate('newslettersallowed', 0);

        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_NEWSLETTERSOFF,
            'userid' => $uid,
            'groupid' => $groupid
        ]);

        $email = $u->getEmailPreferred();
        if ($email) {
            list ($transport, $mailer) = getMailer();
            $html = newsletters_off(USER_DOMAIN, USERLOGO);

            $message = Swift_Message::newInstance()
                ->setSubject("Email Change Confirmation")
                ->setFrom([NOREPLY_ADDR => 'Do Not Reply'])
                ->setReturnPath('bounce@direct.ilovefreegle.org')
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
            $g = new Group($this->dbhr, $this->dbhm, $groupid);
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
            'from' => $g ? $g->getModsEmail() : NOREPLY_ADDR,
            'fromname' => $g ? $gatts['namedisplay'] : SITE_NAME,
            'html' => newsletter(SITE_NAME, $html),
            'text' => $this->newsletter['textbody']
        ];

        # Now find the users that we want to send to:
        # - an override to a single user
        # - users on a group
        # - all users on a group type.
        $sql = $uid ? "SELECT DISTINCT userid FROM memberships WHERE userid = $uid;" : ($groupid ? "SELECT userid FROM memberships INNER JOIN users ON users.id = memberships.userid WHERE groupid = $groupid AND newslettersallowed = 1 ORDER BY userid ASC;" : "SELECT DISTINCT userid FROM users INNER JOIN memberships ON memberships.userid = users.id INNER JOIN groups ON groups.id = memberships.groupid AND type = '$grouptype' WHERE newslettersallowed = 1 ORDER BY id ASC;");
        $replacements = [];

        $users = $this->dbhr->preQuery($sql);

        foreach ($users as $user) {
            $u = new User($this->dbhr, $this->dbhm, $user['userid']);

            # We are only interested in sending events to users for whom we have a preferred address -
            # otherwise where would we send them?
            $email = $u->getEmailPreferred();

            if ($email) {
                # TODO These are the replacements for the mails sent before FDv2 is retired.  These will change.
                $replacements[$email] = [
                    '{{toname}}' => $u->getName(),
                    '{{unsubscribe}}' => 'https://direct.ilovefreegle.org/unsubscribe.php?email=' . urlencode($email),
                    '{{email}}' => $email,
                    '{{noemail}}' => 'newslettersoff-' . $user['userid'] . "@" . USER_DOMAIN
                ];
            }
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
                $message = Swift_Message::newInstance()
                    ->setSubject($tosend['subject'])
                    ->setFrom([$tosend['from'] => $tosend['fromname']])
                    ->setReturnPath('bounce@direct.ilovefreegle.org')
                    ->setBody($tosend['text'])
                    ->addPart($tosend['html'], 'text/html');

                $headers = $message->getHeaders();
                $headers->addTextHeader('List-Unsubscribe', '<mailto:{{newslettersoff}}>, <{{unsubscribe}}>');

                try {
                    $message->addTo($email);
                    $this->sendOne($mailer, $message);
                    $sent++;
                } catch (Exception $e) {
                    error_log($email . " skipped with " . $e->getMessage());
                }
            }
        }

        $this->dbhm->preExec("UPDATE newsletters SET completed = NOW() WHERE id = ?;", [ $this->id ]);

        error_log("Returning $sent");
        return($sent);
    }
}