<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/plugin.php');


$p = new Plugin($dbhr, $dbhm);

$users = $dbhr->preQuery("SELECT groupid, email, yahooDeliveryType FROM users_emails INNER JOIN memberships_yahoo on emailid = users_emails.id INNER JOIN memberships ON memberships.id = memberships_yahoo.membershipid WHERE (email like 'fbuser%' or backwards like 'gro.elgeerfevoli.sresu%')  AND yahooDeliveryType != 'NONE' ");

foreach ($users as $user) {
    $p->add($user['groupid'], [
        'type' => 'DeliveryType',
        'email' => $user['email'],
        'deliveryType' => 'NONE'
    ]);
}
