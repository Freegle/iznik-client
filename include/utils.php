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

function dirToArray($dir) {
    $contents = array();
    # Foreach node in $dir
    foreach (scandir($dir) as $node) {
        # Skip link to current and parent folder
        if ($node == '.')  continue;
        if ($node == '..') continue;
        # Check if it's a node or a folder
        if (is_dir($dir . DIRECTORY_SEPARATOR . $node)) {
            # Add directory recursively, be sure to pass a valid path
            # to the function, not just the folder's name
            $contents[$node] = dirToArray($dir . DIRECTORY_SEPARATOR . $node);
        } else {
            # Add node, the keys will be updated automatically
            $contents[] = $node;
        }
    }
    # done
    return $contents;
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
