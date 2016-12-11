<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$opts = getopt('i:');

if (count($opts) == 0) {
    echo "Usage: hhvm prerender.php -i ID\n";
} else {
    $id = presdef('i', $opts, NULL);
    $pages = $dbhr->preQuery("SELECT id, url FROM prerender WHERE id = ?;", [$id]);
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
                
                page.settings.resourceTimeout = 12000; 
                page.onResourceTimeout = function(e) {
                  console.log(e.errorCode);   // it'll probably be 408 
                  console.log(e.errorString); // it'll probably be 'Network timeout on resource'
                  console.log(e.url);         // the url whose request timed out
                  phantom.exit(1);
                };
                                
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
                            var title = page.evaluate(function() {
                                return document.title;
                            });
                            fs.write('{$file_name}.title', title, 'w');
                           
                            var description = page.evaluate(function() {
                                var metas = document.getElementsByTagName('meta');
                                var desc = null; 
    
                                for (var i = 0; i < metas.length; i++) { 
                                    if (metas[i].getAttribute('property') == 'description') {
                                        desc = metas[i].getAttribute('content'); 
                                    }
                                } 
                                
                                return(desc);
                            });
                            
                            fs.write('{$file_name}.description', description, 'w');                                                         

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
        $title = file_get_contents("$file_name.title");
        $desc = file_get_contents("$file_name.description");
        unlink($file_name);
        unlink("$file_name.title");
        unlink("$file_name.description");
        unlink($job_file);

        if ($html && strlen($html) > 100) {
            $rc = $dbhm->preExec("UPDATE prerender SET html = ?, title = ?, description = ? WHERE id = ?;", [
                $html,
                strlen($title) > 0 ? $title : NULL,
                strlen($desc) > 0 ? $desc : NULL,
                $page['id']]);
            if ($rc) {
                error_log("...saved");
            } else {
                error_log("...failed to save");
            }
        } else {
            error_log("...failed to fetch");
        }
    }
}
