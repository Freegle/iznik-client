<?php
$scriptstart = microtime(false);
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('UTC');
session_start();
define( 'BASE_DIR', dirname(__FILE__) . '/..' );
require_once(BASE_DIR . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/session/Yahoo.php');
require_once(IZNIK_BASE . "/lib/JSMin.php");

global $dbhr, $dbhm;

if (pres('REQUEST_URI', $_SERVER) == 'yahoologin') {
    # We have been redirected here from Yahoo.  Time to try to log in while we still have the
    # OAUTH data in our parameters (which won't be the case on subsequent API calls).
    #error_log("Redirect from Yahoo");
    $y = new Yahoo($dbhr, $dbhm);

    # No need to pay attention to the result - whether it worked or not will be determined by the
    # client later.
    $y->login(get_current_url());
}
include_once(BASE_DIR . '/include/misc/pageheader.php');
?>
<body>
<noscript>
    <h1>Please enable Javascript</h1>

    <p>We'd love to do a version
        which was accessible to people who don't use Javascript, but we do not have the volunteer resources to do that.
        If you'd like to help with skills or funding, please <a href="mailto:edward@ehibbert.org.uk">mail us</a>.</p>
</noscript>
<div id="fb-root"></div>
<div id="bodyEnvelope">
    <div id="bodyContent" class="nopad">
    </div>
</div>
</body>
</html>
