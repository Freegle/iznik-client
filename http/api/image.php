<?php
function image() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];
    $id = intval(presdef('id', $_REQUEST, 0));
    $msgid = pres('msgid', $_REQUEST) ? intval($_REQUEST['msgid']) : NULL;
    $fn = presdef('filename', $_REQUEST, NULL);
    $identify = array_key_exists('identify', $_REQUEST) ? filter_var($_REQUEST['identify'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $group = presdef('group', $_REQUEST, NULL);
    $newsletter = presdef('newsletter', $_REQUEST, NULL);

    if ($newsletter) {
        $type = Attachment::TYPE_NEWSLETTER;
    } else if ($group) {
        $type = Attachment::TYPE_GROUP;
    } else {
        $type = Attachment::TYPE_MESSAGE;
    }

    switch ($_REQUEST['type']) {
        case 'GET': {
            $a = new Attachment($dbhr, $dbhm, $id, $type);
            $data = $a->getData();
            $i = new Image($data);

            $ret = [
                'ret' => 1,
                'status' => "Failed to create image $id of type $type"
                ];

            if ($i->img) {
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
            }

            break;
        }

        case 'PUT': {
            $fn = IZNIK_BASE . "/http/uploads/" . basename($fn);
            $data = file_get_contents($fn);
            $a = new Attachment($dbhr, $dbhm, NULL, $type);
            $id = $a->create($msgid, mime_content_type($fn), $data);

            # Make sure it's not too large, to keep DB size down.
            $data = $a->getData();
            $i = new Image($data);
            $h = $i->height();
            $w = $i->width();

            if ($w > 800) {
                $h = $h * 800 / $w;
                $w = 800;
                $i->scale($w, $h);
                $data = $i->getData(100);
                $a->setPrivate('data', $data);
            }

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'id' => $id,
                'path' => Attachment::getPath($id, $type)
            ];

            if ($identify) {
                $a = new Attachment($dbhr, $dbhm, $id);
                $ret['items'] = $a->identify();
            }
        }
    }

    return($ret);
}
