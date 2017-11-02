<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$lockh = lockScript(basename(__FILE__));

$pace = 100000;

$siteurl = 'https://www.avivacommunityfund.co.uk/acfcms/sitefinity/public/services/custom/projectservice.svc/BrowseProjects';

$urls = [ 'https://www.avivacommunityfund.co.uk/voting/project/view/17-1949' ];

# Get all the projects.
$page = 1;

do {
    error_log("Get page $page, urls " . count($urls));
    $last = count($urls);

    $data = [
        'CentreLatitude' => "55.3781",
        'CentreLongitude' => "3.4360",
        'FundingLevelId' => "4",
        'PageNumber' => $page,
        'TerritoryCode' => "uk",
        'UICulture' => "en",
        'ZoomLevel' => "0"
    ];

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($siteurl, false, $context);

    if ($result) {
        if (preg_match_all('/(voting..project..view...*?)\"/m', $result, $matches)) {
            foreach ($matches as $val) {
                foreach ($val as $url) {
                    $url = "https://www.avivacommunityfund.co.uk/" . str_replace('\\', '', str_replace('"', '', $url));
                    $urls[] = $url;
                }
            }
        }
    } else {
        break;
    }

    $page++;
    usleep($pace);
} while (count($urls) != $last);

$urls = array_unique($urls);

foreach ($urls as $url) {
    $data = file_get_contents($url);
    $p = strrpos($url, '/');
    $id = substr($url, $p + 1);
    error_log("ID $id from $p in $url");
    $votes = NULL;
    $title = NULL;

    if (preg_match('/<span class="votes-count">(.*)<\/span/', $data, $matches)) {
        $votes = str_replace(',', '', $matches[1]);
    }

    if (preg_match('/<title>(.*) \|/', $data, $matches)) {
        $title = $matches[1];
    }

    error_log("$id, $title, $votes");
    if ($id && $title && $votes) {
        $dbhm->preExec("INSERT INTO aviva_votes (project, name, votes) VALUES (?, ?, ?);", [
            $id,
            $title,
            $votes
        ]);

        usleep($pace);
    }
}

unlockScript($lockh);