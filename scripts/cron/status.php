<?php

require_once('../../include/config.php');

# This is run from cron to check status, which can then be returned from the API.
@unlink('/tmp/iznik.status');

function status()
{
    global $dbhr, $dbhm;

    $ret = ['ret' => 100, 'status' => 'Unknown verb'];

    $hosts = explode(',', SERVER_LIST);

    $info = [];

    $overallerror = FALSE;
    $overallwarning = FALSE;

    foreach ($hosts as $host) {
        # Each host runs monit, so we ssh in and see what's happening.
        error_log("Check $host");
        $error = FALSE;
        $warning = FALSE;
        $warningtext = NULL;
        $errortext = NULL;

        $op = shell_exec("ssh -oStrictHostKeyChecking=no root@$host monit summary 2>&1");
        #error_log("$host returned $op err " );
        $info[$host]['monit'] = $op;

        if (strpos($op, "The Monit daemon") === FALSE) {
            # Failed to monit.  That doesn't necessarily mean we're in trouble as the underlying components might
            # be ok.
            $warning = TRUE;
            $overallwarning = TRUE;
        } else {
            $lines = explode("\n", $op);

            for ($i = 2; $i < count($lines); $i++) {
                if (strlen(trim($lines[$i]))> 0) {
                    if (preg_match('/(Not monitored)/', $lines[$i])) {
                        error_log("Failed on $host - $lines[$i]");
                        $warning = TRUE;
                        $warningtext = "$host - $lines[$i]";
                        $overallwarning = TRUE;
                    } else if (!preg_match('/(Running)|(Accessible)|(Status ok)/', $lines[$i])) {
                        error_log("Failed on $host - $lines[$i]");
                        $error = TRUE;
                        $errortext = "$host - $lines[$i]";
                        $overallerror = TRUE;
                    }
                }
            }
        }

        $info[$host]['error'] = $error;
        $info[$host]['errortext'] = $errortext;
        $info[$host]['warning'] = $warning;
        $info[$host]['warningtext'] = $errortext;
    }

    $ret = [
        'ret' => 0,
        'status' => 'Success',
        'error' => $overallerror,
        'warning' => $overallwarning,
        'info' => $info
    ];


# Set up the plain text HTML file.
    $updated = date(DATE_RSS, time());

    $html = "<!DOCTYPE HTML>
<html>
    <head>
        <title>Status</title>
        
        <link rel=\"stylesheet\" href=\"/css/bootstrap.min.css\">
        <link rel=\"stylesheet\" href=\"/css/bootstrap-theme.min.css\">
        <link rel=\"stylesheet\" href=\"/css/glyphicons.css\">
        <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/style.css?a=177\">
        <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/modtools.css?a=135\">
    </head>
    <body>
        <h1>System Status</h1>
        <p>Last updated at $updated.</p>
         <h2>Overall Status</h2>";

    if ($overallerror) {
        $html .= "<div class=\"alert alert-danger\">There is a serious problem.  Please make sure the Geeks are investigating.</div>";
    } else if ($overallwarning) {
        $html .= "<div class=\"alert alert-warning\">There is a problem.  Please alert the Geeks if this persists for more than an hour.</div>";
    } else {
        $html .= "<div class=\"alert alert-success\">Everything seems fine.</div>";
    }

    foreach ($hosts as $host) {
        $html .= "<h2>$host</h2>";

        $i = $info[$host];

        if ($i['error']) {
            $html .= "<div class=\"alert alert-danger\">There is a serious problem with $host.</div>";
        } else if ($i['warning']) {
            $html .= "<div class=\"alert alert-warning\">There is a problem with $host.</div>";
        } else {
            $html .= "<div class=\"alert alert-success\">$host seems fine.</div>";
        }

        if ($i['error'] || $i['warning']) {
            $html .= "<p>Details:</p>";
            $html .= nl2br($i['monit']);
        }
    }

    $html .="
    <script>
        window.setTimeout(function() {
            document.location = '/status.html?' + (new Date()).getTime();
        }, 30000);
    </script>
    </body>
</html>";

    file_put_contents(IZNIK_BASE . '/http/status.html', $html);

    return($ret);
}

# Put into cache file for API call.
file_put_contents('/tmp/iznik.status', json_encode(status()));
