<?php

# We have a list of UK postcodes in the DB, among other locations.  UK postcodes change fairly frequently.
#
# 1. Go to https://www.ordnancesurvey.co.uk/opendatadownload/products.html
# 2. Download Code-Point Open, in ZIP form
# 3. Unzip it somewhere
# 4. Run this script to process it.
#
# It will add any new postcodes to the DB.
# TODO Removal?

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "ilovefreegle.org";

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/mail/Newsletter.php');

$opts = getopt('e:i:');

if (count($opts) == 0) {
    echo "Usage: hhvm newsletter <-e <email>> -i <newsletter id>)\n";
} else {
    $email = presdef('e', $opts, NULL);
    $id = presdef('i', $opts, NULL);

    $n = new Newsletter($dbhr, $dbhm, $id);

    if ($n->getId() == $id) {
        if ($email) {
            $u = new User($dbhr, $dbhm);
            $eid = $u->findByEmail($email);

            if ($eid) {
                $n->send(NULL, $eid);
            }
        } else {
            $n->send(NULL, NULL);
        }
    }
}
