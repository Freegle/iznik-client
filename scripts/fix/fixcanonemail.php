
<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

do {
    $sql = "SELECT * FROM users_emails WHERE canon LIKE '%.%' LIMIT 10000;";
    $emails = $dbhr->preQuery($sql);

    foreach ($emails as $email) {
        $dbhm->preExec("UPDATE users_emails SET canon = ? WHERE id = ?;", [
            User::canonMail($email['email']),
            $email['id']
        ]);

        $at++;
        if ($at % 1000 == 0) {
            error_log("...$at");
        }
    }
} while (count($emails) > 0);
