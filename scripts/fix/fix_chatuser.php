<?php

# Run on backup server to recover a user from a backup to the live system.  Use with astonishing levels of caution.
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host=localhost;port=3309;dbname=iznik;charset=utf8";
$dbhback = new LoggedPDO($dsn, SQLUSER, SQLPASSWORD, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$chats = $dbhr->preQuery("SELECT id FROM chat_rooms WHERE user1 = 33532851;");
foreach ($chats as $chat) {
    $backs = $dbhback->preQuery("SELECT user1 FROM chat_rooms WHERE id = ?;", [ $chat['id'] ]);

    foreach ($backs as $back) {
        if ($back['user1'] != $chat['user1']) {
            error_log("Wrong user in {$chat['id']}");
        }
    }
}