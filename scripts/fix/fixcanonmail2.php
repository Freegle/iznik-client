
<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$sql = "SELECT canon, COUNT( * ) FROM users_emails GROUP BY canon HAVING COUNT( * ) > 1";
$emails = $dbhr->preQuery($sql);
$u = new User($dbhr, $dbhm);

foreach ($emails as $email) {
    $ids = $dbhr->preQuery("SELECT userid FROM users_emails WHERE canon = ?;", [$email['canon']]);

    $to = $ids[0]['userid'];

    for ($i = 1; $i < count($ids); $i++) {
        $u->merge($to, $ids[$i]['userid'], "Fix: Same canon mail");
    }

    $at++;
    if ($at % 1000 == 0) {
        error_log("...$at");
    }
}
