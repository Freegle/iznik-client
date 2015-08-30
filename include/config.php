<?php

define('IZNIK_BASE', dirname(__FILE__) . '/..');
require_once(IZNIK_BASE . '/composer/vendor/autoload.php');

# Per-machine config or overrides
require_once('/etc/iznik.conf');

define('INCLUDE_TEMPLATE_NAME', true);
define('MINIFY', false);
