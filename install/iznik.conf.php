<?php
# This file should be suitably modified, then go into /etc/iznik.conf
define('SQLDB', 'iznik');
define('SQLUSER', 'zzzz');
define('SQLPASSWORD', 'zzzz');
define('PASSWORD_SALT', 'zzzz');
define('MODERATOR_EMAIL', 'zzzz');

# Logos
define('USERLOGO', 'https://iznik.modtools.org/images/user_logo.png');
define('MODLOGO', 'https://iznik.modtools.org/images/modlogo-large.jpg');

# We can query Trash Nothing to get real email addresses for their users.
define('TNKEY', 'zzzzz');

# We can use push notifications
define('GOOGLE_PROJECT', 'zzz');
define('GOOGLE_PUSH_KEY', 'zzzz');

# Other Google keys
define('GOOGLE_VISION_KEY', 'zzz');
define('GOOGLE_CLIENT_ID', 'zzz');
define('GOOGLE_CLIENT_SECRET', 'zzz');
define('GOOGLE_APP_NAME', 'zzz');

# We support Facebook login, but you have to create your own app
define('FBAPP_ID', 'zzz');
define('FBAPP_SECRET', 'zzz');

# We use beanstalk for backgrounding.
define('PHEANSTALK_SERVER', '127.0.0.1');

# You can force all user activity onto a test group
define('USER_GROUP_OVERRIDE', 'FreeglePlayground');

$host = $_SERVER && array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : 'iznik.modtools.org';
define('SITE_HOST', $host);

switch($host) {
    case 'iznik.modtools.org':
        define('SITE_NAME', 'Iznik');
        define('SITE_DESC', 'Making moderating easier');
        define('FAVICON_HOME', 'modtools');
        define('CHAT_HOST', 'iznik.modtools.org');
        define('MANIFEST_STARTURL', 'modtools');
        break;
    case 'dev.modtools.org':
    case 'modtools.org':
        define('SITE_NAME', 'Iznik');
        define('SITE_DESC', 'Making moderating easier');
        define('FAVICON_HOME', 'modtools');
        define('CHAT_HOST', 'modtools.org');
        define('MANIFEST_STARTURL', 'modtools');
        break;
    case 'iznik.ilovefreegle.org':
        define('SITE_NAME', 'Freegle');
        define('SITE_DESC', 'Online dating for stuff');
        define('FAVICON_HOME', 'user');
        define('CHAT_HOST', 'chat.ilovefreegle.org');
        define('MANIFEST_STARTURL', '');
        break;
}

# Image host domain - both for active, and archived.
define('IMAGE_DOMAIN', 'zzzz');
define('IMAGE_ARCHIVED_DOMAIN', 'zzz');

# Domain for email addresses for our users
define('USER_DOMAIN', 'zzzz');

# Contact emails
define('SUPPORT_ADDR', 'support@zzz');
define('INFO_ADDR', 'info@zzz');
define('NOREPLY_ADDR', 'noreply@zzz');

# This speeds up load time
define('MINIFY', TRUE);

# For test scripts
define('USER_TEST_SITE', 'https://iznik.ilovefreegle.org');
define('MOD_TEST_SITE', 'https://iznik.modtools.org');
