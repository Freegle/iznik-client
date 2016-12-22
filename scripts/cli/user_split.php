<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('e:n:');

if (count($opts) < 1) {
    echo "Usage: hhvm user_split.php -e <email to split out into separate user>\n";
} else {
    $email = $opts['e'];
    $name = $opts['n'];
    $u = User::get($dbhr, $dbhm);
    $uid = $u->findByEmail($email);

    if ($uid) {
        $u = User::get($dbhr, $dbhm, $uid);
        error_log("Found user #$uid");
        $u->split($email);

        $uid2 = $u->create(NULL, NULL, $name);
        error_log("Split into user #$uid2");
        $u->addEmail($email);

        # We might be able to move some messages over.
        $dbhm->preExec("UPDATE messages SET fromuser = ? WHERE fromaddr = ?;", [
            $uid2,
            $email
        ]);
        $dbhm->preExec("UPDATE messages_history SET fromuser = ? WHERE fromaddr = ?;", [
            $uid2,
            $email
        ]);

        # Chats which reference the messages sent from that email must also be intended for the split user.
        $chats = $dbhr->preQuery("SELECT DISTINCT chat_rooms.* FROM chat_rooms INNER JOIN chat_messages ON chat_messages.chatid = chat_rooms.id WHERE refmsgid IN (SELECT id FROM messages WHERE fromaddr = ?);", [
            $email
        ]);

        foreach ($chats as $chat) {
            if ($chat['user1'] == $uid) {
                $dbhm->preExec("UPDATE chat_rooms SET user1 = ? WHERE id = ?;", [
                    $uid2,
                    $chat['id']
                ]);
            }
            if ($chat['user2'] == $uid) {
                $dbhm->preExec("UPDATE chat_rooms SET user2 = ? WHERE id = ?;", [
                    $uid2,
                    $chat['id']
                ]);
            }
        }

        # We might have a name.
        $dbhm->preExec("UPDATE users SET fullname = (SELECT fromname FROM messages WHERE fromaddr = ? LIMIT 1) WHERE id = ?;", [
            $email,
            $uid2
        ]);

        # Zap any existin sessions for either.
        $dbhm->preExec("DELETE FROM sessions WHERE userid IN (?, ?);", [ $uid, $uid2 ]);
    }
}
