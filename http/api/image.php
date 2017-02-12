<?php
function image() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];
    $id = intval(presdef('id', $_REQUEST, 0));
    $msgid = pres('msgid', $_REQUEST) ? intval($_REQUEST['msgid']) : NULL;
    $fn = presdef('filename', $_REQUEST, NULL);
    $identify = array_key_exists('identify', $_REQUEST) ? filter_var($_REQUEST['identify'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $ocr = array_key_exists('ocr', $_REQUEST) ? filter_var($_REQUEST['ocr'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $group = presdef('group', $_REQUEST, NULL);
    $newsletter = presdef('newsletter', $_REQUEST, NULL);
    $communityevent = presdef('communityevent', $_REQUEST, NULL);

    if ($communityevent) {
        $type = Attachment::TYPE_COMMUNITY_EVENT;
        $shorttype = '_c';
    } else if ($newsletter) {
        $type = Attachment::TYPE_NEWSLETTER;
        $shorttype = '_n';
    } else if ($group) {
        $type = Attachment::TYPE_GROUP;
        $shorttype = '_g';
    } else {
        $type = Attachment::TYPE_MESSAGE;
        $shorttype = '';
    }

    switch ($_REQUEST['type']) {
        case 'GET': {
            # We cache the data to files to avoid the DB queries where we can.
            $fn = IZNIK_BASE . "/http/imgcache/img_{$shorttype}_{$id}_" . presdef('w', $_REQUEST, '') . "x" . presdef('h', $_REQUEST, '') . ".jpg";

            $data = @file_get_contents($fn);

            if ($data) {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'img' => $data
                ];
            } else {
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

                    file_put_contents($fn, $ret['img']);
                }
            }

            break;
        }

        case 'POST': {
            $ret = [ 'ret' => 1, 'status' => 'No photo provided' ];

            # This next line is to simplify UT.
            $rotate = pres('rotate', $_REQUEST) ? intval($_REQUEST['rotate']) : NULL;

            if ($rotate) {
                # We want to rotate.  Do so.
                $a = new Attachment($dbhr, $dbhm, $id, $type);
                $data = $a->getData();
                $i = new Image($data);
                $i->rotate($rotate);
                $newdata = $i->getData(100);
                $a->setData($newdata);

                # Now clear any cached image files.  This will include any thumbnail, which
                # will get created as a _250x250 file.
                foreach (glob(IZNIK_BASE . "/http/imgcache/img_{$shorttype}_{$id}*") as $filename) {
                    unlink($filename);
                }

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'rotatedsize' => strlen($newdata)
                ];
            } else {
                $photo = presdef('photo', $_FILES, NULL) ? $_FILES['photo'] : $_REQUEST['photo'];
                $imgtype = presdef('imgtype', $_REQUEST, Attachment::TYPE_MESSAGE);
                $mimetype = presdef('type', $photo, NULL);

                # Make sure what we have looks plausible - the file upload plugin should ensure this is the case.
                if ($photo &&
                    pres('tmp_name', $photo) &&
                    strpos($mimetype, 'image/') === 0) {

                    # We may need to rotate.
                    $data = file_get_contents($photo['tmp_name']);
                    $image = imagecreatefromstring($data);
                    $exif = exif_read_data($photo['tmp_name']);

                    if($exif && !empty($exif['Orientation'])) {
                        switch($exif['Orientation']) {
                            case 8:
                                $image = imagerotate($image,90,0);
                                break;
                            case 3:
                                $image = imagerotate($image,180,0);
                                break;
                            case 6:
                                $image = imagerotate($image,-90,0);
                                break;
                        }

                        ob_start();
                        imagejpeg($image, NULL, 100);
                        $data = ob_get_contents();
                        ob_end_clean();
                    }

                    if ($data) {
                        $a = new Attachment($dbhr, $dbhm, NULL, $imgtype);
                        $id = $a->create($msgid, $photo['type'], $data);

                        # Make sure it's not too large, to keep DB size down.  Ought to have been resized by
                        # client, but you never know.
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
                            'path' => Attachment::getPath($id, $imgtype),
                            'paththumb' => Attachment::getPath($id, $imgtype, TRUE)
                        ];

                        # Return a new thumbnail (which might be a different orientation).
                        $ret['initialPreview'] =  [
                            '<img src="' . Attachment::getPath($id, $imgtype, TRUE) . '" class="file-preview-image" width="130px">',
                        ];

                        if ($identify) {
                            $a = new Attachment($dbhr, $dbhm, $id);
                            $ret['items'] = $a->identify();
                        }

                        if ($ocr) {
                            $a = new Attachment($dbhr, $dbhm, $id, $type);
                            $ret['ocr'] = $a->ocr();
                        }
                    }
                }

                # Uploader code requires this field.
                $ret['error'] = $ret['ret'] == 0 ? NULL : $ret['status'];
            }

            break;
        }
    }

    return($ret);
}
