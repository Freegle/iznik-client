<?php
function status() {
    $ret = [ 'ret' => 1, 'status' => 'Cannot access status file' ];

    $status = @file_get_contents('/tmp/iznik.status');

    if ($status) {
        $ret = json_decode($status, TRUE);
    }

    return($ret);
}
