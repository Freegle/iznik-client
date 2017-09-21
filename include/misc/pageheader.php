<?php
require_once(IZNIK_BASE . '/include/misc/scripts.php');
?><!DOCTYPE HTML>
<html>
<head>
    <?php
    if (!defined('BASE_DIR')) {
        define( 'BASE_DIR', dirname(__FILE__) . '/../..' );
    }

    # We serve up different og: tags to allow preview.
    if (preg_match('/\/explore\/(.*)/', $_SERVER["REQUEST_URI"], $matches)) {
        # Individual group - preview with name, tagline, image.
        require_once(BASE_DIR . '/include/config.php');
        require_once(IZNIK_BASE . '/include/db.php');
        require_once(IZNIK_BASE . '/include/group/Group.php');
        global $dbhr, $dbhm;
        $g = Group::get($dbhr, $dbhm);
        $gid = $g->findByShortName($matches[1]);
        if ($gid) {
            $g = Group::get($dbhr, $dbhm, $gid);
            $atts = $g->getPublic();
            $groupdescdef = "Give and Get Stuff for Free on {$atts['namedisplay']}";
            ?>
            <title><?php echo $atts['namedisplay']; ?></title>
            <meta itemprop="title" content="<?php echo $atts['namedisplay']; ?>"/>
            <meta itemprop="description" content="<?php echo presdef('tagline', $atts, $groupdescdef) ; ?>"/>
            <meta name="description" content="<?php echo presdef('tagline', $atts, $groupdescdef) ; ?>"/>
            <meta property="og:title" content="<?php echo $atts['namedisplay']; ?>"/>
            <meta property="og:description" content="<?php echo presdef('tagline', $atts, $groupdescdef) ; ?>"/>
            <meta property="og:image" content="<?php echo presdef('profile', $atts, USERLOGO); ?>"/>
            <?php
        }
    } else if (preg_match('/\/message\/(.*)/', $_SERVER["REQUEST_URI"], $matches)) {
        # Individual message - preview with subject and photo.
        require_once(BASE_DIR . '/include/config.php');
        require_once(IZNIK_BASE . '/include/db.php');
        require_once(IZNIK_BASE . '/include/message/Message.php');
        global $dbhr, $dbhm;
        $m = new Message($dbhr, $dbhm, intval($matches[1]));
        if ($m->getID()) {
            $atts = $m->getPublic();

            if ($m->canSee($atts)) {
                $icon = (count($atts['attachments']) > 0 && pres('path', $atts['attachments'][0])) ? $atts['attachments'][0]['path'] : USERLOGO;

                $rsptext = '';
                if ($m->getType() == Message::TYPE_OFFER) {
                    $rsptext = "Interested?  Click here to reply.  Everything on Freegle is free.  ";
                } else if ($m->getType() == Message::TYPE_WANTED) {
                    $rsptext = "Got one?  Click here to reply.  Everything on Freegle is free.  ";
                }

                ?>
                <title><?php echo $atts['subject']; ?></title>
                <meta itemprop="title" content="<?php echo $atts['subject']; ?>"/>
                <meta itemprop="description" content="<?php echo $rsptext; ?>"/>
                <meta name="description" content="<?php echo $rsptext; ?>"/>
                <meta property="og:title" content="<?php echo $atts['subject']; ?>"/>
                <meta property="og:description" content="<?php echo $rsptext; ?>"/>
                <meta property="og:image" content="<?php echo $icon; ?>"/>
                <?php
            }
        }
    } else if (preg_match('/\/communityevent\/(.*)/', $_SERVER["REQUEST_URI"], $matches)) {
        # Community event - preview with title and description
        require_once(BASE_DIR . '/include/config.php');
        require_once(IZNIK_BASE . '/include/db.php');
        require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');
        global $dbhr, $dbhm;
        $e = new CommunityEvent($dbhr, $dbhm, intval($matches[1]));

        if ($e->getID()) {
            $atts = $e->getPublic();
            $photo = presdef('photo', $atts, NULL);
            $icon = $photo ? $photo['path'] : USERLOGO;

            ?>
            <title><?php echo $atts['title']; ?></title>
            <meta itemprop="title" content="<?php echo $atts['title']; ?>"/>
            <meta name="description" content="<?php echo $atts['title']; ?>"/>
            <meta property="og:description" content="<?php echo $atts['title']; ?>"/>
            <meta property="og:title" content="<?php echo $atts['title']; ?>"/>
            <meta property="og:description" content=""/>
            <meta property="og:image" content="<?php echo $icon; ?>"/>
            <?php
        }
    } else if (preg_match('/\/story\/(.*)/', $_SERVER["REQUEST_URI"], $matches)) {
        # Story - preview with headline and description
        require_once(BASE_DIR . '/include/config.php');
        require_once(IZNIK_BASE . '/include/db.php');
        require_once(IZNIK_BASE . '/include/user/Story.php');
        global $dbhr, $dbhm;
        $s = new Story($dbhr, $dbhm, intval($matches[1]));

        if ($s->getID()) {
            $atts = $s->getPublic();
            $photo = presdef('photo', $atts, NULL);
            $icon = $photo ? $photo['path'] : USERLOGO;
            $headline = $atts['headline'];

            ?>
            <title><?php echo $headline; ?></title>
            <meta itemprop="title" content="<?php echo $headline; ?>"/>
            <meta name="description" content="<?php echo $headline; ?>"/>
            <meta property="og:description" content="<?php echo $headline; ?>"/>
            <meta property="og:title" content="<?php echo $headline; ?>"/>
            <meta property="og:description" content="Click to read more"/>
            <meta property="og:image" content="<?php echo $icon; ?>"/>
            <?php
        }
    } else if (preg_match('/\/chat\/(.*)\/external/', $_SERVER["REQUEST_URI"], $matches)) {
        # External link to a chat reply.
        require_once(BASE_DIR . '/include/config.php');
        require_once(IZNIK_BASE . '/include/db.php');
        require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');
        global $dbhr, $dbhm;
        $title = "Click to read your reply";
        $desc = "We passed on your message and got a reply - click here to read it."
        ?>
        <title><?php echo $title; ?></title>
        <meta itemprop="title" content="<?php echo $title; ?>"/>
        <meta name="description" content="<?php echo $desc; ?>"/>
        <meta property="og:description" content="<?php echo $desc; ?>"/>
        <meta property="og:title" content="<?php echo $title; ?>"/>
        <meta property="og:image" content="<?php echo $icon; ?>"/>
        <?php
    } else if (preg_match('/\/newsfeed\/(.*)/', $_SERVER["REQUEST_URI"], $matches)) {
        # External link to a newsfeed thread.
        require_once(BASE_DIR . '/include/config.php');
        require_once(IZNIK_BASE . '/include/db.php');
        require_once(IZNIK_BASE . '/include/newsfeed/Newsfeed.php');
        global $dbhr, $dbhm;
        $n = new Newsfeed($dbhr, $dbhm, $matches[1]);

        $title = 'A discussion on ' . SITE_NAME;
        $desc = '';
        $image = "https://" . USER_SITE . "/images/favicon/" . FAVICON_HOME . "/largetile.png?a=1";

        if ($n->getId()) {
            $atts = $n->getPublic();
            $desc = preg_replace('/\\\\\\\\u.*\\\\\\\\u/', '', $atts['message']);

            if ($atts['user']) {
                $title = $atts['user']['displayname'] . "'s discussion on " . SITE_NAME;
                $image = $atts['user']['profile']['url'];
            }
        }

        ?>
        <title><?php echo $title; ?></title>
        <meta itemprop="title" content="<?php echo $title; ?>"/>
        <meta name="description" content="<?php echo $desc; ?>"/>
        <meta property="og:description" content="<?php echo $desc; ?>"/>
        <meta property="og:title" content="<?php echo $title; ?>"/>
        <meta property="og:image" content="<?php echo $image; ?>"/>
        <?php
    } else if (preg_match('/\/streetwhack(\/.*)/', $_SERVER["REQUEST_URI"], $matches)) {
        $title = "Streetwhack!";
        $desc = "How popular is your streetname?  Is it a streetwhack - a one-off?  Or are there lots across the UK?  Find out now...";
        $count = presdef(1, $matches, NULL);
        $count = $count ? str_replace('/', '', $count) : NULL;

        if ($count) {
            $p = strpos($count, '?');
            $count = $p != -1 ? substr($count, 0, $p) : $count;
        }

        $countdesc = "";
        if ($count == 1) {
            $countdesc = "I'm a streetwhack!  Are you?\n\n";
        } else if ($count > 0) {
            $countdesc = "$count streets across the UK have the same name as mine.  How about you?\n\n";
        }

        $desc = "$countdesc $desc";
        ?>
        <title><?php echo $title; ?></title>
        <meta name="description" content="<?php echo $desc; ?>"/>
        <meta itemprop="description" content="<?php echo $desc; ?>"/>
        <meta itemprop="title" content="<?php echo $title; ?>"/>
        <meta property="og:title" content="<?php echo $title; ?>"/>
        <meta property="og:description" content="<?php echo $desc; ?>"/>
        <meta property="og:image" content="/images/streetwhack.png?a=2"/>
        <?php
        
    } else {
        $image = "https://" . USER_SITE . "/images/favicon/" . FAVICON_HOME . "/largetile.png?a=1";

        ?>
        <title><?php echo SITE_NAME; ?></title>
        <meta name="description" content="<?php echo SITE_DESC; ?>"/>
        <meta itemprop="description" content="<?php echo SITE_DESC; ?>"/>
        <meta itemprop="title" content="<?php echo SITE_NAME; ?>"/>
        <meta property="og:title" content="<?php echo SITE_NAME; ?>"/>
        <meta property="og:description" content="<?php echo SITE_DESC; ?>"/>
        <meta property="og:image" content="<?php echo $image; ?>"/>
        <?php
        echo "<!-- requested " . $_SERVER["REQUEST_URI"] . " -->\r\n";
    }

    if (!MODTOOLS && defined('GOOGLE_SITE_VERIFICATION')) {
        ?>
        <meta name="google-site-verification" content="<?php echo GOOGLE_SITE_VERIFICATION; ?>" />
        <?php
    }

    ?>
    <!-- Hi there.  We always need geek volunteers.  Why not mail geeks@ilovefreegle.org to get in touch, or -->
    <!-- help make the code better at https://github.com/Freegle/iznik ?  -->
    <meta name="msapplication-tap-highlight" content="no"/>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=yes, minimal-ui">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="<?php echo SITE_NAME; ?>">
    <meta name="format-detection" content="telephone=no">
    <link rel="manifest" href="/manifest.json">
    <meta property="og:url" content="<?php get_current_url(); ?>">

    <?php if (defined('IOS_APPID')) { ?>
        <meta name="apple-itunes-app" content="app-id=<?php echo IOS_APPID; ?>" />
    <?php } ?>
    <?php if (defined('ANDROID_APPID')) { ?>
    <meta name="google-play-app" content="app-id=<?php echo ANDROID_APPID; ?>">
    <?php } ?>

    <meta name="apple-mobile-web-app-capable" content="yes" />

    <?php
    # We use require on the client, and we want to avoid caching code after it has changed.  Find out when the
    # last change was.
    #
    $version = @file_get_contents('/tmp/iznik.version');
    $version = $version ? $version : 0;
    echo "<meta name=\"iznikcache\" content=\"$version\" >\n";
    ?>

    <link rel="stylesheet" href="/css/smart-app-banner.css?a=1" type="text/css" media="screen">
    <script src="/js/lib/smart-app-banner.js"></script>
    <script type="text/javascript">
        var userAgent = navigator.userAgent || navigator.vendor || window.opera;

        if (/android/i.test(userAgent)) {
            // SmartBanner to encourage people to install the apps.  IOS has it natively.
            <?php if (!MODTOOLS) { ?>
            new SmartBanner({
                daysHidden: 15,   // days to hide banner after close button is clicked (defaults to 15)
                daysReminder: 90, // days to hide banner after "VIEW" button is clicked (defaults to 90)
                appStoreLanguage: 'us', // language code for the App Store (defaults to user's browser language)
                title: 'Freegle',
                author: 'Freegle UK',
                button: 'VIEW',
                store: {
                    ios: 'On the App Store',
                    android: 'In Google Play'
                },
                price: {
                    ios: 'FREE',
                    android: 'FREE'
                },
                icon: '/images/user_logo.png'
            });
            <?php } else { ?>
            new SmartBanner({
                daysHidden: 15,   // days to hide banner after close button is clicked (defaults to 15)
                daysReminder: 90, // days to hide banner after "VIEW" button is clicked (defaults to 90)
                appStoreLanguage: 'us', // language code for the App Store (defaults to user's browser language)
                title: 'ModTools',
                author: 'Freegle UK',
                button: 'VIEW',
                store: {
                    ios: 'On the App Store',
                    android: 'In Google Play'
                },
                price: {
                    ios: 'FREE',
                    android: 'FREE'
                },
                icon: '/images/modtools_logo.png'
            });
            <?php } ?>
        }

        // Start a timer to reload if we fail to get the page rendered.  Do this now as any JS errors might prevent
        // us doing it later.  This is a last resort so the timer can be long.
        window.setTimeout(function() {
            var loader = document.getElementById('pageloader');
            if (loader) {
                // We've not managed to render and remove the page.  Probably a network issue.  Reload.
                console.log("Loader found - force reload", loader);
                window.location.reload();
            }
        }, 120000);

        if ('serviceWorker' in navigator) {
            // Before we do anything else, get our service worker up and running.  This will allow us to do better
            // caching where the browser supports them.
            //
            // We see problems with Service Workers in Firefox, so stick to Chrome for now.
            window.serviceWorker = null;
            var inChrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;

            if (inChrome) {
                // Use our version so that we will add a new service worker when the code changes.
                //
                // There may be a delay before that service worker becomes live, but that's ok.
                var version = <?php echo $version; ?>;
                console.log("Register service worker", version);
                navigator.serviceWorker.register('/sw.js?version=' + version).then(function (reg) {
                    console.log("Registered service worker");
                    window.serviceWorker = reg;
                }).catch(function (err) {
                    console.log("Can't register service worker", err);
                });

                try {
                    localStorage.setItem('version', version);
                } catch (e) {};
            } else {
                // Make sure no service workers running.
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    console.log("Got SW registrations", registrations);
                    for (var registration in registrations) {
                        console.log("Registration", registration);
                        if (registration && typeof registration.unregister === 'function') {
                            registration.unregister();
                        }
                    }
                });
            }
        }
    </script>    

    <link rel="chrome-webstore-item" href="https://chrome.google.com/webstore/detail/jekkhomlnoblcnangfcdohhaipmmaddc">
    <link rel="shortcut icon" href="/images/favicon/<?php echo FAVICON_HOME; ?>/favicon/<?php echo FAVICON_HOME; ?>.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" sizes="57x57" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" href="/images/favicon/<?php echo FAVICON_HOME; ?>/favicon/<?php echo FAVICON_HOME; ?>-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="/images/favicon/<?php echo FAVICON_HOME; ?>/favicon/<?php echo FAVICON_HOME; ?>-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/images/favicon/<?php echo FAVICON_HOME; ?>/favicon/<?php echo FAVICON_HOME; ?>-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="/images/favicon/<?php echo FAVICON_HOME; ?>/android-chrome-192x192.png" sizes="192x192">
    <meta name="msapplication-square70x70logo" content="images/favicon/<?php echo FAVICON_HOME; ?>/smalltile.png" />
    <meta name="msapplication-square150x150logo" content="images/favicon/<?php echo FAVICON_HOME; ?>/mediumtile.png" />
    <meta name="msapplication-wide310x150logo" content="images/favicon/<?php echo FAVICON_HOME; ?>/widetile.png" />
    <meta name="msapplication-square310x310logo" content="images/favicon/<?php echo FAVICON_HOME; ?>/largetile.png?a=1" />

    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/glyphicons.css">
    <link rel="stylesheet" href="/css/glyphicons-social.css">
    <link rel="stylesheet" href="/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="/css/bootstrap-switch.min.css">
    <link rel="stylesheet" href="/css/bootstrap-dropmenu.min.css">
    <link rel="stylesheet" href="/css/bootstrap-notifications.min.css">
    <link rel="stylesheet" href="/css/datepicker3.css">
    <link rel="stylesheet" href="/js/lib/bootstrap-datetimepicker/css/bootstrap-datetimepicker.css">
    <link rel="stylesheet" href="/css/dd.css">
    <link rel="stylesheet" href="/css/fileinput.css" />

    <link rel="stylesheet" type="text/css" href="/css/style.css?a=199">
    <!--[if lt IE 9]>
    <link rel="stylesheet" type="text/css" href="/css/ie-only.css">
    <![endif]-->

    <!-- Iznik info -->
    <meta name="iznikchat" content="<?php echo CHAT_HOST; ?>">
    <meta name="iznikevent" content="<?php echo EVENT_HOST; ?>">
    <?php if (defined('USER_GROUP_OVERRIDE')) { ?>
    <meta name="iznikusergroupoverride" content="<?php echo USER_GROUP_OVERRIDE; ?>">
    <?php } ?>
    <meta name="izniksitename" content="<?php echo SITE_NAME; ?>">
    <meta name="izniksitedesc" content="<?php echo SITE_DESC; ?>">
    <meta name="iznikusersite" content="<?php echo USER_SITE; ?>">
    <meta name="iznikmodsite" content="<?php echo MOD_SITE; ?>">
    <meta name="iznikmodtools" content="<?php echo MODTOOLS ? 1 : 0; ?>">

    <!-- And then some custom styles for our different apps -->
    <?php
    if (strpos($_SERVER['REQUEST_URI'], 'modtools') !== FALSE || strpos($_SERVER['HTTP_HOST'], 'modtools') !== FALSE) {
        ?><link rel="stylesheet" type="text/css" href="/css/modtools.css?a=24"><?php
    } else {
        ?><link rel="stylesheet" type="text/css" href="/css/user.css?a=154"><?php
    }
    ?>

    <?php
    # Pull in all our JS.
    $ret = scriptInclude(MINIFY ? (function($str) { return(JSMin::minify($str)); }) : FALSE);
    echo implode("\n", $ret[1]);
    ?>

    <!--[if lt IE 9]>
    <script src="/js/lib/html5shiv.js"></script>
    <script src="/js/lib/respond.min.js"></script>
    <![endif]-->

    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="HandheldFriendly" content="true">

    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <meta name="google-signin-client_id" content="<?php echo GOOGLE_CLIENT_ID; ?>">
    <meta name="facebook-app-id" content="<?php echo FBAPP_ID; ?>">
    <meta name="facebook-graffiti-app-id" content="<?php echo FBGRAFFITIAPP_ID; ?>">
    <script>
        (function() {
            var po = document.createElement('script');
            po.type = 'text/javascript'; po.async = true;
            po.src = 'https://plus.google.com/js/client:plusone.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(po, s);
        })();
    </script>
</head>
