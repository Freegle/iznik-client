<?php

function getVersion() {
    $directory = new RecursiveDirectoryIterator(IZNIK_BASE);
    $flattened = new RecursiveIteratorIterator($directory);
    $files = new RegexIterator($flattened, '/.*\.((php)|(html)|(js)|(css))/i');

    $max = 0;

    foreach ($files as $filename=>$cur) {
        $time = $cur->getMTime();
        $max = max($max, $time);
    }

    return($max);
}

# We take the minify function as a parameter to ease UT.
function scriptInclude($minify)
{
    $jsfiles = array(
        "js/lib/require.js",
        "js/requirejs-setup.js",
        "js/iznik/main.js"
    );

    $ret = [];

    $cachefile = NULL;

    if (!$minify) {
        # Just output them as script tags.  Add a timestamp so that if we change the file, the client will reload.
        foreach ($jsfiles as $jsfile) {
            $thisone = file_get_contents(IZNIK_BASE . "/http/$jsfile");
            if (!$thisone) { error_log("Failed to log $jsfile"); }
            $ret[] = "<script>$thisone</script>\n";
        }
    }
    else
    {
        # We combine the scripts into a single minified file.  We cache this based on an MD5 hash of the file modification times
        # so that if we change the script, we will regenerate it.
        $tosign = '';
        foreach ($jsfiles as $jsfile) {
            $tosign .= date("YmdHis", filemtime(IZNIK_BASE . "/http/$jsfile")) . $jsfile;
        }

        $hash = md5($tosign);
        $cachefile = IZNIK_BASE . "/http/jscache/$hash.js";

        if (!file_exists($cachefile))
        {
            # We do not already have a minified version cached.
            # We need to generate a minified version.
            @mkdir(IZNIK_BASE . '/http/jscache/');
            $js = '';
            foreach ($jsfiles as $jsfile) {
                $thisone = file_get_contents(IZNIK_BASE . "/http/$jsfile");
                try {
                    $mind = $minify($thisone);
                    $js .= $mind;
                    error_log("Minified $jsfile from " . strlen($thisone) . " to " . strlen($mind));
                } catch (Exception $e) {
                    error_log("Minify $jsfile len " . strlen($thisone) . " failed");
                    $js .= $thisone;
                }
            }

            error_log("Minifed len " . strlen($js));
            file_put_contents($cachefile, $js);
        }

        $ret[] = "<script type=\"text/javascript\" src=\"/jscache/$hash.js\"></script>";
    }

    return([$cachefile, $ret]);
}

?>