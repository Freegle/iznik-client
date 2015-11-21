<?php
require_once(IZNIK_BASE . '/include/misc/scripts.php');
require_once(IZNIK_BASE . '/include/misc/template.php');
?><!DOCTYPE HTML>
<html>
<head>
    <meta name="msapplication-tap-highlight" content="no"/>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=yes, minimal-ui">
    <meta name="robots" content="nofollow, noindex, noarchive, nocache">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="<?php echo SITE_NAME; ?>">
    <meta name="format-detection" content="telephone=no">

    <?php
    # TODO _escaped_fragment_ previews for Facebook etc.
    ?>

    <!-- Facebook meta info -->
    <title><?php echo SITE_NAME; ?></title>
    <meta property="og:title" content="<?php echo SITE_NAME; ?>"/>
    <meta property="og:url" content="<?php get_current_url(); ?>">
    <meta property="og:description" content="<?php echo SITE_DESC; ?>"/>
    <meta property="og:image" content="/images/logo.png"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta property="description" content="<?php echo SITE_DESC; ?>"/>

    <link rel="chrome-webstore-item" href="https://chrome.google.com/webstore/detail/jekkhomlnoblcnangfcdohhaipmmaddc">
    <link rel="shortcut icon" href="/images/favicon/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" sizes="57x57" href="/images/favicon/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/images/favicon/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/images/favicon/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/images/favicon/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/favicon/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/favicon/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/favicon/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/favicon/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" href="/images/favicon/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="/images/favicon/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/images/favicon/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="/images/favicon/android-chrome-192x192.png" sizes="192x192">
    <meta name="msapplication-square70x70logo" content="images/favicon/smalltile.png" />
    <meta name="msapplication-square150x150logo" content="images/favicon/mediumtile.png" />
    <meta name="msapplication-wide310x150logo" content="images/favicon/widetile.png" />
    <meta name="msapplication-square310x310logo" content="images/favicon/largetile.png" />

    <?php
    # Pull in all our JS.
    $ret = scriptInclude(MINIFY ? (function($str) { return(JSMin::minify($str)); }) : FALSE);
    echo implode("\n", $ret[1]);

    # Pull in all the templates as script tags for later expansion.
    # TODO Could cache these rather than return inline?
    $tpls = addTemplate(IZNIK_BASE . '/http/template/', IZNIK_BASE . '/http/template/');
    echo implode("\n", $tpls);
    ?>
    <script src="https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyCpB54NtsIUX6L9ChabAmvr6k48DZhRJs0&libraries=geometry,places,drawing,visualization"></script>
    <script type="text/javascript" src="https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1','packages':['corechart', 'annotationchart']}]}"></script>

    <!-- We use bootstrap as a base UI -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

    <!--[if lt IE 9]>
    <script src="/js/lib/html5shiv.js"></script>
    <script src="/js/lib/respond.min.js"></script>
    <![endif]-->

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

    <!-- Plugins -->
    <link rel="stylesheet" href="/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="/css/bootstrap-switch.min.css">
    <link rel="stylesheet" href="/css/datepicker3.css">
    <link rel="stylesheet" href="/js/lib/bootstrap-datetimepicker/css/bootstrap-datetimepicker.css">
    <link rel="stylesheet" href="/css/dd.css">

    <!-- And then we do custom styling on top. -->
    <!--[if !IE]><!-->
    <link rel="stylesheet" type="text/css" href="/css/style.css?t=<?php echo date("YmdHis", filemtime(IZNIK_BASE . "/http/css/style.css")); ?>">
    <![endif]-->
    <!--[if gte IE 9]>
    <link rel="stylesheet" type="text/css" href="/css/style.css?t=<?php echo date("YmdHis", filemtime(IZNIK_BASE . "/http/css/style.css")); ?>"><![endif]-->
    <!--[if lt IE 9]>
    <link rel="stylesheet" type="text/css" href="/css/ie-only.css?t=<?php echo date("YmdHis", filemtime(IZNIK_BASE . "/http/css/ie-only.css")); ?>">
    <![endif]-->

    <!-- And then some custom styles for our different apps -->
    <?php
    if (strpos($_SERVER['REQUEST_URI'], 'modtools') !== FALSE) {
        ?><link rel="stylesheet" type="text/css" href="/css/modtools.css?t=<?php echo date("YmdHis", filemtime(IZNIK_BASE . "/http/css/modtools.css")); ?>"><?php
    } else {
        ?><link rel="stylesheet" type="text/css" href="/css/user.css?t=<?php echo date("YmdHis", filemtime(IZNIK_BASE . "/http/css/user.css")); ?>"><?php
    }
    ?>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="HandheldFriendly" content="true">

    <script>
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-10627716-9']);
        _gaq.push(['_trackPageview']);
        (function () {
            var ga = document.createElement('script');
            ga.type = 'text/javascript';
            ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(ga, s);
        })();
    </script>

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
