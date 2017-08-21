<?php
function logs() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $logtype = presdef('logtype', $_REQUEST, NULL);
    $logsubtype = presdef('logsubtype', $_REQUEST, NULL);
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $msgid = intval(presdef('msgid', $_REQUEST, NULL));
    $userid = intval(presdef('useridid', $_REQUEST, NULL));
    $date = intval(presdef('date', $_REQUEST, NULL));
    $search = presdef('search', $_REQUEST, NULL);
    $ctx = presdef('context', $_REQUEST, NULL);
    $limit = intval(presdef('limit', $_REQUEST, 20));

    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = [ 'ret' => 2, 'status' => 'Not moderator' ];

            if ($me->isAdminOrSupport() || $me->isModOrOwner($groupid)) {
                $ret = [ 'ret' => 0, 'status' => 'Success' ];

                $l = new Log($dbhr, $dbhm);

                switch ($logtype) {
                    case 'messages': {
                        $types = [ Log::TYPE_MESSAGE ];
                        $subtypes = $logsubtype ? [ $logsubtype ] : [ Log::SUBTYPE_RECEIVED, Log::SUBTYPE_APPROVED, Log::SUBTYPE_REJECTED, Log::SUBTYPE_DELETED, Log::SUBTYPE_AUTO_REPOSTED, Log::SUBTYPE_AUTO_APPROVED, Log::SUBTYPE_OUTCOME ];
                        break;
                    }
                }

                $ctx = $ctx ? $ctx : [];

                $ret['logs'] = $l->get($types, $subtypes, $groupid, $date, $search, $limit, $ctx);
            }
            $ret['context'] = $ctx;
        }
        break;
    }

    return($ret);
}
