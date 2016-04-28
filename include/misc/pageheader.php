<?php
require_once(IZNIK_BASE . '/include/misc/scripts.php');
require_once(IZNIK_BASE . '/include/misc/template.php');
?><!DOCTYPE HTML>
<html>
<head>
    <!-- Hi there.  We always need geek volunteers.  Why not mail geeks@ilovefreegle.org to get in touch, or
    help make the code better at https://github.com/Freegle/iznik ?  -->
    <meta name="msapplication-tap-highlight" content="no"/>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=yes, minimal-ui">
    <meta name="robots" content="nofollow, noindex, noarchive, nocache">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="<?php echo SITE_NAME; ?>">
    <meta name="format-detection" content="telephone=no">
    <link rel="manifest" href="/manifest.json">

    <?php
    # TODO _escaped_fragment_ previews for Facebook etc.
    ?>

    <!-- Facebook meta info -->
    <title><?php echo SITE_NAME; ?></title>
    <meta property="og:title" content="<?php echo SITE_NAME; ?>"/>
    <meta property="og:url" content="<?php get_current_url(); ?>">
    <meta property="og:description" content="<?php echo SITE_DESC; ?>"/>
    <meta property="og:image" content="/images/favicon/<?php echo FAVICON_HOME; ?>largetile.png"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta property="description" content="<?php echo SITE_DESC; ?>"/>

    <!-- Google signin -->
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <meta name="google-signin-client_id" content="<?php echo GOOGLE_CLIENT_ID; ?>">

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
    <meta name="msapplication-square310x310logo" content="images/favicon/<?php echo FAVICON_HOME; ?>/largetile.png" />

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/glyphicons.css">
    <link rel="stylesheet" href="/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="/css/bootstrap-switch.min.css">
    <link rel="stylesheet" href="/css/datepicker3.css">
    <link rel="stylesheet" href="/css/bootstrap-tagsinput.css">
    <link rel="stylesheet" href="/js/lib/bootstrap-datetimepicker/css/bootstrap-datetimepicker.css">
    <link rel="stylesheet" href="/css/dd.css">

    <link rel="stylesheet" type="text/css" href="/css/style.css">
    <![endif]-->
    <!--[if gte IE 9]>
    <link rel="stylesheet" type="text/css" href="/css/style.css"><![endif]-->
    <!--[if lt IE 9]>
    <link rel="stylesheet" type="text/css" href="/css/ie-only.css">
    <![endif]-->

    <!-- Iznik info -->
    <meta name="iznikchat" content="<?php echo CHAT_HOST; ?>">
    <meta name="iznikusergroupoverride" content="<?php echo USER_GROUP_OVERRIDE; ?>">
    <?php

    # We use require on the client, and we want to avoid caching code after it has changed.  Find out when the
    # last change was.
    #
    # TODO We could speed page load by changing this.  It takes about 0.1s, which is significant.
    $buststart = microtime(true);
    $directory =new RecursiveDirectoryIterator(IZNIK_BASE);
    $flattened = new RecursiveIteratorIterator($directory);
    $files = new RegexIterator($flattened, '/.*\.((php)|(html)|(js)|(css))/i');

    $max = 0;

    foreach ($files as $filename=>$cur) {
        $time = $cur->getMTime();
        $max = max($max, $time);
    }

    echo "<meta name=\"iznikcache\" content=\"$max\" >\n";
    echo '<meta name="iznikcachecalc" content="' . (microtime(true) - $buststart) . '" >';
    ?>

    <!-- And then some custom styles for our different apps -->
    <?php
    if (strpos($_SERVER['REQUEST_URI'], 'modtools') !== FALSE || strpos($_SERVER['HTTP_HOST'], 'modtools') !== FALSE) {
        ?><link rel="stylesheet" type="text/css" href="/css/modtools.css"><?php
    } else {
        ?><link rel="stylesheet" type="text/css" href="/css/user.css"><?php
    }
    ?>

    <?php
    # Pull in all our JS.
    $ret = scriptInclude(MINIFY ? (function($str) { return(JSMin::minify($str)); }) : FALSE);
    echo implode("\n", $ret[1]);

    # Pull in all the templates as script tags for later expansion.
    # TODO Could cache these rather than return inline?
    $tpls = addTemplate(IZNIK_BASE . '/http/template/', IZNIK_BASE . '/http/template/');
    echo implode("\n", $tpls);
    ?>
    <script src="https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyCdTSJKGWJUOx2pq1Y0f5in5g4kKAO5dgg&libraries=geometry,places,drawing,visualization"></script>
    <script type="text/javascript" src="https://google-maps-utility-library-v3.googlecode.com/svn/trunk/maplabel/src/maplabel-compiled.js"></script>
    <script type="text/javascript" src="https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1','packages':['corechart', 'annotationchart']}]}"></script>
    <script type="text/javascript" src="//cdn.tinymce.com/4/tinymce.min.js"></script>

    <!--[if lt IE 9]>
    <script src="/js/lib/html5shiv.js"></script>
    <script src="/js/lib/respond.min.js"></script>
    <![endif]-->

    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="HandheldFriendly" content="true">

    <script src="https://apis.google.com/js/client:platform.js" async defer></script>
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
