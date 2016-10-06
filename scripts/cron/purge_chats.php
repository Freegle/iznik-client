<?php
#
# Purge chats. We do this in a script rather than an event because we want to chunk it, otherwise we can hang the
# cluster with an op that's too big.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

# Bypass our usual DB class as we don't want the overhead nor to log.
$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$dbhm = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

# Purge chats which have no messages.  This can happen for spam replies, which create a chat and then the message
# later gets deleted.
error_log("Purge empty");

$total = 0;
do {
    $sql = "SELECT chat_rooms.id FROM `chat_rooms` LEFT OUTER JOIN chat_messages ON chat_rooms.id = chat_messages.chatid WHERE chat_messages.chatid IS NULL AND chat_Rooms.chattype = 'User2User' LIMIT 1000;";
    $chats = $dbhm->query($sql)->fetchAll();
    foreach ($chats as $chat) {
        $dbhm->exec("DELETE FROM chat_rooms WHERE id = {$chat['id']};");
        $total++;

        if ($total % 1000 == 0) {
            error_log("...$total");
        }
    }
} while (count($chats) > 0);

