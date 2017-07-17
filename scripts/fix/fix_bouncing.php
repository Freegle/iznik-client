<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$bouncings = $dbhr->preQuery("SELECT * FROM users WHERE bouncing = 1;");
error_log("Bouncing users " . count($bouncings));
$domains = [];
$count = 0;

foreach ($bouncings as $bouncing) {
    $u = new User($dbhr, $dbhm, $bouncing['id']);
    $email = $u->getEmailPreferred();

    if ($email) {
        $domain = strtolower(strrchr($email, '@'));

        if ($domain) {
            if (!array_key_exists($domain, $domains)) {
                $domains[$domain] = 1;
                error_log($domain);
            } else {
                $domains[$domain]++;
            }
        }
    }

    $count++;

    if ($count % 1000 == 0) {
        error_log("...$count");
    }
}

$gooddomains = [];

foreach ($domains as $domain => $count) {
    error_log("$domain => $count");

    if (in_array($domain, $gooddomains)) {
        error_log("...reset");

    }
}