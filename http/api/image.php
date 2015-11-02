<?php
function image() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];
    $id = intval(presdef('id', $_REQUEST, 0));

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET': {
            $a = new Attachment($dbhr, $dbhm, $id);
            $data = $a->getData();
            $i = new Image($data);

            $w = intval(presdef('w', $_REQUEST, $i->width()));
            $h = intval(presdef('h', $_REQUEST, $i->height()));

            error_log("Image $w x $h");

            if (($w > 0) || ($h > 0)) {
                # Need to resize
                error_log("Resize");
                $i->scale($w, $h);
            }

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'img' => $i->getData()
            ];

            break;
        }
    }

    return($ret);
}
