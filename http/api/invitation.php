<?php
function invitation()
{
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    $ret = ['ret' => 100, 'status' => 'Unknown verb'];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];
            if ($myid) {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'invitations' => $me->listInvitations()
                ];
            }
            break;
        }

        case 'PUT': {
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];
            $email = presdef('email', $_REQUEST, NULL);

            if ($myid && $email) {
                $me->invite($email);

                # Whether or not it worked, say it did.  This is so that if we have someone abusing the feature,
                # they can't tell that we've noticed.
                $ret = [ 'ret' => 0, 'status' => 'Success' ];
            }
            break;
        }

        case 'PATCH': {
            $id = intval(presdef('id', $_REQUEST, NULL));
            $outcome = presdef('outcome', $_REQUEST, User::INVITE_ACCEPTED);

            if ($id) {
                $u = new User($dbhr, $dbhm);
                $u->inviteOutcome($id, $outcome);
                $ret = [ 'ret' => 0, 'status' => 'Success' ];
            }
            break;
        }
    }

    return($ret);
}