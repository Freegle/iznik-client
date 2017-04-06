<?php
# Rescale large images in message_attachments

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Attachment.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');

use WindowsAzure\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;

$blobRestProxy = ServicesBuilder::getInstance()->createBlobService(AZURE_CONNECTION_STRING);

$id = file_get_contents('/tmp/fixarchive');
$id = $id ? $id : 1835462;

$sql = "SELECT id FROM messages_attachments WHERE id >= $id AND archived = 1 ORDER BY id ASC;";
$atts = $dbhr->preQuery($sql);
$total = count($atts);
$count = 0;

$options = new CreateBlobOptions();
$options->setBlobContentType("image/jpeg");

foreach ($atts as $att) {
    $data = @file_get_contents("/archive/attachments/img_{$att['id']}.jpg");
    if ($data) {
        for ($i = 0; $i < 10; $i++) {
            try {
                $blob = $blobRestProxy->getBlob("images", "img_{$att['id']}.jpg");

                # Exists = nothing to do.
                $count++;
                error_log("...{$att['id']} exists $count / $total");
                break;
            } catch (Exception $e) {
                try    {
                    $blobRestProxy->createBlockBlob("images", "img_{$att['id']}.jpg", $data, $options);

                    $i = new Image($data);
                    if ($i->img) {
                        $i->scale(250, 250);
                        $thumbdata = $i->getData(100);
                        $blobRestProxy->createBlockBlob("images", "timg_{$att['id']}.jpg", $thumbdata, $options);
                    } else {
                        error_log("...failed to create image");
                    }

                    $count++;
                    error_log("...{$att['id']} $count / $total");
                    break;
                }
                catch (Exception $e){
                    $code = $e->getCode();
                    $error_message = $e->getMessage();
                    error_log("...failed $code: " . $error_message);
                    sleep(1);
                }
            }
        }
    } else {
        error_log("...{$att['id']} not found");
    }

    file_put_contents('/tmp/fixarchive', $att['id']);
}
