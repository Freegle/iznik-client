<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

# Extract the messages
$msgs = $dbhr->preQuery("SELECT messages_spamham.spamham, messages.message FROM messages_spamham INNER JOIN messages ON messages.id = messages_spamham.msgid;");
foreach ($msgs as $msgs) {
    $fn = tempnam($msgs['spamham'] == 'Spam' ? '/tmp/sa_train/spam' : '/tmp/sa_train/ham', 'msg');
    error_log($fn);
    file_put_contents($fn, $msgs['message']);
}