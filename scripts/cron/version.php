<?php
# The way we pick up changes to the code and force the clients to reload is via a version number in /etc/iznik.version.
#
# This background file updates that.
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/scripts.php');

$lockh = lockScript(basename(__FILE__));

try {
 
    while (true) {
        $version = getversion();
        file_put_contents("/tmp/iznik.version", $version);
        sleep(1);
    }
} catch (Exception $e) {
    error_log("Top-level exception " . $e->getMessage() . "\n");
}

unlockScript($lockh);