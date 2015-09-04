<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

use GeoIp2\Database\Reader;

class Spam {
    CONST USER_THRESHOLD = 4;
    CONST GROUP_THRESHOLD = 20;

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

    public function check(IncomingMessage $msg) {
        $ip = $msg->getHeader('x-freegle-ip');
        $ip = $ip ? $ip : $msg->getHeader('x-originating-ip');
        $ip = $ip ? $ip : $msg->getHeader('x-yahoo-post-ip');
        $host = NULL;

        if ($ip) {
            $ip = str_replace('[', '', $ip);
            $ip = str_replace(']', '', $ip);
            $msg->setFromIP($ip);

            $host = $msg->getFromhost();
            if (preg_match('/mail.*yahoo\.com/', $host)) {
                # Posts submitted by email to Yahoo show up with an X-Originating-IP of one of Yahoo's MTAs.  We don't
                # want to consider those as spammers.
                $ip = NULL;
                $msg->setFromIP($ip);
            }
        }

        if ($ip) {
            # We have an IP, we reckon.  It's unlikely that someone would fake an IP which gave a spammer match, so
            # we don't have to worry too much about false positives.
            try {
                $record = $this->reader->country($ip);
                $country = $record->country->name;
            } catch (Exception $e) {
                # Failed to look it up.
                $country = NULL;
            }

            # Now see if we're blocking all mails from that country.  This is legitimate if our service is for a
            # single country and we are vanishingly unlikely to get legitimate emails from certain others.
            $countries = $this->dbhr->preQuery("SELECT * FROM spam_countries WHERE country LIKE ?;", [$country]);
            foreach ($countries as $country) {
                # Gotcha.
                return(array(true, "Blocking IP $ip as it's in {$country['country']}"));
            }

            # Now see if this IP has been used for too many different users.  That is likely to
            # be someone masquerading to fool people.
            #
            # Should check address, but we don't yet have the canonical address so will be fooled by FBUser
            # TODO
            $sql = "SELECT COUNT(*) AS count, fromaddr FROM messages_history WHERE fromip = ? GROUP BY fromname;";
            $counts = $this->dbhr->preQuery($sql, [$ip]);
            $numusers = count($counts);

            if ($numusers > Spam::USER_THRESHOLD) {
                return(array(true, "Blocking IP $ip ($host) as used for $numusers different users"));
            }

            # Now see if this IP has been used for too many different groups.  That's likely to
            # be someone spamming.
            $sql = "SELECT COUNT(*) AS count, groupid FROM messages_history WHERE fromip = ? GROUP BY groupid;";
            $counts = $this->dbhr->preQuery($sql, [$ip]);
            $numgroups = count($counts);

            if ($numgroups > Spam::GROUP_THRESHOLD) {
                return(array(true, "Blocking IP $ip ($host) used for $numgroups different groups"));
            }
        }

        # It's fine.  So far as we know.
        return(NULL);
    }
}