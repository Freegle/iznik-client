<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

ini_set('default_socket_timeout', 10);

$lockh = lockScript(basename(__FILE__));

$pace = 100000;

$id = 1;

# We know from the site that there are fewer than 7K projects.  This may change year on year.
for ($id = 0; $id < 7000; $id++) {

    $url = "https://www.avivacommunityfund.co.uk/voting/project/view/17-$id";

    for ($try = 0; $try < 5; $try++) {
        $data = @file_get_contents($url);

        if ($data) {
            break;
        }

        error_log("...retry");
    }

    $votes = NULL;
    $title = NULL;

    if (strpos($data, 'Funding level:  £10,001&nbsp;to&nbsp;£25,000') !== FALSE) {
        if (preg_match('/<span class="votes-count">(.*)<\/span/', $data, $matches)) {
            $votes = str_replace(',', '', $matches[1]);
        }

        if (preg_match('/<title>(.*) \|/', $data, $matches)) {
            $title = $matches[1];
        }

        error_log("$id, $title, $votes");
        if ($id && $title && $votes) {
            $dbhm->preExec("INSERT INTO aviva_votes (project, name, votes) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE timestamp = NOW(), votes = ?;", [
                $id,
                $title,
                $votes,
                $votes
            ]);

            usleep($pace);
        }
    }
}

# Now the history of our position.
$recents = $dbhm->preQuery("SELECT * FROM aviva_votes ORDER BY votes DESC");
$position = 1;

foreach ($recents as $recent) {
    if ($recent['project'] == '1949') {
        $dbhm->preExec("INSERT INTO aviva_history (position, votes) VALUES (?,?);", [
            $position,
            $recent['votes']
        ]);
        break;
    }

    $position++;
}

unlockScript($lockh);