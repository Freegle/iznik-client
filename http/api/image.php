<?php
function image() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];
    $id = intval(presdef('id', $_REQUEST, 0));
    $msgid = pres('msgid', $_REQUEST) ? intval($_REQUEST['msgid']) : NULL;
    $fn = presdef('filename', $_REQUEST, NULL);
    $identify = array_key_exists('identify', $_REQUEST) ? filter_var($_REQUEST['identify'], FILTER_VALIDATE_BOOLEAN) : FALSE;

    switch ($_REQUEST['type']) {
        case 'GET': {
            $a = new Attachment($dbhr, $dbhm, $id);
            $data = $a->getData();
            $i = new Image($data);

            $w = intval(presdef('w', $_REQUEST, $i->width()));
            $h = intval(presdef('h', $_REQUEST, $i->height()));

            if (($w > 0) || ($h > 0)) {
                # Need to resize
                $i->scale($w, $h);
            }

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'img' => $i->getData()
            ];

            break;
        }

        case 'PUT': {
            $fn = IZNIK_BASE . "/http/uploads/" . basename($fn);
            $data = file_get_contents($fn);
            $a = new Attachment($dbhr, $dbhm);
            $id = $a->create($msgid, mime_content_type($fn), $data);

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'id' => $id
            ];

            if ($identify) {
                $a = new Attachment($dbhr, $dbhm, $id);
                $ret['items'] = $a->identify();
            }
        }
    }

    return($ret);
}
