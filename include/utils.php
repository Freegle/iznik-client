<?php

function tmpdir() {
    $tempfile=tempnam(sys_get_temp_dir(),'');
    if (file_exists($tempfile)) { unlink($tempfile); }
    mkdir($tempfile);
    if (is_dir($tempfile)) { return $tempfile; }
    return null;
}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

function get_current_url() {
    $current_url  = 'http';
    $server_https = pres('HTTPS', $_SERVER);
    $server_name  = pres('SERVER_NAME', $_SERVER);
    $request_uri  = pres('REQUEST_URI', $_SERVER);
    if ($server_https == "on") $current_url .= "s";
    $current_url .= "://";
    $current_url .= $server_name . $request_uri;
    #error_log("Current url $current_url");
    return $current_url;
}

function pres($key, $arr) {
    return($arr && array_key_exists($key, $arr) && $arr[$key] ? $arr[$key] : FALSE);
}

function presdef($key, $arr, $def) {
    if ($arr && array_key_exists($key, $arr) && $arr[$key]) {
        return($arr[$key]);
    } else {
        return($def);
    }
}

function filterArray(&$array, $skip = NULL) {
    foreach($array as $key=>$var){

        # PDO can return numeric indices, which we don'[t want to pass out.
        #error_log("key $key exists " . array_key_exists($key, $array) . " val {$array[$key]} vs $var int " . is_int($key) . " numeric " . is_numeric($key));
        #print "$key type ". gettype($array[$key]) . " null? " . is_null($array[$key]) . "\n";
        if ($skip && (array_search($key, $skip) !== false)) {
            # Asked to do nothing
        } else if (is_null($array[$key])) {
            unset($array[$key]);
        } else if (is_int($key) || is_numeric($key)) {
            unset($array[$key]);
        } else if (is_array($var)) {
            filterArray($array[$key]);
        } else if ((array_key_exists($key, $array)) && (gettype($array[$key]) == 'string') && (strlen($array[$key]) == 0)) {
            # There is no value here worth returning.
            unset($array[$key]);
        } else if ((is_numeric($array[$key])) && (strpos(',', $array[$key]) === false)) {
            # This is an integer value.  We want to return it as an int rather than a string,
            # not least for boolean values which would otherwise require a parseInt on the client.
            $array[$key] = intval($array[$key]);
        } else {
            # This is a hack which flattens odd characters to avoid json_encode returning null.
            $array[$key] = @iconv('UTF-8', 'UTF-8//IGNORE', $array[$key]);
        }
    }
}

function getCpuUsage() {
    global $tusage, $rusage;
    $dat = getrusage();
    $dat["ru_utime.tv_usec"] = ($dat["ru_utime.tv_sec"]*1e6 + $dat["ru_utime.tv_usec"]) - $rusage;
    $time = (microtime(true) - $tusage) * 1000000;

    // cpu per request
    if($time > 0) {
        $cpu = $dat["ru_utime.tv_usec"] / $time / 1000;
    } else {
        $cpu = 0;
    }

    return $cpu;
}

// equiv to rand, mt_rand
// returns int in *closed* interval [$min,$max]
function devurandom_rand($min = 1, $max = 0x7FFFFFFF) {
    if (function_exists('mcrypt_create_iv')) {
        $diff = $max - $min;
        if ($diff < 0 || $diff > 0x7FFFFFFF) {
            throw new RuntimeException("Bad range");
        }
        $bytes = mcrypt_create_iv(4, MCRYPT_DEV_URANDOM);
        if ($bytes === false || strlen($bytes) != 4) {
            throw new RuntimeException("Unable to get 4 bytes");
        }
        $ary = unpack("Nint", $bytes);
        $val = $ary['int'] & 0x7FFFFFFF;   // 32-bit safe
        $fp = (float)$val / 2147483647.0; // convert to [0,1]
        return round($fp * $diff) + $min;
    } else {
        return mt_rand($min, $max);
    }
}
