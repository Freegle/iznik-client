<?php
function stories() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $id = presdef('id', $_REQUEST, NULL);
    $groupid = presdef('groupid', $_REQUEST, NULL);
    $reviewed = intval(array_key_exists('reviewed', $_REQUEST) ? $_REQUEST['reviewed'] : 1);
    $s = new Story($dbhr, $dbhm, $id);
    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = [ 'ret' => 3, 'status' => 'Invalid id' ];
            if ($id) {
                $ret = ['ret' => 2, 'status' => 'Permission denied'];

                if ($s->canSee()) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'story' => $s->getPublic()
                    ];
                }
            } else if ($me && $reviewed === 0) {
                $groupids = [ $groupid ];

                if (!$groupid) {
                    # We want to see the ones on groups we mod.
                    $mygroups = $me->getMemberships(TRUE);
                    $groupids = [];
                    foreach ($mygroups as $mygroup) {
                        # This group might have turned stories off.
                        $g = new Group($dbhr, $dbhm, $mygroup['id']);
                        if ($g->getSetting('stories', 1)) {
                            $groupids[] = $mygroup['id'];
                        }
                    }
                }

                $stories = [];

                if (count($groupids) > 0) {
                    $stories = $s->getForReview($groupids);
                }

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'stories' => $stories
                ];
            } else {
                # We want to see the most recent few
                $stories = $s->getStories($groupid);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'stories' => $stories
                ];
            }

            break;
        }

        case 'PUT':
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];
            if ($me) {
                $id = $s->create($me->getId(),
                    array_key_exists('public', $_REQUEST) ? filter_var($_REQUEST['public'], FILTER_VALIDATE_BOOLEAN) : FALSE,
                    presdef('headline', $_REQUEST, NULL),
                    presdef('story', $_REQUEST, NULL));
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'id' => $id
                ];
            }
            break;

        case 'PATCH': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            if ($s->canMod()) {
                $s->setAttributes($_REQUEST);
                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
            }
            break;
        }

        case 'DELETE': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            if ($s->canMod()) {
                $s->delete();

                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
            }
            break;
        }
    }

    return($ret);
}
