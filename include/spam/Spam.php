<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

use GeoIp2\Database\Reader;

class Spam {
    CONST USER_THRESHOLD = 10;
    CONST GROUP_THRESHOLD = 20;
    CONST SUBJECT_THRESHOLD = 30;  // SUBJECT_THRESHOLD must be > GROUP_THRESHOLD for UT

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
    }

    public function check(Message $msg) {
        $ip = $msg->getHeader('x-freegle-ip');
        $ip = $ip ? $ip : $msg->getHeader('x-yahoo-post-ip');
        $ip = $ip ? $ip : $msg->getHeader('x-originating-ip');
        $ip = preg_replace('/[\[\]]/', '', $ip);
        $host = NULL;

        if ($ip) {
            $msg->setFromIP($ip);

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
                return(array(true, Spam::REASON_IP_USED_FOR_DIFFERENT_USERS, "IP $ip ($host) recently used for $numusers different users (" . implode(',', $list) . ")"));
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
                return(array(true, Spam::REASON_IP_USED_FOR_DIFFERENT_GROUPS, "IP $ip ($host) recently used for $numgroups different groups (" . implode(',', $list) . ")"));
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
}