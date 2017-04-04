<?php
# We archive out of the DB into Azure.  This reduces load on the servers because we don't have to serve
# the images up, and it also reduces the disk space we need within the DB (which is not an ideal
# place to store large amounts of image data);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$sql = "SELECT id FROM messages_attachments WHERE archived = 0;";
$atts = $dbhr->preQuery($sql);
error_log(count($atts) . " to archive");
$count = 0;
$total = count($atts);

foreach ($atts as $att) {
    $a = new Attachment($dbhr, $dbhm, $att['id']);
    $a->archive();

    $count++;
    error_log("...$count / $total");
}

unlockScript($lockh);