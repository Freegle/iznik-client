<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

# We want to pre-cache all Freegle groups.
$groups = $dbhr->preQuery("SELECT id FROM groups WHERE type = 'Freegle' AND publish = 1;");
foreach ($groups as $group) {
    $dbhm->preExec("INSERT IGNORE INTO prerender (url) VALUES (?);", [ "https://" . USER_SITE . "/explore/{$group['id']}" ]);
}

$pages = $dbhr->preQuery("SELECT id, url FROM prerender WHERE html IS NULL OR HOUR(TIMEDIFF(NOW(), retrieved) * 60) >= timeout;");
foreach ($pages as $page) {
    $url = $page['url'] . "?nocache=1";
    $file_name = tempnam('/tmp', 'prerender_') . ".html";
    $job_file = tempnam('/tmp', 'prerender_') . ".js";
    error_log("Fetch $url using $job_file");

    # Create phantomjs script which loads the page, and then waits until a time has passed during which there have
    # been no new network requests.  That tells us that the page has loaded.
    $src = "
                var page = new WebPage();
                page.settings.userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:16.0) Gecko/20120815 Firefox/16.0';
                var fs = require('fs');
                var requests = 0;
                
                page.onResourceRequested = function(request) {
                    console.log('Requested', request.url);
                    requests++;
                };
                
                page.onLoadFinished = function(status) {
                    interval = setInterval(function() {
                        console.log('Check finished', requests);
                        if (requests == 0) {
                            var bodyhtml = page.evaluate(function() {
                                return document.body.outerHTML;
                            });
                            fs.write('{$file_name}', bodyhtml, 'w');
                            phantom.exit();
                        }
                        
                        requests = 0;
                    }, 10000);
                }
                page.open('{$url}');
            ";

    file_put_contents($job_file, $src);
    exec("phantomjs --ssl-protocol=tlsv1 $job_file");
    $html = file_get_contents($file_name);
    unlink($file_name);
    #unlink($job_file);

    if ($html && strlen($html) > 100) {
        $rc = $dbhm->preExec("UPDATE prerender SET html = ? WHERE id = ?;", [ $html, $page['id'] ]);
        if ($rc) {
            error_log("...saved");
        } else {
            error_log("...failed to save");
        }
    } else {
        error_log("...failed to fetch");
    }
}

