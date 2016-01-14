<?php
function user() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $yahooUserId = presdef('yahooUserId', $_REQUEST, NULL);
    $subject = presdef('subject', $_REQUEST, NULL);
    $body = presdef('body', $_REQUEST, NULL);
    $stdmsgid = presdef('stdmsgid', $_REQUEST, NULL);
    $action = presdef('action', $_REQUEST, NULL);

    if (!$id && $yahooUserId) {
        # We don't know our unique ID, but we do know the Yahoo one. Find it.
        $u = new User($dbhr, $dbhm);
        $id = $u->findByYahooId($yahooUserId);
    }

    $email = presdef('email', $_REQUEST, NULL);
    if (!$id && $email) {
        # We still don't know our unique ID, but we do know an email.  Find it.
        $u = new User($dbhr, $dbhm);
        $id = $u->findByEmail($email);
        error_log("Looked up $email to $id");
    }

    $yahooDeliveryType = presdef('yahooDeliveryType', $_REQUEST, NULL);
    $yahooPostingStatus = presdef('yahooPostingStatus', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $logs = array_key_exists('logs', $_REQUEST) ? filter_var($_REQUEST['logs'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $ctx = presdef('logcontext', $_REQUEST, NULL);

            $u = new User($dbhr, $dbhm, $id);

            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($u && $me) {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];

                $ret['user'] = $u->getPublic(NULL, TRUE, $logs, $ctx);
                $ret['logcontext'] = $ctx;
            }

            break;
        }

        case 'PATCH': {
            $u = new User($dbhr, $dbhm, $id);
            $p = new Plugin($dbhr, $dbhm);
            $l = new Log($dbhr, $dbhm);

            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($u && $me && $me->isModOrOwner($groupid)) {
                if ($yahooDeliveryType) {
                    $l->log([
                        'type' => Log::TYPE_USER,
                        'subtype' => Log::SUBTYPE_YAHOO_DELIVERY_TYPE,
                        'groupid' => $groupid,
                        'user' => $id,
                        'text' => $yahooDeliveryType
                    ]);

                    $emails = $u->getEmails();
                    foreach ($emails as $email) {
                        $p->add($groupid, [
                            'type' => 'DeliveryType',
                            'email' => $email['email'],
                            'deliveryType' => $yahooDeliveryType
                        ]);
                    }
                }

                if ($yahooPostingStatus) {
                    $l->log([
                        'type' => Log::TYPE_USER,
                        'subtype' => Log::SUBTYPE_YAHOO_POSTING_STATUS,
                        'groupid' => $groupid,
                        'user' => $id,
                        'text' => $yahooPostingStatus
                    ]);

                    $emails = $u->getEmails();
                    foreach ($emails as $email) {
                        $p->add($groupid, [
                            'type' => 'PostingStatus',
                            'email' => $email['email'],
                            'postingStatus' => $yahooPostingStatus
                        ]);
                    }
                }

                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
            }
        }

        case 'POST': {
            $u = new User($dbhr, $dbhm, $id);
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            $role = $me ? $me->getRole($groupid) : User::ROLE_NONMEMBER;

            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                $ret = [ 'ret' => 0, 'status' => 'Success' ];

                switch ($action) {
                    case 'Mail':
                        $u->mail($groupid, $subject, $body, $stdmsgid);
                        break;
                }
            }
        }
    }

    return($ret);
}
