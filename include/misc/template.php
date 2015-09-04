<?php

require_once(IZNIK_BASE . '/include/utils.php');

function addTemplate($collection, $widget_location){
    $ret = [];
    foreach($collection as $key => $node){
        if(is_array($node)){
            foreach($node as $template){
                $html = file_get_contents ( $widget_location.$key.'/'.$template);
                // Get the filename to use.
                $template = pathinfo ( $template );
                $template = $template ['filename'];
                $tplname = INCLUDE_TEMPLATE_NAME ? "\n<!-- TPL $widget_location$key/$template -->\n" : "";
                $ret[] = '<script id="' .$key.'_'.$template . "_template\" type=\"text/template\" >$tplname" . $html . '</script>';
            }
        }else{
            $template = $node;
            $html = file_get_contents ($widget_location.$template);
            // Get the filename to use.
            $template = pathinfo ( $template );
            $template = $template ['filename'];
            $tplname = INCLUDE_TEMPLATE_NAME ? "\n<!-- TPL $widget_location$key/$template -->\n" : "";
            $ret[] = '<script id="' . $template . "_template\" type=\"text/template\" >$tplname" . $html . '</script>';
        }
    }

    return($ret);
}

?>