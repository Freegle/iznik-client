<?php
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

# We shrink profile pictures down to 200x200.
$pics = $dbhr->preQuery("SELECT * FROM users_images WHERE data IS NOT NULL AND LENGTH(data) > 50000;");
error_log("Check " . count($pics) . " profile pictures");
foreach ($pics as $pic) {
    try {
        $i = new Image($pic['data']);
        $i->scale(200, 200);
        $d = $i->getData(100);
        if (strlen($d) < strlen($pic['data'])) {
            error_log("{$pic['id']} " . strlen($pic['data']) . " => " . strlen($d));
            $dbhm->preExec("UPDATE users_images SET data = ? WHERE id = ?;", [
                $d,
                $pic['id']
            ]);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

# We archive message photos of the DB into Azure.  This reduces load on the servers because we don't have to serve
# the images up, and it also reduces the disk space we need within the DB (which is not an ideal
# place to store large amounts of image data);
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