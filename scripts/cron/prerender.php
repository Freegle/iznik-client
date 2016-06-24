<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$pages = $dbhr->preQuery("SELECT id, url FROM prerender WHERE HOUR(TIMEDIFF(NOW(), retrieved)) >= 4;");
foreach ($pages as $page) {
    $url = $page['url'] . "?nocache=1";
    error_log($url);
    $file_name = tempnam('/tmp', 'prerender_') . ".html";
    $job_file = tempnam('/tmp', 'prerender_') . ".js";

    # Create phantomjs script
    $src = "
                var page = new WebPage();
                page.settings.userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:16.0) Gecko/20120815 Firefox/16.0';
                var fs = require('fs');
                page.onLoadFinished = function(status) {
                    setTimeout(function() {
                        var bodyhtml = page.evaluate(function() {
                            return document.body.outerHTML;
                        });
                        fs.write('{$file_name}', bodyhtml, 'w');
                        phantom.exit();
                    }, 10000);
                }
                page.open('{$url}');
            ";

    file_put_contents($job_file, $src);
    exec("phantomjs --ssl-protocol=tlsv1 $job_file");
    $html = file_get_contents($file_name);
    unlink($file_name);
    unlink($job_file);

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

