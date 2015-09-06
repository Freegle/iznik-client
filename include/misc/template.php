<?php

require_once(IZNIK_BASE . '/include/utils.php');

function addTemplate($location, $toplocation){
    $ret = [];
    foreach (scandir($location) as $node) {
        # Skip link to current and parent folder
        if ($node == '.')  continue;
        if ($node == '..') continue;

        # Check if it's a node or a folder
        if (is_dir($location . DIRECTORY_SEPARATOR . $node)) {
            # Recurse.
            $ret = array_merge($ret, addTemplate($location . DIRECTORY_SEPARATOR . $node, $toplocation));
        } else {
            $fn = $location . DIRECTORY_SEPARATOR . $node;
            $html = file_get_contents ($fn);

            # Get template name to use for id
            $tplname = str_replace(DIRECTORY_SEPARATOR, '_', substr($fn, strlen($toplocation) + 1));
            $tplname = str_replace('.html', '', $tplname);

            $tplcomm = INCLUDE_TEMPLATE_NAME ? "\n<!-- TPL $tplname -->\n" : "";

            $ret[] = '<script id="' . $tplname . "_template\" type=\"text/template\" >$tplcomm" . $html . '</script>';
        }
    }

    return($ret);
}

?>