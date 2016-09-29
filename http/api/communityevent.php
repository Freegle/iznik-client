<?php
function communityevent() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));

    if ($groupid) {
        # This might be a legacy groupid.
        $g = Group::get($dbhr, $dbhm, $groupid);
        $groupid = $g->getId();
    }

    $pending = array_key_exists('pending', $_REQUEST) ? filter_var($_REQUEST['pending'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $ctx = presdef('context', $_REQUEST, NULL);
    
    $c = new CommunityEvent($dbhr, $dbhm, $id);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    if ($id && $c->getId() || $_REQUEST['type'] == 'POST' || (!$id && $_REQUEST['type'] == 'GET')) {
        switch ($_REQUEST['type']) {
            case 'GET': {
                if ($id) {
                    # We're not bothered about privacy of events - people may not be logged in when they see them.
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'communityevent' => $c->getPublic()
                    ];
                } else if ($groupid) {
                    # List for a specific group - which we can do even if not logged in.
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'communityevents' => $c->listForGroup($pending, $groupid, $ctx),
                        'context' => $ctx
                    ];
                } else {
                    # List all for this user.
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];

                    if ($me) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'communityevents' => $c->listForUser($me->getId(), $pending, $ctx),
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

                    foreach (['title', 'location', 'contactname', 'contactphone', 'contactemail', 'contacturl', 'description'] as $att) {
                        $$att = presdef($att, $_REQUEST, NULL);
                    }

                    $id = NULL;

                    if ($title && $location && $description) {
                        $id = $c->create($me->getId(), $title, $location, $contactname, $contactphone, $contactemail, $contacturl, $description);
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
                        case 'AddDate': $c->addDate(presdef('start', $_REQUEST, NULL), presdef('end', $_REQUEST, NULL)); break;
                        case 'RemoveDate': $c->removeDate(intval(presdef('dateid', $_REQUEST, NULL))); break;
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
