<?php
define('IZNIK_BASE', dirname(__FILE__) . '/..');
require_once('/etc/iznik.conf');
require_once(IZNIK_BASE . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

echo "User-agent: *\n";

# Exclude some folders.  Some crawlers may ignore this.
foreach ([
    'api',
    'css',
    'facebook',
    'fonts',
    'js',
    'jscache',
    'mobile',
    'plugin',
    'swagger-ui',
    'template',
    'tools',
    'twitter'
         ] as $excl) {
    echo "Disallow: /$excl\n";
}

echo "\nSITEMAP: http://" . USER_SITE . '/sitemap.xml';
