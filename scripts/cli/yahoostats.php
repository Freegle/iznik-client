<?php

$opts = getopt('g:');

$groupname = $opts['g'];

$url = "https://groups.yahoo.com/api/v1/groups/$groupname/history";
error_log($url);
$html = file_get_contents($url);
$data = json_decode($html, true);
$history = $data['ygData']['messageHistory'];
foreach ($history as $year) {
    $theyear = $year['year'];
    if (!array_key_exists($theyear, $dates)) {
        $dates[$theyear] = array();
    }

    foreach ($year['months'] as $month) {
        $themonth = $month['month'];

        if (!array_key_exists($themonth, $dates[$theyear])) {
            $dates[$theyear][$themonth] = array();
        }

        $dates[$theyear][$themonth] = $month['messageCount'];
    }
}

ksort($dates, SORT_NUMERIC);

foreach ($dates as $theyear => $year) {
    foreach ($year as $month => $count) {
        error_log("$theyear-$month-01, $count");
    }
}