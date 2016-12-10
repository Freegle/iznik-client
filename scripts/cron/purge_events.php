<?php
#
# Purge logs. We do this in a script rather than an event because we want to chunk it, otherwise we can hang the
# cluster with an op that's too big.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

$lockh = lockScript(basename(__FILE__));

if ($handle = opendir(IZNIK_BASE . "/events")) {
    while (false !== ($file = readdir($handle))) {
        $fn = IZNIK_BASE . "/events/$file";

        # Skip index files.
        if (is_file($fn) && strpos($file, '.') === FALSE) {
            $modified = filemtime($fn);

            if (time() - filemtime($fn) > 4 * 3600) {
                unlink($fn);
            }
        }
    }
    closedir($handle);
}

unlockScript($lockh);