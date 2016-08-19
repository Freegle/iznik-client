<?php

$msg = '';

while(!feof(STDIN))
{
    $msg .= fread(STDIN, 1024);
}

$fh = fopen('/tmp/iznik_bounce.log', 'wa');
fwrite($fh, $msg . "\r\n\r\n");