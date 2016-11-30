<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$domains = explode(',', TRUSTED_LINKS);

foreach ($domains as $domain) {
    $dbhm->preExec("INSERT IGNORE INTO spam_whitelist_links (domain) VALUES (?);", [
        $domain
    ]);
}