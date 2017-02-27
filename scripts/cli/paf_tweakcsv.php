<?php

$opts = getopt('f:');

$fh = fopen($opts['f'],'r');

while ($line = fgets($fh)){
    $line = str_replace(',,', ',\N,', $line);
    $line = str_replace(',,', ',\N,', $line);
    $line = preg_replace('/,$/', ',\N', $line);
    $line = str_replace('" "', '\N', $line);
    echo "$line";
}
