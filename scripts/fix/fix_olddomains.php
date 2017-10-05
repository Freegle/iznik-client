<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');


$domains = [
    'Orange.net',
    'Orangehome.co.uk',
    'Wanadoo.co.uk',
    'Freeserve.co.uk',
    'Fsbusiness.co.uk',
    'Fslife.co.uk',
    'Fsmail.net',
    'Fsworld.co.uk',
    'Fsnet.co.uk'
];

foreach ($domains as $domain) {
    $emails = $dbhr->preQuery("SELECT * FROM users_emails WHERE backwards LIKE ?;", [ strrev($domain) . '%']);
    error_log("$domain " . count($emails));

    foreach ($emails as $email) {
        $dbhm->preExec("DELETE FROM users_emails WHERE id = ?;", [ $email['id'] ]);
    }
}