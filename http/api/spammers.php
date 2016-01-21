<?php
function spammers() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));
    $userid = intval(presdef('userid', $_REQUEST, NULL));
    $collection = presdef('collection', $_REQUEST, Spam::TYPE_SPAMMER);
    $reason = presdef('reason', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($collection) {
        case Spam::TYPE_SPAMMER:
        case Spam::TYPE_WHITELIST:
        case Spam::TYPE_PENDING_ADD:
        case Spam::TYPE_PENDING_REMOVE:
            break;
        default:
            $collection = NULL;
    }

    $s = new Spam($dbhr, $dbhm);
    $ret = ['ret' => 1, 'status' => 'Not logged in'];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = [ 'ret' => 0, 'status' => 'Success', 'spammers' => $s->listSpammers($collection, $context) ];
            $ret['context'] = $context;
            break;
        }

        case 'POST': {
            if ($me) {
                if ($me->isAdminOrSupport() || $collection == Spam::TYPE_PENDING_ADD) {
                    # Admin/Support can do anything; anyone can submit a spammer.
                    $ret = ['ret' => 0, 'status' => 'Success', 'id' => $s->addSpammer($userid, $collection, $reason)];
                } else {
                    # Not allowed
                    $ret = ['ret' => 2, 'status' => 'Permission denied'];
                }
            }
            break;
        }

        case 'PATCH': {
            if ($me) {
                $spammer = $s->getSpammer($id);
                $ret = ['ret' => 2, 'status' => 'Permission denied'];

                error_log("Got $collection, $id spammer " . var_export($spammer, true));

                if ($spammer) {
                    if ($me->isAdminOrSupport()) {
                        # Admin/Support can do anything
                        $ret = ['ret' => 0, 'status' => 'Success', 'id' => $s->updateSpammer($userid, $collection, $reason)];
                    } else if ($me->isModerator() &&
                        ($spammer['collection'] == Spam::TYPE_SPAMMER) &&
                        ($collection == Spam::TYPE_PENDING_REMOVE)) {
                        # Mods can request removal
                        $ret = ['ret' => 0, 'status' => 'Success', 'id' => $s->updateSpammer($userid, $collection, $reason)];
                    }
                }
            }
            break;
        }

        case 'DELETE': {
            if ($me) {
                $ret = ['ret' => 2, 'status' => 'Permission denied'];

                if ($me->isAdminOrSupport()) {
                    # Only Admin/Support can remove.
                    $ret = [ 'ret' => 0, 'status' => 'Success' ];
                    $s->deleteSpammer($id, $reason);
                }
            }
            break;
        }
    }

    return($ret);
}
