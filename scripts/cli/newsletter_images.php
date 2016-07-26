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

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');

$opts = getopt('d:');

if (count($opts) != 1) {
    echo "Usage: hhvm newsletter_images -d <folder with images>)\n";
} else {
    $fold = presdef('d', $opts, NULL);

    if ($fold) {
        foreach (glob("$fold/*.*") as $file) {
            $data = file_get_contents($file);

            $a = new Attachment($dbhr, $dbhm, NULL, Attachment::TYPE_NEWSLETTER);
            $attid = $a->create(NULL, 'image/jpeg', $data);
            error_log("$file => $attid");
        }
    }
}
