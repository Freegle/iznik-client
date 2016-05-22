<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');
require_once(IZNIK_BASE . '/mailtemplates/alert.php');

class Alert extends Entity
{
    const MODS = 'Mods';
    const USERS = 'Users';
    
    const TYPE_MODEMAIL = 'ModEmail';
    const TYPE_OWNEREMAIL = 'OwnerEmail';

    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'groupid', 'from', 'to', 'created', 'groupprogress', 'complete', 'subject',
        'text', 'html');

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'alerts', 'alert', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    public function create($groupid, $from, $to, $subject, $text, $html) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO alerts (`groupid`, `from`, `to`, `subject`, `text`, `html`) VALUES (?,?,?,?,?,?);", [
            $groupid, $from, $to, $subject, $text, $html
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->fetch($this->dbhr, $this->dbhm, $id, 'alerts', 'alert', $this->publicatts);
        }

        return($id);
    }

    public function getList() {
        $sql = "SELECT id FROM alerts ORDER BY id DESC;";
        $alerts = $this->dbhr->preQuery($sql);
        $ret = [];
        foreach ($alerts as $alert) {
            $a = new Alert($this->dbhr, $this->dbhm, $alert['id']);
            $thisone = $a->getPublic();
            $thisone['created'] = ISODate($thisone['created']);
            $thisone['complete'] = ISODate($thisone['complete']);
            $thisone['stats'] = $a->getStats();
            $ret[] = $thisone;
        }

        return($ret);
    }

    public function constructMessage($to, $toname, $from, $subject, $text, $html) {
        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom([$from])
            ->setTo([$to => $toname])
            #->setTo(['log@ehibbert.org.uk'])
            ->setBody($text)
            ->addPart($html, 'text/html');

        return($message);
    }
    
    public function beacon($id) {
        $this->dbhm->preExec("UPDATE alerts_tracking SET responded = NOW(), response = 'Read' WHERE id = ?;", [ $id] );
    }

    public function clicked($id) {
        $this->dbhm->preExec("UPDATE alerts_tracking SET responded = NOW(), response = 'Clicked' WHERE id = ?;", [ $id] );
    }

    public function process($id = NULL) {
        $done = 0;
        $idq = $id ? " id = $id AND " : '';
        $sql = "SELECT * FROM alerts WHERE $idq complete IS NULL;";
        $alerts = $this->dbhr->preQuery($sql);

        foreach ($alerts as $alert) {
            $a = new Alert($this->dbhr, $this->dbhm, $alert['id']);

            # This alert might be for a specific group, or all Freegle groups.  We only process a single group in this
            # pass.  If it's for multiple, we'll update the progress and do the next one next time.
            $groupid = $a->getPrivate('groupid');
            $groupq =  $groupid ? " WHERE id = $groupid " : (" WHERE `type` = '" . Group::GROUP_FREEGLE . "' AND id > {$alert['groupprogress']} LIMIT 1");

            $groups = $this->dbhr->preQuery("SELECT id, nameshort FROM groups $groupq;");
            $complete = count($groups) == 0;
            error_log("Count " . count($groups) . " done $done");

            foreach ($groups as $group) {
                error_log("...{$alert['id']} -> {$group['nameshort']}");
                $done += $a->mailMods($group['id']);

                if ($groupid) {
                    # This is to a specific group.  We are now done.
                    error_log("Specific group $groupid");
                    $complete = TRUE;
                } else {
                    # This is for multiple groups.
                    $this->dbhm->preExec("UPDATE alerts SET groupprogress = ? WHERE id = ?;", [
                        $group['id'],
                        $alert['id']
                    ]);
                }
            }

            if ($complete) {
                error_log("Done");
                $this->dbhm->preExec("UPDATE alerts SET complete = NOW() WHERE id = ?;", [ $alert['id'] ]);
            }
        }

        return($done);
    }

    public function getFrom() {
        $from = NULL;
        
        switch ($this->alert['from']) {
            case 'support': $from = SUPPORT_ADDR; break;
            case 'info': $from = INFO_ADDR; break;
            case 'geeks': $from = GEEKS_ADDR; break;
            case 'board': $from = BOARD_ADDR; break;
            case 'mentors': $from = MENTORS_ADDR; break;
            case 'newgroups': $from = NEWGROUPS_ADDR; break;
        }
        # TODO This dom
        return($from);
    }

    public function getStats() {
        $ret = [
            'sent' => [],
            'responses' => [
                'groups' => [],
                'mods' => [],
                'modemails' => [],
                'owner' => []
            ]
        ];

        $ret['sent']['mods'] = count($this->dbhr->preQuery("SELECT DISTINCT userid FROM alerts_tracking WHERE alertid = ? AND `type` = ?;", [
            $this->id,
            Alert::TYPE_MODEMAIL
        ]));

        $ret['sent']['modemails'] = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM alerts_tracking WHERE alertid = ? AND `type` = ?;", [ 
            $this->id,
            Alert::TYPE_MODEMAIL
        ])[0]['count'];
        
        $ret['sent']['owneremails'] = $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM alerts_tracking WHERE alertid = ? AND `type` = ?;", [
            $this->id,
            Alert::TYPE_OWNEREMAIL
        ])[0]['count'];
        
        $ret['responses']['mods']['none'] = count($this->dbhr->preQuery("SELECT DISTINCT userid FROM alerts_tracking WHERE alertid = ? AND `type` = ? AND response IS NULL;", [
            $this->id,
            Alert::TYPE_MODEMAIL
        ]));

        $ret['responses']['mods']['reached'] = count($this->dbhr->preQuery("SELECT DISTINCT userid FROM alerts_tracking WHERE alertid = ? AND `type` = ? AND response IN ('Read', 'Clicked');", [
            $this->id,
            Alert::TYPE_MODEMAIL
        ]));

        # Owner.  It looks like Yahoo fetches our beacon, so only count clicks.
        $ret['responses']['owner']['none'] = count($this->dbhr->preQuery("SELECT DISTINCT userid FROM alerts_tracking WHERE alertid = ? AND `type` = ? AND (response IS NULL OR response = 'Read');", [
            $this->id,
            Alert::TYPE_OWNEREMAIL
        ]));

        $ret['responses']['owner']['reached'] = count($this->dbhr->preQuery("SELECT DISTINCT userid FROM alerts_tracking WHERE alertid = ? AND `type` = ? AND response IN ('Clicked');", [
            $this->id,
            Alert::TYPE_OWNEREMAIL
        ]));

        $sql = "SELECT DISTINCT groupid FROM alerts_tracking WHERE alertid = ?;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id ]);

        foreach ($groups as $group) {
            # We have reached a group if we've had a click on an owner, or a read/click on a mod.
            $sql = "SELECT COUNT(*) AS count, CASE WHEN ((response = 'Clicked') OR (response = 'Read' AND `type` = 'ModEmail')) THEN 'Reached' ELSE 'None' END AS rsp FROM alerts_tracking WHERE alertid = ? AND groupid = ? GROUP BY rsp;";
            $data = $this->dbhr->preQuery($sql, [ $this->id, $group['groupid'] ]);
            $g = new Group($this->dbhr, $this->dbhm, $group['groupid']);
            $ret['responses']['groups'][] = [
                'group' => $g->getPublic(),
                'summary' => $data
            ];
        }

        return($ret);
    }

    public function mailMods($groupid) {
        $transport = Swift_SmtpTransport::newInstance();
        $mailer = Swift_Mailer::newInstance($transport);
        $done = 0;

        # Mail the mods individually
        $g = new Group($this->dbhr, $this->dbhm, $groupid);

        $sql = "SELECT userid FROM memberships WHERE groupid = ? AND role IN ('Owner', 'Moderator');";
        $mods = $this->dbhr->preQuery($sql, [ $groupid ]);
        $from = $this->getFrom();

        foreach ($mods as $mod) {
            $u = new User($this->dbhr, $this->dbhm, $mod['userid']);

            $emails = $u->getEmails();
            foreach ($emails as $email) {
                if (realEmail($email['email'])) {
                    $this->dbhm->preExec("INSERT INTO alerts_tracking (alertid, groupid, userid, emailid, `type`) VALUES (?,?,?,?,?);",
                        [
                            $this->id,
                            $groupid,
                            $mod['userid'],
                            $email['id'],
                            Alert::TYPE_MODEMAIL
                        ]
                    );
                    $trackid = $this->dbhm->lastInsertId();
                    $html = alert_tpl(
                        $u->getName(),
                        USER_SITE, 
                        USERLOGO, 
                        $this->alert['subject'], 
                        $this->alert['html'], 
                        NULL, # Should be $u->getUnsubLink(USER_SITE, $mod['userid']) once we go live TODO ,
                        'https://' . USER_SITE . "/alert/viewed/$trackid",
                        'https://' . USER_SITE . "/beacon/$trackid");
                    $msg = $this->constructMessage($email['email'], $u->getName(), $from, $this->alert['subject'], $this->alert['text'], $html);
                    $mailer->send($msg);
                    $done++;
                }
            }
        }

        if ($g->getPrivate('onyahoo')) {
            # This group is on Yahoo - so mail the owner address too.
            $this->dbhm->preExec("INSERT INTO alerts_tracking (alertid, groupid, `type`) VALUES (?,?,?);",
                [
                    $this->id,
                    $groupid,
                    Alert::TYPE_OWNEREMAIL
                ]
            );

            $trackid = $this->dbhm->lastInsertId();
            $toname = $g->getPrivate('nameshort') . " volunteers";
            $html = alert_tpl(
                $toname,
                USER_SITE,
                USERLOGO,
                $this->alert['subject'],
                $this->alert['html'],
                NULL,
                'https://' . USER_SITE . "/alert/viewed/$trackid",
                'https://' . USER_SITE . "/beacon/$trackid");

            $msg = $this->constructMessage($g->getModsEmail(), $toname, $from, $this->alert['subject'], $this->alert['text'], $html);
            $mailer->send($msg);
            $done++;
        }

        return($done);
    }
}

