<?php
# Move attachments out of the database into archive storage.

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');

# TODO Make this host generic.
$dsn = "mysql:host=db3.ilovefreegle.org;dbname=iznik;charset=utf8";
$dbh = new PDO($dsn, SQLUSER, SQLPASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

$time = date('Y-m-d', strtotime("midnight 31 days ago"));
$sql = "SELECT messages_attachments.id FROM messages INNER JOIN messages_attachments ON messages.id = messages_attachments.msgid WHERE arrival < '$time';";
$atts = $dbh->query($sql);
$count = 0;

foreach ($atts as $att) {
    $sql = "SELECT * FROM messages_attachments WHERE id = {$att['id']};";
    $datas = $dbh->query($sql);
    foreach ($datas as $data) {
        $rc = file_put_contents(IZNIK_BASE . "/http/attachments/img_{$att['id']}.jpg", $data['data']);
        if ($rc) {
            $sql = "UPDATE messages_attachments SET archived = 1, data = NULL WHERE id = {$att['id']};";
            $dbh->exec($sql);
        }
    }

    $count++;
    if ($count % 1000 == 0) {
      error_log("...$count");
    }
}
