<?php
# Rescale large images in message_attachments

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/chat/ChatRoom.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');

$chats = $dbhr->preQuery("SELECT id, user1 FROM chat_rooms WHERE chattype = 'User2Mod';");

foreach ($chats as $chat) {
    error_log("Chat #{$chat['id']}");
    $c = new ChatRoom($dbhr, $dbhm, $chat['id']);
    list($msgs, $users) = $c->getMessages();

    if (count($msgs) > 0) {
        $msg = $msgs[0];

        if (presdef('userid', $msg, NULL) != $chat['user1']) {
            # First message is from mod.
            error_log("First msg #{$msg['id']} from {$msg['userid']} vs {$chat['user1']}");
            #error_log(var_export($msgs, TRUE));
            $roster = $c->getRoster();
            #error_log("Roster " . var_export($roster, TRUE));

            if (count($roster) == 0) {
                error_log("...No roster, add");
                $c->updateRoster($msg['userid'], $msg['id'], ChatRoom::STATUS_OFFLINE);
            } else {
                foreach ($roster as $user) {
                    if ($user['user']['id'] == $msg['userid']) {
                        if ($user['lastmsgseen'] && $user['lastmsgseen'] > $msg['id']) {
                            error_log("...already seen later");
                        } else {
                            error_log("...mark first as seen");
                            $c->updateRoster($msg['userid'], $msg['id'], ChatRoom::STATUS_OFFLINE);
                        }
                    }
                }
            }
        }
    }
}