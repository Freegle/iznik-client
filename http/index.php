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
require_once(IZNIK_BASE . '/include/session/Facebook.php');
require_once(IZNIK_BASE . '/include/session/Google.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . "/lib/JSMin.php");

if (!defined('SITE_NAME')) { error_log("Bad config " . $_SERVER['HTTP_HOST']); }

global $dbhr, $dbhm;

if (pres('REQUEST_URI', $_SERVER) == 'yahoologin') {
    # We have been redirected here from Yahoo.  Time to try to log in while we still have the
    # OAUTH data in our parameters (which won't be the case on subsequent API calls).
    #error_log("Redirect from Yahoo");
    $y = new Yahoo($dbhr, $dbhm);

    # No need to pay attention to the result - whether it worked or not will be determined by the
    # client later.
    $y->login(get_current_url());
} else if (pres('fblogin', $_REQUEST)) {
    # We are logging in using Facebook, but on the server because of a problem with Chrome on IOS - see
    # signinup.js
    $fbcode = presdef('code', $_REQUEST, NULL);
    $f = new Facebook($dbhr, $dbhm);
    $url = get_current_url();
    $url = substr($url, 0, strpos($url, '&code'));
    $f->login(NULL, $fbcode, $url);

    # Now redirect so that the code doesn't appear in the URL to the user, which looks messy.
    $url = substr($url, 0, strpos($url, '?'));
    header("Location: " . $url);
    exit(0);
} else if (pres('googlelogin', $_REQUEST)) {
    # We are logging in using Google.  We always do server logins for google due to issues with multiple accounts -
    # see google.js for more details.
    $code = presdef('code', $_REQUEST, NULL);
    $g = new Google($dbhr, $dbhm, FALSE);
    $url = get_current_url();
    $url = substr($url, 0, strpos($url, '&code'));
    $client = $g->getClient();
    $client->setRedirectUri($url);

    $g->login($code);

    # Now redirect so that the code doesn't appear in the URL to the user, which looks messy.
    $url = substr($url, 0, strpos($url, '?'));
    header("Location: " . $url);
    exit(0);
} else if (pres('fb_locale', $_REQUEST) && pres('signed_request', $_REQUEST)) {
    # Looks like a load of the Facebook app.
    $f = new Facebook($dbhr, $dbhm);
    $f->loadCanvas();
}

include_once(BASE_DIR . '/include/misc/pageheader.php');

# Depending on rewrites we might not have set up $_REQUEST.
if (strpos($_SERVER['REQUEST_URI'], '?') !== FALSE) {
    list($path, $qs) = explode("?", $_SERVER["REQUEST_URI"], 2);
    parse_str($qs, $qss);
    $_REQUEST = array_merge($_REQUEST, $qss);
}

if (!pres('id', $_SESSION)) {
    # Not logged in.  Check if we are fetching this url with a key which allows us to auto-login a user.
    $uid = presdef('u', $_REQUEST, NULL);
    $key = presdef('k', $_REQUEST, NULL);
    if ($uid && $key) {
        $u = User::get($dbhr, $dbhm, $uid);
        $u->linkLogin($key);
    }
}

if (pres('src', $_REQUEST)) {
    $dbhm->preExec("INSERT INTO logs_src (src, userid) VALUES (?, ?);", [
        $_REQUEST['src'],
        presdef('id', $_SESSION, NULL)
    ]);
}

$default = TRUE;

if (!pres('id', $_SESSION) && !pres('nocache', $_REQUEST)) {
    # We're not logged in.  Check if we can pre-render some HTML to make us appear fast.  The user can gawp at our
    # amazing speed, and while they do so, the JS on the client can catch up and do the actual render.
    $url = "https://" . $_SERVER['HTTP_HOST'] . presdef('REQUEST_URI', $_SERVER, '');
    $pages = $dbhr->preQuery("SELECT * FROM prerender WHERE url = ?;", [ $url ]);

    if (count($pages) > 0 && $pages[0]['html']) {
        $html = $pages[0]['html'];
        echo $html;
        $default = FALSE;
    }
}

if ($default) {
?>
        <body style="background-colour: #dff2d1;" id="thebody">
            <div itemscope itemtype="http://schema.org/Organization" style="display: none">
                <span itemprop="name"><?php echo SITE_NAME; ?></span>
                <img itemprop="logo" src="<?php echo USERLOGO; ?>" />
                <a itemprop="url" href="https://<?php echo USER_SITE; ?>"></a>
            </div>
            <noscript>
                <h1>Please enable Javascript</h1>

                <p>
                    We'd love to do a version which was accessible to people who don't use Javascript, but we do not have the resources to do that.
                    If you'd like to help with skills or funding, please <a href="mailto:geeks@ilovefreegle.org">mail us</a>.
                </p>
            </noscript>
            <div id="pageloader" style="position: relative; height: 100vh; width: 100%">
                <?php
                if (strpos($_SERVER['REQUEST_URI'], 'modtools') !== FALSE || strpos($_SERVER['HTTP_HOST'], 'modtools') !== FALSE) {
                    ?><img src="/images/modloader.gif" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; margin: auto;"/><?php
                } else {
                    ?><img src="/images/userloader.gif" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; margin: auto;"/><?php
                }
                ?>
            </div>
            <div id="fb-root"></div>
            <div id="bodyEnvelope">
                <div id="bodyContent" class="nopad">
                </div>
            </div>
            <div id="botleft" />

            <!-- Some nonsense to make browsers remember user/password. -->
            <div id="hiddenloginform" style="display: none">
                <form action="" method="post">
                    <input type="text" name="email" id="hiddenloginemail"/>
                    <input type="password" name="password" id="hiddenloginpassword"/>
                    <input type="submit" value="Login" id="hiddenloginsubmit"/>
                </form>
            </div>
        </body>
<?php
}
?>
    </html>