<?php
# Rescale large images in message_attachments

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');

use Jenssegers\ImageHash\ImageHash;

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";

$sql = "SELECT id FROM messages_attachments WHERE hash IS NULL;";
$atts = $dbhr->preQuery($sql);
$total = count($atts);
$count = 0;
$hasher = new ImageHash;

foreach ($atts as $att) {
    $atts2 = $dbhr->preQuery("SELECT data FROM messages_attachments WHERE id = {$att['id']};");
    $att['data'] = $atts2[0]['data'];

    $img = imagecreatefromstring($att['data']);

    if ($img) {
        $hash = $hasher->hash($img);

        #error_log("{$att['id']} $hash");
        $dbhm->preExec("UPDATE messages_attachments SET hash = ? WHERE id = ?;", [ $hash, $att['id']]);
    }

    $count++;
    if ($count % 100 == 0) {
        error_log("...$count / $total");
    }
}
