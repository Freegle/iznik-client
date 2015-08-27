<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/Entity.php');

use GeoIp2\Database\Reader;

class SpamIP {
    function __construct($dbhr, $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function check(IncomingMessage $msg) {
        $ip = $msg->getHeader('x-freegle-ip');
        $ip = $ip ? $ip : $msg->getHeader('x-originating-ip');
        $ip = $ip ? $ip : $msg->getHeader('x-yahoo-post-ip');

        if (preg_match('/mta.*groups\.mail.*yahoo\.com/', $ip)) {
            # Posts submitted by email to Yahoo show up with an X-Originating-IP of one of Yahoo's MTAs.  We don't
            # want to consider those as spammers.
            $ip = NULL;
        }

        if ($ip) {
            $ip = str_replace('[', '', $ip);
            $ip = str_replace(']', '', $ip);
            $msg->setFromIP($ip);

            # We have an IP, we reckon.  It's unlikely that someone would fake an IP which gave a spammer match, so
            # we don't have to worry too much about false positives.
            $reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
            $record = $reader->country($ip);
            $country = $record->country->name;

            # Now see if we're blocking all mails from that country.  This is legitimate if our service is for a
            # single country and we are vanishingly unlikely to get legitimate emails from certain others.
            $countries = $this->dbhr->preQuery("SELECT * FROM spam_countries WHERE country LIKE ?;", [$country]);
            foreach ($countries as $country) {
                # Gotcha.
                return(true);
            }
        }

        # It's fine.  So far as we know.
        return(false);
    }
}