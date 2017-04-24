<?php
function group() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);

    # The id parameter can be an ID or a nameshort.
    $id = presdef('id', $_REQUEST, NULL);
    $nameshort = NULL;

    if (is_numeric($id)) {
        $id = intval($id);
    } else {
        $nameshort = $id;
    }

    $action = presdef('action', $_REQUEST, NULL);

    if ($nameshort) {
        $g = Group::get($dbhr, $dbhm);
        $id = $g->findByShortName($nameshort);
    }

    if ($id || ($action == 'Create') || ($action == 'Contact')) {
        $g = new Group($dbhr, $dbhm, $id);

        switch ($_REQUEST['type']) {
            case 'GET': {
                $members = array_key_exists('members', $_REQUEST) ? filter_var($_REQUEST['members'], FILTER_VALIDATE_BOOLEAN) : FALSE;

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'group' => $g->getPublic()
                ];

                $ret['group']['myrole'] = $me ? $me->getRoleForGroup($id) : User::ROLE_NONMEMBER;
                $ret['group']['mysettings'] = $me ? $me->getGroupSettings($id) : NULL;
                $ctx = presdef('context', $_REQUEST, NULL);
                $limit = presdef('limit', $_REQUEST, 5);
                $search = presdef('search', $_REQUEST, NULL);

                if ($members && $me && $me->isModOrOwner($id)) {
                    $ret['group']['members'] = $g->getMembers($limit, $search, $ctx);
                    $ret['context'] = $ctx;
                }

                if ($me && $me->isModerator()) {
                    # Return info on Twitter status.  This isn't secret info - we don't put anything confidential
                    # in here - but it's of no interest to members so there's no point delaying them by
                    # fetching it.
                    #
                    # Similar code in session.php
                    $t = new Twitter($dbhr, $dbhm, $id);
                    $atts = $t->getPublic();
                    unset($atts['token']);
                    unset($atts['secret']);
                    $atts['authdate'] = ISODate($atts['authdate']);
                    $ret['group']['twitter'] =  $atts;

                    # Ditto Facebook.
                    $uids = GroupFacebook::listForGroup($dbhr, $dbhm, $id);
                    $ret['group']['facebook'] = [];

                    foreach ($uids as $uid) {
                        $f = new GroupFacebook($dbhr, $dbhm, $uid);
                        $atts = $f->getPublic();
                        unset($atts['token']);
                        $atts['authdate'] = ISODate($atts['authdate']);
                        $ret['group']['facebook'][] =  $atts;
                    }
                }

                $ret['group']['polygon'] = presdef('polygon', $_REQUEST, FALSE) ? $g->getPrivate('poly') : NULL;

                break;
            }

            case 'PATCH': {
                $settings = presdef('settings', $_REQUEST, NULL);
                $profile = intval(presdef('profile', $_REQUEST, NULL));

                $ret = [
                    'ret' => 1,
                    'status' => 'Not logged in',
                ];

                if ($me) {
                    $ret = [
                        'ret' => 1,
                        'status' => 'Failed or permission denied'
                    ];

                    if ($me->isModOrOwner($id) || $me->isAdminOrSupport()) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success'
                        ];

                        $wasonyahoo = $g->onYahoo();

                        if ($settings) {
                            $g->setSettings($settings);
                        }

                        if ($profile) {
                            # Set the profile picture.  Rescale if need be to 200x200 to save space in the DB and,
                            # more importantly, download time.
                            $g->setPrivate('profile', $profile);
                            $a = new Attachment($dbhr, $dbhm, $profile, Attachment::TYPE_GROUP);
                            $data = $a->getData();
                            $i = new Image($data);
                            
                            if ($i->width() > 200 || $i->height() > 200) {
                                $i->scale(200, 200);
                                $data = $i->getData(100);
                                $a->setPrivate('data', $data);
                            }

                            $a->setPrivate('groupid', $id);
                        }

                        # Other settable attributes
                        foreach (['tagline', 'showonyahoo', 'onyahoo', 'onhere', 'namefull', 'welcomemail', 'description', 'region'] as $att) {
                            $val = presdef($att, $_REQUEST, NULL);
                            if (array_key_exists($att, $_REQUEST)) {
                                $g->setPrivate($att, $val);
                            }
                        }

                        // @codeCoverageIgnoreStart
                        // Impractical to test.
                        $nowonyahoo = $g->onYahoo();

                        if ($wasonyahoo && !$nowonyahoo) {
                            # We are switching a group over from being on Yahoo to not being.  Enshrine the owner/
                            # mod roles and moderation status.
                            $g->setNativeRoles();
                            $g->setNativeModerationStatus();

                            #  Notify TrashNothing so that it can also do that, and talk to us rather than Yahoo.
                            $url = "https://trashnothing.com/modtools/api/switch-to-freegle-direct?key=" . TNKEY . "&group_id=" . $g->getPrivate('nameshort') . "&moderator_email=" . $me->getEmailPreferred();
                            $rsp = file_get_contents($url);
                            error_log("Move to FD on TN " . var_export($rsp, TRUE));
                        } else if (!$wasonyahoo  && $nowonyahoo) {
                            # We are switching a group over from being on here to Yahoo.  This is poorly tested.
                            $url = "https://trashnothing.com/modtools/api/switch-to-yahoo-groups?key=" . TNKEY . "&group_id=" . $g->getPrivate('nameshort') . "&moderator_email=" . $me->getEmailPreferred();
                            $rsp = file_get_contents($url);
                            error_log("Move from FD on TN " . var_export($rsp, TRUE));
                        }
                        // @codeCoverageIgnoreEnd

                        # Other support-settable attributes
                        if ($me->isAdminOrSupport()) {
                            foreach (['publish', 'licenserequired', 'lat', 'lng'] as $att) {
                                $val = presdef($att, $_REQUEST, NULL);
                                if (array_key_exists($att, $_REQUEST)) {
                                    $g->setPrivate($att, $val);
                                }
                            }

                            # For polygon attributes, check that they are valid before putting them into the DB.
                            # Otherwise, we can break the whole site.
                            foreach (['poly', 'polyofficial'] as $att) {
                                $val = presdef($att, $_REQUEST, NULL);
                                if (array_key_exists($att, $_REQUEST)) {
                                    try {
                                        $dbhr->preQuery("SELECT GeomFromText(?);", [
                                            $val
                                        ]);
                                        $g->setPrivate($att, $val);
                                    } catch (Exception $e) {
                                        $ret = [
                                            'ret' => 3,
                                            'status' => 'Invalid polygon data'
                                        ];
                                    }
                                }
                            }

                        }
                    }
                }
            }

            case 'POST': {
                switch ($action) {
                    case 'Create': {
                        $ret = [
                            'ret' => 1,
                            'status' => 'Not logged in'
                        ];

                        if ($me) {
                            $name = presdef('name', $_REQUEST, NULL);
                            $type = presdef('grouptype', $_REQUEST, NULL);
                            $lat = presdef('lat', $_REQUEST, NULL);
                            $lng = presdef('lng', $_REQUEST, NULL);
                            $core = presdef('corearea', $_REQUEST, NULL);
                            $catchment = presdef('atchmentarea', $_REQUEST, NULL);

                            $id = $g->create($name, $type);

                            $ret = ['ret' => 2, 'status' => 'Create failed'];

                            if ($id) {
                                $ret = [
                                    'ret' => 0,
                                    'status' => 'Success',
                                    'id' => $id
                                ];

                                if ($me && $me->isAdminOrSupport()) {
                                    # Admin or support can say where a group is. Not normal mods otherwise people might
                                    # trample on each other's toes.
                                    $g->setPrivate('lat', $lat);
                                    $g->setPrivate('lng', $lng);
                                    $g->setPrivate('polyofficial', $core);
                                    $g->setPrivate('poly', $catchment);
                                }
                            }
                        }

                        break;
                    }

                    case 'ConfirmKey': {
                        if ($me && $me->isAdminOrSupport()) {
                            # If we already have Admin or Support rights, we trust ourselves enough to add the
                            # membership immediately.  This helps with people who are on many groups, because
                            # it avoids having to wait for Yahoo invitation processing.
                            #
                            # If this is incorrect, and we're not actually a mod on Yahoo, then it will get
                            # downgraded on the next sync.
                            $me->addMembership($id, User::ROLE_MODERATOR);
                            $ret = [
                                'ret' => 100,
                                'status' => 'Added status on server.'
                            ];
                        } else {
                            $ret = [
                                'ret' => 0,
                                'status' => 'Success',
                                'key' => $g->getConfirmKey()
                            ];
                        }

                        break;
                    }

                    case 'AddLicense': {
                        $voucher = presdef('voucher', $_REQUEST, NULL);
                        $ret = [
                            'ret' => 1,
                            'status' => 'Not logged in'
                        ];

                        if ($me) {
                            $rc = $g->redeemVoucher($voucher);

                            if ($rc) {
                                $ret = ['ret' => 0, 'status' => 'Success'];
                            } else {
                                $ret = ['ret' => 2, 'status' => 'Failed'];
                            }
                        }

                        break;
                    }

                    case 'AddFacebookGroup': {
                        $name = presdef('name', $_REQUEST, NULL);
                        $facebookid = presdef('facebookid', $_REQUEST, NULL);
                        $ret = ['ret' => 2, 'status' => 'Invalid parameters'];

                        if ($id && $name && $facebookid) {
                            $f = new GroupFacebook($dbhr, $dbhm);
                            $f->add($id, NULL, $name, $facebookid, GroupFacebook::TYPE_GROUP);
                            $ret = ['ret' => 0, 'status' => 'Success'];
                        }

                        break;
                    }

                    case 'RemoveFacebookGroup': {
                        $uid = intval(presdef('uid', $_REQUEST, NULL));
                        $ret = ['ret' => 2, 'status' => 'Invalid parameters'];

                        if ($uid) {
                            $f = new GroupFacebook($dbhr, $dbhm);
                            $f->remove($uid);
                            $ret = ['ret' => 0, 'status' => 'Success'];
                        }

                        break;
                    }
                }

                break;
            }
        }
    } else {
        $ret = [
            'ret' => 2,
            'status' => 'We don\'t host that group'
        ];
    }

    return($ret);
}
