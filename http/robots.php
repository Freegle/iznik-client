<?php
define('IZNIK_BASE', dirname(__FILE__) . '/..');
require_once('/etc/iznik.conf');
require_once(IZNIK_BASE . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

echo "User-agent: *\n";

echo "\nSITEMAP: http://" . USER_SITE . '/sitemap.xml';
