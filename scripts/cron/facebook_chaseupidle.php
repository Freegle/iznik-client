<?php
# Spot idle members and chase them via Facebook notifications.

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/session/Facebook.php');

use Pheanstalk\Pheanstalk;

global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$f = new Facebook($dbhr, $dbhm);

# Randomise the order in case we get blocked by Facebook, so that all groups get a lookin.
$sql = "SELECT id, nameshort FROM groups WHERE `type` = 'Freegle' AND onhere = 1 AND publish = 1 AND id NOT IN (21260, 21589, 21354, 21241, 21439, 21333) ORDER BY RAND() ASC;";
$groups = $dbhr->preQuery($sql);

foreach ($groups as $group) {
    $count = 0;

    # Find users with Facebook logins who have not been active for 31 days.
    $mysqltime = date ("Y-m-d", strtotime("Midnight 31 days ago"));
    $users = $dbhr->preQuery("SELECT users_logins.uid, users.lastaccess FROM users_logins INNER JOIN memberships ON memberships.userid = users_logins.userid AND groupid = ? INNER JOIN users ON users.id = memberships.userid WHERE users.lastaccess < ? AND  type = ?;", [
        $group['id'],
        $mysqltime,
        User::LOGIN_FACEBOOK
    ]);

    foreach ($users as $user) {
        $f->notify($user['uid'], "We miss you!  We'd love you to give something, find something, or just have a look what's happening.  We're just a click away...", '/?src=fbchase');
        $count++;
    }

    error_log("...{$group['nameshort']} $count");

    # Wait for background queue to subside so that we pace things a bit.
    $pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);
    $start = time();

    do {
        try {
            $stats = $pheanstalk->stats();
            $ready = $stats['current-jobs-ready'];

            $job = $pheanstalk->peekReady();
            $data = json_decode($job->getData(), true);

            error_log("...waiting for background work, current $ready queued " . date('r', $data['queued']) . " vs " . date('r', $start));

            if ($data['queued'] > $start) {
                break;
            }
        } catch (Exception $e) {}

        sleep(5);
        $count++;
    } while (TRUE);
}

unlockScript($lockh);