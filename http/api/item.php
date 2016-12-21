<?php
function item() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $me = whoAmI($dbhr, $dbhm);

    $id = presdef('id', $_REQUEST, NULL);
    $typeahead = presdef('typeahead', $_REQUEST, NULL);
    $weightless = presdef('weightless', $_REQUEST, NULL);
    $weight = presdef('weight', $_REQUEST, NULL);
    $i = new Item($dbhr, $dbhm, $id);

    if ($id && $i->getId() || $_REQUEST['type'] == 'POST' || $typeahead || $weightless) {
        switch ($_REQUEST['type']) {
            case 'GET': {
                if ($typeahead) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'items' => $i->typeahead($typeahead)
                    ];
                } else if ($weightless) {
                    $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

                    if ($me) {
                        $ret = [ 'ret' => 2, 'status' => 'None left' ];
                        $id = $i->getWeightless($me->getId());
                        error_log("Got weightless id $id");

                        if ($id) {
                            $ret = [
                                'ret' => 0,
                                'status' => 'Success',
                                'item' => (new Item($dbhr, $dbhm, $id))->getPublic()
                            ];
                        }
                    }
                } else {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'item' => $i->getPublic()
                    ];
                }

                break;
            }

            case 'POST': {
                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else {
                    $name = presdef('name', $_REQUEST, NULL);
                    $systemrole = $me->getPublic()['systemrole'];

                    if (!$name) {
                        $ret = [
                            'ret' => 3,
                            'status' => 'Must supply name'
                        ];
                    } else if ($systemrole != User::SYSTEMROLE_MODERATOR &&
                        $systemrole != User::SYSTEMROLE_SUPPORT &&
                        $systemrole != User::SYSTEMROLE_ADMIN) {
                        $ret = [
                            'ret' => 4,
                            'status' => 'Don\t have rights to create items'
                        ];
                    } else {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'id' => $i->create($name)
                        ];
                    }
                }

                break;
            }

            case 'PUT':
            case 'PATCH': {
                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else if ($weight && $id) {
                    $i->setWeight($me->getId(), intval($weight));
                    $ret = ['ret' => 0, 'status' => 'Success'];
                } else {
                    $systemrole = $me->getPublic()['systemrole'];
                    if ($systemrole != User::SYSTEMROLE_MODERATOR &&
                        $systemrole != User::SYSTEMROLE_SUPPORT &&
                        $systemrole != User::SYSTEMROLE_ADMIN) {
                        $ret = [
                            'ret' => 4,
                            'status' => 'Don\t have rights to modify items'
                        ];
                    } else {
                        $i->setAttributes($_REQUEST);

                        $ret = [
                            'ret' => 0,
                            'status' => 'Success'
                        ];
                    }
                }
                break;
            }

            case 'DELETE': {
                if (!$me) {
                    $ret = ['ret' => 1, 'status' => 'Not logged in'];
                } else {
                    $systemrole = $me->getPublic()['systemrole'];
                    if ($systemrole != User::SYSTEMROLE_MODERATOR &&
                        $systemrole != User::SYSTEMROLE_SUPPORT &&
                        $systemrole != User::SYSTEMROLE_ADMIN) {
                        $ret = [
                            'ret' => 4,
                            'status' => 'Don\t have rights to modify items'
                        ];
                    } else {
                        $i->delete();
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success'
                        ];
                    }
                }
            }
        }
    } else {
        $ret = [
            'ret' => 2,
            'status' => 'Invalid item id'
        ];
    }

    return($ret);
}
