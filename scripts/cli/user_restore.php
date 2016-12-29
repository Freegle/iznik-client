<?php

# Run on backup server to recover a user from a backup to the live system.  Use with astonishing levels of caution.
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host=localhost;port=3309;dbname=iznik;charset=utf8";
$dbhback = new LoggedPDO($dsn, SQLUSER, SQLPASSWORD, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$opts = getopt('e:');

if (count($opts) < 1) {
    echo "Usage: hhvm user_restore.php -e <email to restore>\n";
} else {
    $email = $opts['e'];
    $ulive = User::get($dbhr, $dbhm);
    $luid = $ulive->findByEmail($email);
    $ulive = User::get($dbhr, $dbhm, $luid);
    error_log("User on live #$luid");

    $uback = new User($dbhback, $dbhback);
    $buid = $uback->findByEmail($email);
    $uback = new User($dbhback, $dbhback, $buid);
    error_log("User on backup #$buid");

    if ($luid && $buid) {
        # User attributes
        foreach (['fullname', 'firstname', 'lastname', 'yahooUserId', 'yahooid', 'systemrole'] as $att) {
            $val = $uback->getPrivate($att);
            $ulive->setPrivate($att, $val);
        }

        # Tables with foreign keys.
        # TODO Automate via schema inspection?
        foreach ([
            'memberships' => [ 'userid' ],
            'spam_users' => [ 'userid', 'byuserid' ],
            'users_banned' => [ 'userid' ],
            'users_logins' => [ 'userid' ],
            'users_emails' => [ 'userid' ],
            'users_comments' => [ 'userid', 'byuserid' ],
            'sessions' => [ 'userid' ],
            'messages' => [ 'fromuser' ],
            'users_push_notifications' => [ 'userid' ],
            'chat_roster' => [ 'userid' ],
            'chat_messages' => [ 'userid' ],
            'users_searches' => [ 'userid' ],
            'memberships_history' => [ 'userid' ],
            'logs' => [ 'user' ],
            'logs_api' => [ 'userid' ],
            'logs_sql' => [ 'userid' ]
                 ] as $table => $keys) {
            foreach ($keys as $key) {
                error_log("Table $table key $key");
                $rows = $dbhback->preQuery("SELECT * FROM $table WHERE $key = ?;", [ $buid ]);

                foreach ($rows as $row) {
                    error_log("  #{$row['id']}");
                    $dbhm->preExec("UPDATE $table SET $key = ? WHERE id = ?;", [
                        $luid,
                        $row['id']
                    ]);
                }
            }
        }

        # memberships_yahoo
        # locations_excluded
    }
}
