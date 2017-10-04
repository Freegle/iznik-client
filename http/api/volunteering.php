<?php
function volunteering() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    $id = intval(presdef('id', $_REQUEST, NULL));
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));

    if ($groupid) {
        # This might be a legacy groupid.
        $g = Group::get($dbhr, $dbhm, $groupid);
        $groupid = $g->getId();
    }

    $pending = array_key_exists('pending', $_REQUEST) ? filter_var($_REQUEST['pending'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $systemwide = array_key_exists('systemwide', $_REQUEST) ? filter_var($_REQUEST['systemwide'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $ctx = presdef('context', $_REQUEST, NULL);

    $c = new Volunteering($dbhr, $dbhm, $id);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    if ($id && $c->getId() || $_REQUEST['type'] == 'POST' || (!$id && $_REQUEST['type'] == 'GET')) {
        switch ($_REQUEST['type']) {
            case 'GET': {
                if ($id) {
                    # We're not bothered about privacy of volunteering - people may not be logged in when they see them.
                    $ret = [ 'ret' => 3, 'status' => 'Deleted' ];

                    if (!$c->getPrivate('deleted')) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'volunteering' => $c->getPublic()
                        ];

                        $ret['volunteering']['canmodify'] = $c->canModify($myid);
                    }
                } else if ($groupid) {
                    # List for a specific group - which we can do even if not logged in.
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'volunteerings' => $c->listForGroup($pending, $groupid, $ctx),
                        'context' => $ctx
                    ];
                } else {
                    # List all for this user.
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];

                    if ($me) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'volunteerings' => $c->listForUser($me->getId(), $pending, $systemwide, $ctx),
                            'context' => $ctx
                        ];
                    }
                }
                break;
            }

            case 'POST': {
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($me) {
                    $title = $location = $contactname = $contactphone = $contactemail = $contacturl = $description = NULL;

                    foreach (['title', 'online', 'location', 'contactname', 'contactphone', 'contactemail', 'contacturl', 'description', 'timecommitment'] as $att) {
                        $$att = presdef($att, $_REQUEST, NULL);
                    }

                    $id = NULL;

                    if ($title && $location && $description) {
                        $id = $c->create($me->getId(), $title, $online, $location, $contactname, $contactphone, $contactemail, $contacturl, $description, $timecommitment);

                        if (pres('groupid', $_REQUEST)) {
                            $c->addGroup(intval($_REQUEST['groupid']));
                        }
                    }

                    $ret = $id ? ['ret' => 0, 'status' => 'Success', 'id' => $id] : ['ret' => 2, 'status' => 'Create failed'];
                }

                break;
            }

            case 'PUT':
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($me && $c->canModify($me->getId())) {
                    $c->setAttributes($_REQUEST);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;

            case 'PATCH': {
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($me && $c->canModify($me->getId())) {
                    $c->setAttributes($_REQUEST);

                    switch (presdef('action', $_REQUEST, NULL)) {
                        case 'AddGroup': $c->addGroup(intval(presdef('groupid', $_REQUEST, 0))); break;
                        case 'RemoveGroup': $c->removeGroup(intval(presdef('groupid', $_REQUEST, 0))); break;
                        case 'AddDate': $c->addDate(presdef('start', $_REQUEST, NULL), presdef('end', $_REQUEST, NULL), presdef('applyby', $_REQUEST, NULL)); break;
                        case 'RemoveDate': $c->removeDate(intval(presdef('dateid', $_REQUEST, NULL))); break;
                        case 'Renew':
                            # Set the renewal date.  Also make sure it's not marked as expired, in case they
                            # do this after that has happened.
                            $c->setPrivate('renewed', date("Y-m-d H:i:s", time()));
                            $c->setPrivate('expired', 0);
                            break;
                        case 'Expire':
                            $c->setPrivate('expired', 1);
                            break;
                    }

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;
            }

            case 'DELETE': {
                $ret = ['ret' => 1, 'status' => 'Not logged in'];

                if ($me && $c->canModify($me->getId())) {
                    $c->delete();

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                }
                break;
            }
        }
    } else {
        $ret = [
            'ret' => 2,
            'status' => 'Invalid id'
        ];
    }

    return($ret);
}
