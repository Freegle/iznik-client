<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');

# Get all distinct images
$start = date('Y-m-d', strtotime("365 days ago"));
$hashes = $dbhr->preQuery("SELECT DISTINCT(hash) FROM messages_attachments INNER JOIN messages_groups ON messages_attachments.msgid = messages_groups.msgid INNER JOIN groups ON messages_groups.groupid = groups.id WHERE messages_groups.arrival > ? AND groups.type = 'Freegle';", [ $start ]);
$frame = 0;
$count = 0;

error_log("Got " . count($hashes));

foreach ($hashes as $hash) {
    $atts = $dbhr->preQuery("SELECT id FROM messages_attachments WHERE hash = ? LIMIT 1;", [
        $hash['hash']
    ]);

    foreach ($atts as $att) {
        $a = new Attachment($dbhr, $dbhm, $att['id']);
        $data = $a->getData();

        if (strlen($data) > 0) {
            $i = new Image($data);

            if ($i->width() > 200) {
                # Only want images that are larger.
                $i->scale(600, NULL);

                $data = $i->getData(100);
                $s = sprintf("%05d", $frame++);
                $fn = "/tmp/t/img_$s.jpg";
                file_put_contents($fn, $data);
            }
        }
    }

    $count++;

    if ($count % 100 == 0) {
        error_log("...$count / " . count($hashes));
    }
}

error_log("Now do this\n\ncd /tmp/t;ffmpeg -framerate 6 -i img_%05d.jpg -c:v libx264 -profile:v high -crf 20 -pix_fmt yuv420p -framerate 25 output.mp4");