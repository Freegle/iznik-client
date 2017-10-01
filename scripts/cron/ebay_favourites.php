<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$ebay = file_get_contents('http://www.ebay.co.uk/egw/ebay-for-charity/charity-profile/Freegle/74430');

if (preg_match('/0"\>(.*?) Favourites</', $ebay, $matches)) {
    $count = str_replace(',', '', $matches[1]);
    error_log("Favourites $count");
    $dbhm->preExec("INSERT INTO ebay_favourites (count) VALUES (?);", [
        $count
    ]);
}