<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');
require_once(IZNIK_BASE . '/mailtemplates/admin.php');

class Admin extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'groupid', 'created', 'complete', 'subject', 'text');

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'admins', 'admin', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    public function create($groupid, $createdby, $subject, $text) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO admins (`groupid`, `createdby`, `subject`, `text`) VALUES (?,?,?,?);", [
            $groupid, $createdby, $subject, $text
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->fetch($this->dbhr, $this->dbhm, $id, 'admins', 'admin', $this->publicatts);
        }

        return($id);
    }

    public function constructMessage($groupname, $to, $toname, $from, $subject, $text) {
        $post = "https://direct.ilovefreegle.org/login.php?action=post";
        $unsubscribe = 'https://direct.ilovefreegle.org/unsubscribe.php?email=' . urlencode($to);

        # TODO Change to per group link.
        $visit = "https://direct.ilovefreegle.org";

        $html = admin_tpl($groupname, $toname, $to, USER_DOMAIN, USERLOGO, $subject, $text, $post, $unsubscribe, $visit);
        $message = Swift_Message::newInstance()
            ->setSubject("ADMIN: $subject")
            ->setFrom([$from => "$groupname Volunteers" ])
            ->setTo([$to => $toname])
            ->setBody($text)
            ->addPart($html, 'text/html');

        return($message);
    }

    public function process($id = NULL) {
        $done = 0;
        $idq = $id ? " id = $id AND " : '';
        $sql = "SELECT * FROM admins WHERE $idq complete IS NULL;";
        $admins = $this->dbhr->preQuery($sql);

        foreach ($admins as $admin) {
            $a = new Admin($this->dbhr, $this->dbhm, $admin['id']);
            $done += $a->mailMembers();
            $this->dbhm->preExec("UPDATE admins SET complete = NOW() WHERE id = ?;", [ $admin['id'] ]);
        }

        return($done);
    }

    public function mailMembers() {
        list ($transport, $mailer) = getMailer();
        $done = 0;
        $groupid = $this->admin['groupid'];

        $g = Group::get($this->dbhr, $this->dbhm, $groupid);
        $atts = $g->getPublic();
        $groupname = $atts['namedisplay'];
        $onyahoo = $atts['onyahoo'];

        $sql = "SELECT userid FROM memberships WHERE groupid = ?;";
        $members = $this->dbhr->preQuery($sql, [ $groupid ]);

        foreach ($members as $member) {
            $u = User::get($this->dbhr, $this->dbhm, $member['userid']);
            $preferred = $u->getEmailPreferred();

            # We send to members who have joined via our platform, or to all users if we host the group.
            list ($eid, $ouremail) = $u->getEmailForYahooGroup($groupid, TRUE);

            if ($preferred && ($ouremail || !$onyahoo)) {
                try {
                    $msg = $this->constructMessage($groupname, $preferred, $u->getName(), $g->getModsEmail(), $this->admin['subject'], nl2br($this->admin['text']));
                    $mailer->send($msg);
                    $done++;
                } catch (Exception $e) {
                    error_log("Failed with " . $e->getMessage());
                }
            }
        }

        return($done);
    }
}

