<?php

$opts = getopt('g:');

$groupname = $opts['g'];

$page = 1;
$counts = [];

do {
    $url = "https://groups.freecycle.org/group/$groupname/posts/all?page=$page&resultsperpage=10&showall=off&include_offers=off&include_wanteds=off&include_receiveds=on&include_takens=on";
    $data = file_get_contents($url);
    if (strpos($data, "<h1>There were no matching messages</h1>") !== FALSE) {
        # No more.
        error_log("No more at page $page");
        break;
    }

    $p = strpos($data, "<table id='group_posts_table'>");

    $q = strpos($data, "</table>", $p);
    $data = substr($data, $p, $q);
    if (preg_match_all('/\<br \/\> (.*?)\<br \/\>/m', $data, $matches)) {
        foreach ($matches[1] as $date) {
            $notime = preg_replace('/\d\d:\\d\d:\\d\d /', '', $date);
            error_log("Date $date $notime");
            if (!array_key_exists($notime, $counts)) {
                $counts[$notime] = 1;
            } else {
                $counts[$notime]++;
            }
        }
    }

    $page++;
} while (TRUE);

foreach ($counts as $date => $count) {
    print "$date, $count\n";
}