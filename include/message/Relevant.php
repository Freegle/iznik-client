<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Search.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/user/User.php');

# Find messages relevant to users which they might have missed, and mail them to them.
class Relevant {
    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function interestedIn($userid, $grouptype = Group::GROUP_FREEGLE) {
        # We have two sources:
        # - outstanding posts by the user, which might be either OFFERs or WANTEDs, where we want to look for the
        #   relevant WANTEDs or OFFERs respectively.
        # - the searches by the user, where we want to look for relevant OFFERs.
        $interested = [];

        # First the messages.
        $sql = "SELECT DISTINCT messages.type, messages.subject FROM messages LEFT OUTER JOIN messages_related ON id1 = fromuser OR id2 = fromuser INNER JOIN messages_groups ON messages_groups.msgid = messages.id AND collection = 'Approved' INNER JOIN groups ON groups.id = messages_groups.groupid AND groups.type = ? WHERE id1 IS NULL AND fromuser = ? AND messages.type IN ('Offer', 'Wanted');";
        $msgs = $this->dbhr->preQuery($sql, [ $grouptype, $userid ] );
        foreach ($msgs as $msg) {
            # We only bother with messages with standard subject line formats.
            if (preg_match("/(.+)\:(.+)\((.+)\)/", $msg['subject'], $matches)) {
                $item = trim($matches[2]);
                $interested[] = [
                    'type' => $msg['type'],
                    'item' => $item
                ];
            }
        }

        # Now the searches.
        $sql = "SELECT * FROM users_searches WHERE userid = ? AND deleted = 0 AND locationid IS NOT NULL;";
        $searches = $this->dbhr->preQuery($sql, [ $userid ]);

        foreach ($searches as $search) {
            $interested[] = [
                'type' => Message::TYPE_WANTED,
                'item' => $search['term']
            ];
        }

        return($interested);
    }

    public function getMessages($userid, $interesteds) {
        $ret = [];
        $ids = [];

        # We want to search in the groups near the last location we have for this user.
        $u = new User($this->dbhr, $this->dbhm, $userid);
        $lastloc = $u->getPrivate('lastlocation');

        # We are interested in messages since the last check - or if this is the first, fairly recent ones.
        $start = $u->getPrivate('lastrelevantcheck');
        $start = $start ? strtotime($start) : strtotime("3 days ago");

        if ($lastloc) {
            $l = new Location($this->dbhr, $this->dbhm, $lastloc);
            $groups = $l->groupsNear();
            #error_log("Groups near $lastloc are " . var_export($groups, TRUE));

            if (count($groups) > 0) {
                foreach ($interesteds as $interested) {
                    $s = new Search($this->dbhr, $this->dbhm, 'messages_index', 'msgid', 'arrival', 'words', 'groupid', $start);
                    $ctx = NULL;
                    $res = $s->search($interested['item'], $ctx, 1, NULL, $groups);
                    #error_log("Search for {$interested['item']} returned " . var_export($res, TRUE));

                    foreach ($res as $r) {
                        if (!in_array($r['id'], $ids)) {
                            # We have a message - see if it's the type we want.
                            $m = new Message($this->dbhr, $this->dbhm, $r['id']);
                            $type = $m->getType();
                            if (($interested['type'] == Message::TYPE_OFFER && $type == Message::TYPE_WANTED) ||
                                ($interested['type'] == Message::TYPE_WANTED && $type == Message::TYPE_OFFER)) {
                                #error_log("Found {$r['id']} " . $m->getSubject());
                                $ret[] = [
                                    'id' => $r['id'],
                                    'term' => $interested['item']
                                ];

                                $ids[] = $r['id'];
                            }
                        }
                    }
                }
            }
        }

        return($ret);
    }

    public function recordCheck($userid) {
        $this->dbhm->preExec("UPDATE users SET lastrelevantcheck = NOW() WHERE id = ?;", [ $userid ]);
    }

    # Split out for UT to override
    public function sendOne($mailer, $message) {
        $mailer->send($message);
    }

    public function sendMessages($userid = NULL) {
        list ($transport, $mailer) = getMailer();

        # TODO until we migrate over, we need to link to the old site, so we need the old group id.
        global $dbconfig;
        $dsn = "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname=republisher;charset=utf8";

        $dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => FALSE
        ));

        $sql = $userid ? "SELECT id FROM users WHERE id = $userid;" : "SELECT id FROM users;";
        $users = $this->dbhr->preQuery($sql);

        foreach ($users as $user) {
            $u = new User($this->dbhr, $this->dbhm, $user['id']);
            $ints = $this->interestedIn($user['id']);
            $msgs = $this->getMessages($user['id'], $ints);

            if (count($msgs) > 0) {
                $textbody = "Based on what you've offered or searched for, we thought you might be interested in these recent messages.\r\n";
                $offers = [];
                $wanteds = [];

                foreach ($msgs as $msg) {
                    $m = new Message($this->dbhr, $this->dbhm, $msg['id']);

                    # We need the approved ID on Yahoo for migration links.
                    # TODO remove in time.
                    $href = NULL;
                    $atts = $m->getPublic(FALSE, FALSE, TRUE);
                    $groups = $atts['groups'];
                    foreach ($groups as $group) {
                        $g = new Group($this->dbhr, $this->dbhm, $group['groupid']);
                        $gatts = $g->getPublic();

                        $sql = "SELECT groupid FROM groups WHERE groupname = " . $dbhold->quote($gatts['nameshort']) . ";";
                        $fdgroupid = NULL;
                        $fdgroups = $dbhold->query($sql);
                        foreach ($fdgroups as $fdgroup) {
                            $fdgroupid = $fdgroup['groupid'];
                        }

                        $href = "https://direct.ilovefreegle.org/login.php?action=mygroups&subaction=displaypost&msgid={$group['yahooapprovedid']}&groupid=$fdgroupid&digest=$fdgroupid";
                    }

                    if ($href) {
                        $subject = $m->getSubject();
                        $subject = preg_replace('/\[.*?\]\s*/', '', $subject);

                        if ($m->getType() == Message::TYPE_OFFER) {
                            $offers[] = "$subject - see $href\r\n";
                        } else {
                            $wanteds[] = "$subject - see $href\r\n";
                        }
                    }
                }

                $textbody .= count($offers) > 0 ? ("\r\nThings people are giving away which you might want:\r\n\r\n" . implode('', $offers)) : '';
                $textbody .= count($wanteds) > 0 ? ("\r\nThings people are looking for which you might have:\r\n\r\n" . implode('', $wanteds)) : '';

                $email = $u->getEmailPreferred();
                if ($email) {
                    $message = Swift_Message::newInstance()
                        ->setSubject("You might be interested in these messages...")
                        ->setFrom([NOREPLY_ADDR => SITE_NAME ])
                        ->setReturnPath('bounce@direct.ilovefreegle.org')
                        #->setTo([ $email => $u->getName() ])
                        ->setTo([ 'log@ehibbert.org.uk' => $u->getName() ])
                        ->setBody($textbody);
//                        ->addPart($html, 'text/html');

                    $this->sendOne($mailer, $message);
                    exit(0);
                }
            }
        }
    }
}