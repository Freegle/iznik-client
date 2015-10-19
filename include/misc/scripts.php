<?php

# We take the minify function as a parameter to ease UT.
function scriptInclude($minify)
{
    $jsfiles = array(
        "js/lib/jquery-1.11.3.js",
        "js/lib/jquery-migrate-1.2.1.js",
        "js/lib/jquery-ui.js",
        "js/lib/underscore.js",
        "js/lib/moment.js",
        "js/lib/combodate.js",
        "js/lib/jquery-dateFormat.min.js",
        "js/lib/exif.js",
        "js/lib/jquery.ui.touch-punch.js",
        "js/lib/backbone-1.1.2.js",
        "js/lib/backbone.collectionView.js",
        "js/lib/jquery.dotdotdot.min.js",
        "js/lib/json2.js",
        "js/lib/flowtype.js",
        "js/lib/pushstream.js",
        "js/lib/FormRepo.js",
        "js/lib/canvasResize.js",
        "js/lib/notify.js",
        "js/lib/timeago.js",
        "js/lib/jquery.validate.min.js",
        "js/lib/jquery.validate.additional-methods.js",
        "js/lib/jquery.geocomplete.min.js",
        "js/lib/jquery.dd.min.js",
        "js/lib/richMarker.js",
        "js/lib/markerclusterer.min.js",
        "js/lib/placeholders.min.js",
        "js/lib/bootstrap-select.min.js",
        "js/lib/bootstrap-switch.min.js",
        "js/lib/bootstrap-datepicker.js",
        "js/lib/sly.min.js",
        "js/lib/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js",
        "js/lib/bootstrap-datepicker.en-GB.js",
        "js/lib/autosize.js",
        "js/lib/jquery.waypoints.js",
        "js/iznik/dateshim.js",
        "js/iznik/zombies.js",
        "js/iznik/underscore.js",
        "js/iznik/main.js",
        "js/iznik/utility.js",
        "js/iznik/facebook.js",
        "js/iznik/google.js",
        "js/iznik/accordionpersist.js",
        "js/iznik/selectpersist.js",
        "js/iznik/majax.js",
        "js/models/session.js",
        "js/models/message.js",
        "js/models/user/user.js",
        "js/models/yahoo/user.js",
        "js/views/plugin.js",
        "js/views/modal.js",
        "js/views/signinup.js",
        "js/views/pages/pages.js",
        "js/views/utils.js",
        "js/views/user/user.js",
        "js/views/yahoo/user.js",
        "js/views/pages/landing.js",
        "js/views/pages/modtools/landing.js",
        "js/views/pages/modtools/messages.js",
        "js/views/pages/modtools/spam.js",
        "js/views/pages/modtools/pending.js",
        "js/views/pages/modtools/approved.js",
        "js/views/group/select.js",
        "js/iznik/router.js",
    );

    $ret = [];
    # This one split out because of weird document.write stuff
    $ret[] = '<script src="/js/lib/binaryajax.js"></script>' . "\n";
    $cachefile = NULL;

    if (!$minify) {
        # Just output them as script tags.  Add a timestamp so that if we change the file, the client will reload.
        foreach ($jsfiles as $jsfile) {
            $thisone = file_get_contents(IZNIK_BASE . "/http/$jsfile");
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