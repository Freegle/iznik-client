<?php
function upload() {
    global $dbhr, $dbhm;
    $upload_handler = new CustomUploadHandler([
        'upload_dir' => IZNIK_BASE . '/http/uploads/',
        'upload_url' => "https://" . $_SERVER['HTTP_HOST'] . '/uploads/'
    ]);
}