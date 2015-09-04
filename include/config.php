<?php

define('IZNIK_BASE', dirname(__FILE__) . '/..');
require_once(IZNIK_BASE . '/composer/vendor/autoload.php');

define('DUPLICATE_POST_PROTECTION', 10); # Set to 0 to disable
define('API_RETRIES', 5);
define('BROWSERTRACKING', TRUE);
define('INCLUDE_TEMPLATE_NAME', true);
define('MINIFY', false);

define('COOKIE_NAME', 'session');

# Per-machine config or overrides
require_once('/etc/iznik.conf');

