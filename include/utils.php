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

function filterResult(&$array, $skip = NULL) {
    # PDO can return numeric indices, which we don't want to pass out.  We also want to ensure that we have the
    # correct data types - for example PDO returns floats as strings.
    $allnumeric = true;
    foreach ($array as $key=>$var) {
        if (!is_int($key) && !is_numeric($key)) {
            $allnumeric = false;
        }
    }

    foreach($array as $key => $val){
        #print "$key type ". gettype($val) . " null? " . is_null($val) . "\n";
        #error_log("Consider $key = $val " . is_numeric($val));
        
        if ($skip && (array_search($key, $skip) !== false)) {
            # Asked to do nothing
        } else if (is_null($val)) {
            unset($array[$key]);
        } else if ((is_int($key) || is_numeric($key)) && (!$allnumeric)) {
            unset($array[$key]);
        } else if (is_array($val)) {
            #error_log("Recurse $key");
            $thisone = $val;
            filterResult($val);
            $array[$key] = $val;
        } else if ((array_key_exists($key, $array)) && (gettype($val) == 'string') && (strlen($val) == 0)) {
            # There is no value here worth returning.
            unset($val);
        } else if (is_numeric($val)) {
            #error_log("Numeric");
            if (strpos($val, '.') === false) {
                # This is an integer value.  We want to return it as an int rather than a string,
                # not least for boolean values which would otherwise require a parseInt on the client.
                $array[$key] = intval($val);
            } else {
                $array[$key] = floatval($val);
            }
        } else {
            # This is a hack which flattens odd characters to avoid json_encode returning null.
            $array[$key] = @iconv('UTF-8', 'UTF-8//IGNORE', $val);
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
