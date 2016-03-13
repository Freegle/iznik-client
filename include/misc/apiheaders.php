<?php
$call = array_key_exists('call', $_REQUEST) ? $_REQUEST['call'] : NULL;
$type = array_key_exists('type', $_REQUEST) ? $_REQUEST['type'] : 'GET';

// We allow anyone to use our API.
//
// Suppress errors on the header command for UT
if (!($call == 'image' && $type == 'GET')) {
    # For images we'll set the content type later.
    @header('Content-type: application/json');
}

@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
@header('Access-Control-Allow-Credentials: true');
@header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

$tusage = NULL;
$rusage = NULL;

function onRequestStart() {
    global $tusage, $rusage;
    $dat = getrusage();
    $tusage =  microtime(true);
    $rusage = $dat["ru_utime.tv_sec"]*1e6+$dat["ru_utime.tv_usec"];
}

onRequestStart();

$apicallretries = 0;
$scriptstart = microtime(true);
