<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

use GeoIp2\Database\Reader;

class Spam {
    CONST TYPE_SPAMMER = 'Spammer';
    CONST TYPE_WHITELIST = 'Whitelisted';
    CONST TYPE_PENDING_ADD = 'PendingAdd';
    CONST TYPE_PENDING_REMOVE = 'PendingRemove';

    CONST USER_THRESHOLD = 10;
    CONST GROUP_THRESHOLD = 20;
    CONST SUBJECT_THRESHOLD = 30;  // SUBJECT_THRESHOLD must be > GROUP_THRESHOLD for UT

    # For checking users as suspect.
    CONST SEEN_THRESHOLD = 16; // Number of groups to join or apply to before considered suspect
    CONST ESCALATE_THRESHOLD = 2; // Level of suspicion before a user is escalated to support/admin for review

    CONST REASON_COUNTRY_BLOCKED = 'CountryBlocked';
    CONST REASON_IP_USED_FOR_DIFFERENT_USERS = 'IPUsedForDifferentUsers';
    CONST REASON_IP_USED_FOR_DIFFERENT_GROUPS = 'IPUsedForDifferentGroups';
    CONST REASON_SUBJECT_USED_FOR_DIFFERENT_GROUPS = 'SubjectUsedForDifferentGroups';
    CONST REASON_SPAMASSASSIN = 'SpamAssassin';

    /** @var  $dbhr LoggedPDO */
    private $dbhr;

    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $reader;

    function __construct($dbhr, $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
        $this->log = new Log($this->dbhr, $this->dbhm);
    }

    public function check(Message $msg) {
        $ip = $msg->getFromIP();
        $host = NULL;

        if ($ip) {
            $host = $msg->getFromhost();
            if (preg_match('/mail.*yahoo\.com/', $host)) {
                # Posts submitted by email to Yahoo show up with an X-Originating-IP of one of Yahoo's MTAs.  We don't
                # want to consider those as spammers.
                $ip = NULL;
                $msg->setFromIP($ip);
            } else {
                # Check if it's whitelisted
                $sql = "SELECT * FROM spam_whitelist_ips WHERE ip = ?;";
                $ips = $this->dbhr->preQuery($sql, [$ip]);
                foreach ($ips as $wip) {
                    $ip = NULL;
                    $msg->setFromIP($ip);
                }
            }
        }

        if ($ip) {
            # We have an IP, we reckon.  It's unlikely that someone would fake an IP which gave a spammer match, so
            # we don't have to worry too much about false positives.
            try {
                $record = $this->reader->country($ip);
                $country = $record->country->name;
                $msg->setPrivate('fromcountry', $record->country->isoCode);
            } catch (Exception $e) {
                # Failed to look it up.
                $country = NULL;
            }

            # Now see if we're blocking all mails from that country.  This is legitimate if our service is for a
            # single country and we are vanishingly unlikely to get legitimate emails from certain others.
            $countries = $this->dbhr->preQuery("SELECT * FROM spam_countries WHERE country LIKE ?;", [$country]);
            foreach ($countries as $country) {
                # Gotcha.
                return(array(true, Spam::REASON_COUNTRY_BLOCKED, "Blocking IP $ip as it's in {$country['country']}"));
            }

            # Now see if this IP has been used for too many different users.  That is likely to
            # be someone masquerading to fool people.
            #
            # Should check address, but we don't yet have the canonical address so will be fooled by FBUser
            # TODO
            $sql = "SELECT fromname FROM messages_history WHERE fromip = ? GROUP BY fromname;";
            $users = $this->dbhr->preQuery($sql, [$ip]);
            $numusers = count($users);

            if ($numusers > Spam::USER_THRESHOLD) {
                $list = [];
                foreach ($users as $user) {
                    $list[] = $user['fromname'];
                }
                return(array(true, Spam::REASON_IP_USED_FOR_DIFFERENT_USERS, "IP $ip " . ($host ? "($host)" : "") . " recently used for $numusers different users (" . implode(', ', $list) . ")"));
            }

            # Now see if this IP has been used for too many different groups.  That's likely to
            # be someone spamming.
            $sql = "SELECT groups.nameshort FROM messages_history INNER JOIN groups ON groups.id = messages_history.groupid WHERE fromip = ? GROUP BY groupid;";
            $groups = $this->dbhr->preQuery($sql, [$ip]);
            $numgroups = count($groups);

            if ($numgroups >= Spam::GROUP_THRESHOLD) {
                $list = [];
                foreach ($groups as $group) {
                    $list[] = $group['nameshort'];
                }
                return(array(true, Spam::REASON_IP_USED_FOR_DIFFERENT_GROUPS, "IP $ip ($host) recently used for $numgroups different groups (" . implode(', ', $list) . ")"));
            }
        }

        # Now check whether this subject (pace any location) is appearing on many groups.
        #
        # Don't check very short subjects - might be something like "TAKEN".
        $subj = $msg->getPrunedSubject();

        if (strlen($subj) >= 10) {
            $sql = "SELECT COUNT(DISTINCT groupid) AS count FROM messages_history WHERE prunedsubject LIKE ? AND groupid IS NOT NULL;";
            $counts = $this->dbhr->preQuery($sql, [
                "$subj%"
            ]);

            foreach ($counts as $count) {
                if ($count['count'] >= Spam::SUBJECT_THRESHOLD) {
                    # Possible spam subject - but check against our whitelist.
                    $found = FALSE;
                    $sql = "SELECT id FROM spam_whitelist_subjects WHERE subject LIKE ?;";
                    $whites = $this->dbhr->preQuery($sql, [$subj]);
                    foreach ($whites as $white) {
                        $found = TRUE;
                    }

                    if (!$found) {
                        return (array(true, Spam::REASON_SUBJECT_USED_FOR_DIFFERENT_GROUPS, "Warning - subject $subj recently used on {$count['count']} groups"));
                    }
                }
            }
        }

        # It's fine.  So far as we know.
        return(NULL);
    }

    public function notSpamSubject($subj) {
        $sql = "INSERT IGNORE INTO spam_whitelist_subjects (subject, comment) VALUES (?, 'Marked as not spam');";
        $this->dbhm->preExec($sql, [ $subj ]);
    }

    public function checkUser($userid) {
        # Called when something has happened to a user which makes them more likely to be a spammer, and therefore
        # needs rechecking.
        $me = whoAmI($this->dbhr, $this->dbhm);

        $suspect = FALSE;
        $reason = NULL;

        # Check whether they have applied to a suspicious number of groups, but exclude whitelisted members.
        $sql = "SELECT COUNT(DISTINCT(groupid)) AS count FROM memberships  LEFT JOIN spam_users ON spam_users.userid = memberships.userid AND spam_users.collection = 'Whitelisted' WHERE memberships.userid = ? AND spam_users.userid IS NULL;";
        $counts = $this->dbhr->preQuery($sql, [ $userid ]);

        if ($counts[0]['count'] > Spam::SEEN_THRESHOLD) {
            $suspect = TRUE;
            $reason = "Seen on many groups";
        }

        if ($suspect) {
            # This user is suspect.  We will mark it as so, which means that it'll show up to mods on relevant groups,
            # and they will review it.
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_SUSPECT,
                'byuser' => $me ? $me->getId() : NULL,
                'user' => $userid,
                'text' => $reason
            ]);

            $this->dbhm->preExec("UPDATE users SET suspectcount = suspectcount + 1, suspectreason = ? WHERE id = ?;",
                [
                    $reason,
                    $userid
                ]);
        }
    }

    public function workCount() {
        $count = 0;

        $me = whoAmI($this->dbhr, $this->dbhm);

        if ($me) {
            $sql = "SELECT COUNT(DISTINCT(users.id)) AS count FROM users INNER JOIN memberships ON users.suspectcount > 0 AND users.id = memberships.userid AND memberships.groupid IN (SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Moderator', 'Owner'));";
            $counts = $this->dbhr->preQuery($sql, [ $me->getId() ]);
            $count = $counts[0]['count'];
        }

        return($count);
    }

    public function collectionCount($collection) {
        $sql = "SELECT COUNT(*) AS count FROM spam_users WHERE collection = ?;";
        $counts = $this->dbhr->preQuery($sql, [ $collection ]);
        $count = $counts[0]['count'];
        return($count);
    }

    public function listSpammers($collection, $search, &$context) {
        # We exclude anyone who isn't a User (e.g. mods, support, admin) so that they don't appear on the list and
        # get banned.
        $collectionq = ($collection ? " AND collection = '$collection'" : '');
        $startq = $context ? (" AND spam_users.id <  " . intval($context['id']) . " ") : '';
        $searchq = $search == NULL ? '' : (" AND (users_emails.email LIKE " . $this->dbhr->quote("%$search%") . " OR users.fullname LIKE " . $this->dbhr->quote("%$search%") . ") ");
        $sql = "SELECT DISTINCT spam_users.* FROM spam_users INNER JOIN users ON spam_users.userid = users.id LEFT JOIN users_emails ON users_emails.userid = spam_users.userid WHERE 1=1 $startq $collectionq $searchq ORDER BY spam_users.id DESC LIMIT 10;";
        $context = [];

        $spammers = $this->dbhr->preQuery($sql);

        foreach ($spammers as &$spammer) {
            $u = new User($this->dbhr, $this->dbhm, $spammer['userid']);
            $spammer['user'] = $u->getPublic(NULL, TRUE, TRUE);
            $spammer['user']['email'] = $u->getEmailPreferred();

            $emails = $u->getEmails();

            $others = [];
            foreach ($emails as $anemail) {
                if ($anemail['email'] != $spammer['user']['email']) {
                    $others[] = $anemail;
                }
            }

            $spammer['user']['otheremails'] = $others;

            if ($spammer['byuserid']) {
                $u = new User($this->dbhr, $this->dbhm, $spammer['byuserid']);
                $spammer['byuser'] = $u->getPublic();
            }

            $context['id'] = $spammer['id'];
        }

        return($spammers);
    }

    public function getSpammer($id) {
        $sql = "SELECT * FROM spam_users WHERE id = ?;";
        $ret = NULL;

        $spams = $this->dbhr->preQuery($sql, [ $id ]);

        foreach ($spams as $spam) {
            $ret = $spam;
        }

        return($ret);
    }

    public function removeSpamMembers($groupid = NULL) {
        $count = 0;
        $groupq = $groupid ? " AND groupid = $groupid " : "";

        # Find anyone in the spammer list with a current (approved or pending) membership.  Don't remove mods
        # in case someone wrongly gets onto the list.
        $sql = "SELECT * FROM memberships INNER JOIN spam_users ON memberships.userid = spam_users.userid AND spam_users.collection = ? AND memberships.role = 'User' $groupq;";
        $spammers = $this->dbhr->preQuery($sql, [ Spam::TYPE_SPAMMER ]);

        foreach ($spammers as $spammer) {
            $g = new Group($this->dbhr, $this->dbhm, $spammer['groupid']);
            $spamcheck = $g->getSetting('spammers', [ 'check' => 1, 'remove' => 1]);
            if ($spamcheck['check'] && $spamcheck['remove']) {
                $u = new User($this->dbhr, $this->dbhm, $spammer['userid']);
                error_log("Found spammer {$spammer['userid']}");
                $u->removeMembership($spammer['groupid'], TRUE, TRUE);
                $count++;
            }
        }

        # Find any messages from spammers which are on groups.
        $groupq = $groupid ? " AND messages_groups.groupid = $groupid " : "";
        $sql = "SELECT DISTINCT messages.id, reason FROM `messages` INNER JOIN spam_users ON messages.fromuser = spam_users.userid AND spam_users.collection = ? AND messages.deleted IS NULL INNER JOIN messages_groups ON messages.id = messages_groups.msgid $groupq AND messages_groups.collection IN ('Approved', 'Pending');";
        $spammsgs = $this->dbhr->preQuery($sql, [ Spam::TYPE_SPAMMER ]);

        foreach ($spammsgs as $spammsg) {
            $g = new Group($this->dbhr, $this->dbhm, $spammer['groupid']);

            # Only remove on Freegle groups by default.
            $spamcheck = $g->getSetting('spammers', [ 'check' => 1, 'remove' => $g->getPrivate('type') == Group::GROUP_FREEGLE]);
            if ($spamcheck['check'] && $spamcheck['remove']) {
                error_log("Found spam message {$spammsg['id']}");
                $m = new Message($this->dbhr, $this->dbhm, $spammsg['id']);
                $m->delete("From known spammer {$spammsg['reason']}");
                $count++;
            }
        }

        return($count);
    }

    public function addSpammer($userid, $collection, $reason) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $text = NULL;

        switch ($collection) {
            case Spam::TYPE_WHITELIST: {
                $text = "Whitelisted: $reason";
                break;
            }
            case Spam::TYPE_PENDING_ADD: {
                $text = "Reported: $reason";
                break;
            }
        }

        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_SUSPECT,
            'byuser' => $me ? $me->getId() : NULL,
            'user' => $userid,
            'text' => $text
        ]);

        $sql = "REPLACE INTO spam_users (userid, collection, reason, byuserid) VALUES (?,?,?,?);";
        $rc = $this->dbhm->preExec($sql, [
            $userid,
            $collection,
            $reason,
            $me ? $me->getId() : NULL
        ]);

        $id = $rc ? $this->dbhm->lastInsertId() : NULL;

        return($id);
    }

    public function updateSpammer($id, $userid, $collection, $reason) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        switch ($collection) {
            case Spam::TYPE_SPAMMER: {
                $text = "Confirmed as spammer";
                break;
            }
            case Spam::TYPE_WHITELIST: {
                $text = "Whitelisted: $reason";
                break;
            }
            case Spam::TYPE_PENDING_ADD: {
                $text = "Reported: $reason";
                break;
            }
            case Spam::TYPE_PENDING_REMOVE: {
                $text = "Requested removal: $reason";
                break;
            }
        }

        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_SUSPECT,
            'byuser' => $me ? $me->getId() : NULL,
            'user' => $userid,
            'text' => $text
        ]);

        # Don't want to lose any existing reason.
        $spammers = $this->dbhr->preQuery("SELECT * FROM spam_users WHERE id = ?;", [ $id ]);
        foreach ($spammers as $spammer) {
            $sql = "UPDATE spam_users SET collection = ?, reason = ? WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql, [
                $collection,
                $reason ? $reason : $spammer['reason'],
                $id
            ]);
        }

        $id = $rc ? $this->dbhm->lastInsertId() : NULL;

        return($id);
    }

    public function deleteSpammer($id, $reason) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $spammers = $this->dbhr->preQuery("SELECT * FROM spam_users WHERE id = ?;", [ $id ]);

        $rc = FALSE;

        foreach ($spammers as $spammer) {
            $rc = $this->dbhm->preExec("DELETE FROM spam_users WHERE id = ?;", [
                $id
            ]);

            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_SUSPECT,
                'byuser' => $me ? $me->getId() : NULL,
                'user' => $spammer['userid'],
                'text' => "Removed: $reason"
            ]);
        }

        return($rc);
    }
}