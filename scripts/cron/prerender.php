<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$lockh = lockScript(basename(__FILE__));

# We want the key top-level pages.
$tops = [
    '' => 1,
    'give' => 1,
    'find' => 1,
    'explore' => 0.8,
    'about' => 0.1,
    'terms' => 0.1,
    'privacy' => 0.1,
    'donate' => 0.3,
    'contact' => 0.5,
    'why' => 0.5
];

foreach ($tops as $top => $prio) {
    $dbhm->preExec("INSERT IGNORE INTO prerender (url) VALUES (?);", [ "https://" . USER_SITE . "/$top" ]);
}

# We want to pre-cache all Freegle groups.
$regions = [];

$groups = $dbhr->preQuery("SELECT id, nameshort, region FROM groups WHERE type = 'Freegle' AND publish = 1;");
foreach ($groups as $group) {
    $dbhm->preExec("INSERT IGNORE INTO prerender (url) VALUES (?);", [ "https://" . USER_SITE . "/explore/{$group['nameshort']}" ]);
    $regions[$group['region']] = TRUE;
}

foreach ($regions as $key => $val) {
    if ($key && strlen($key)) {
        $dbhm->preExec("INSERT IGNORE INTO prerender (url) VALUES (?);", [ "https://" . USER_SITE . "/explore/region/$key" ]);
    }
}
$pages = $dbhr->preQuery("SELECT id, url FROM prerender WHERE html IS NULL OR TIMESTAMPDIFF(MINUTE, retrieved,NOW()) >= timeout ORDER BY html ASC;");
shuffle($pages);

foreach ($pages as $page) {
    echo "php ../cli/prerender.php -i {$page['id']}&\nsleep 1\n";
}

echo "wait\n";

unlockScript($lockh);