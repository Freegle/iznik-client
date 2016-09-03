<?php
# Move attachments out of the database into archive storage.

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

# TODO Make this host generic.

$time = date('Y-m-d', strtotime("midnight 7 days ago"));
$sql = "SELECT messages_attachments.id FROM messages INNER JOIN messages_attachments ON messages.id = messages_attachments.msgid WHERE arrival < '$time' AND data IS NOT NULL;";
$atts = $dbhr->query($sql);
$count = 0;

foreach ($atts as $att) {
    $a = new Attachment($dbhr, $dbhm, $att['id']);
    $a->archive();

    $count++;
    if ($count % 1000 == 0) {
      error_log("...$count");
    }
}

unlockScript($lockh);