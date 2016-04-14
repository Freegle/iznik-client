<?php
# Rescale large images in message_attachments

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";

$sql = "SELECT id, data, LENGTH(data) FROM messages_attachments WHERE LENGTH(data) > 300000;";
$atts = $dbhr->query($sql);

foreach ($atts as $att) {
    error_log("Scale attach {$att['id']}");
    $i = new Image($att['data']);
    $w = $i->width();

    if ($w > 1024) {
        $i->scale(1024, NULL);
    }

    # Now get the data back.  Even if we didn't resize, we will be reducing the quality, which will reduce the size
    $data = $i->getData();
    error_log("Scaled to " . strlen($data));

    # Now save the new jpeg data
    $sql = "UPDATE messages_attachments SET contenttype = 'image/jpeg', data = ? WHERE id = ?;";
    $rc = $dbhm->preExec($sql, [ $data, $att['id']] );
    error_log("Updated $rc");
}
