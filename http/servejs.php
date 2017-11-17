<?php
define('IZNIK_BASE', dirname(__FILE__) . '/..');

# To improve performance on the client, we catch templates in the JS we serve up.  This avoids us fetching the
# JS in one HTTP request, then multiple templates in others.

$script = array_key_exists('script', $_REQUEST) ? $_REQUEST['script'] : NULL;

if ($script) {
    $script = IZNIK_BASE . "/http/$script.js";
    $scriptmtime = filemtime($script);
    $cachefile = IZNIK_BASE . "/http/jscache/" . urlencode($script) . ".tpl";
    $cachemtime = file_exists($cachefile) ? filemtime($cachefile) : NULL;

    #error_log("Script $script times $scriptmtime vs $cachemtime");

    if ($cachemtime && $cachemtime >= $scriptmtime) {
        $str = @file_get_contents($cachefile);
        #error_log("Got cached $script");

        echo $str;
    } else {
        #error_log("Script not cached $script");
        $str .= file_get_contents($script);

        $tpls = '';

        if (preg_match_all('/template:\s*?("|\')(.*?)("|\')/m', $str, $matches)) {
            foreach ($matches[2] as $url) {
                $url = str_replace('_', '/', $url);
                $tplname = IZNIK_BASE . '/http/template/' . $url . ".html";
                $tpl = @file_get_contents($tplname);

                if ($tpl) {
                    $tpl = str_replace("'", "\'", $tpl);
                    $lines = preg_split('/\R/', $tpl);
                    $tpl = implode("' + \n'", $lines);

                    $frag = "templateStore('$url', '" . $tpl . "');";
                    #error_log("Template $tplname $frag");
                    $tpls .= $frag;
                }
            }
        }

        $str .= $tpls;

        echo $str;
        file_put_contents($cachefile, $str);
    }
}
