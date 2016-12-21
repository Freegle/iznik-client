<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";
$fddbh = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$items = $fddbh->query("SELECT * FROM weights WHERE weight IS NOT NULL AND weight > 0;");

foreach ($items as $item) {
    $is = $dbhr->preQuery("SELECT * FROM items WHERE name LIKE ?;",
        [ $item['keyword'] ]);

    if (count($is) > 0) {
        foreach ($is as $i) {
            error_log("Know {$item['keyword']}");
            $dbhm->preExec("INSERT INTO items_weights (itemid, weight, userid) VALUES (?, ?, NULL);", [
                $i['id'],
                $item['weight']
            ]);
        }
    } else {
        error_log("Don't know {$item['keyword']}");
        $dbhm->preExec("INSERT INTO items (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);", [ $item['keyword'] ]);
        $dbhm->preExec("INSERT INTO items_weights (itemid, weight, userid) VALUES (?, ?, NULL);", [
            $dbhm->lastInsertId(),
            $item['weight']
        ]);
    }
}
