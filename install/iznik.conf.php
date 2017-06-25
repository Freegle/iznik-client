<?php
# This file should be suitably modified, then go into /etc/iznik.conf
define('SQLHOST', '127.0.0.1');
define('SQLPORT', 3306);
define('SQLDB', 'iznik');
define('SQLUSER', 'zzzz');
define('SQLPASSWORD', 'zzzz');
define('PASSWORD_SALT', 'zzzz');
define('MODERATOR_EMAIL', 'zzzz');

# Logos
define('USERLOGO', 'https://www.ilovefreegle.org/images/user_logo.png');
define('MODLOGO', 'https://modtools.org/images/modlogo-large.jpg');

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
define('GOOGLE_SITE_VERIFICATION', 'zzz');

# We support Facebook login, but you have to create your own app
define('FBAPP_ID', 'zzz');
define('FBAPP_SECRET', 'zzz');

# We have a separate app for posting to group pages, in case this one gets blocked
define('FBGRAFFITIAPP_ID', 'zzz');
define('FBGRAFFITIAPP_SECRET', 'zzz');

# We post to Twitter
define('TWITTER_CONSUMER_KEY', 'zzzz');
define('TWITTER_CONSUMER_SECRET', 'zzzz');
define('TWITTER_ACCOUNT_TOKEN', 'zzz');
define('TWITTER_ACCOUNT_SECRET', 'zzz');

# We access PayPal to retrieve info on donations
define('PAYPAL_USERNAME', 'zzzz');
define('PAYPAL_PASSWORD', 'zzzz');
define('PAYPAL_SIGNATURE', 'zzzz');
define('PAYPAL_THANKS_FROM', 'zzzz');

define('SERVER_LIST', '');

# We use beanstalk for backgrounding.
define('PHEANSTALK_SERVER', '127.0.0.1');

# Host to monitor
define('MONIT_HOST', 'zzz');

# You can force all user activity onto a test group
define('USER_GROUP_OVERRIDE', 'FreeglePlayground');

# The domain for users to access.
define('USER_SITE', 'iznik.ilovefreegle.org');

# The domain for mods to access.
define('MOD_SITE', 'modtools.org');

$host = $_SERVER && array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : 'iznik.modtools.org';
define('SITE_HOST', $host);

# Where we send event tracking.
define('EVENT_HOST', 'dev.ilovefreegle.org');

switch($host) {
    case 'iznik.modtools.org':
        define('SITE_NAME', 'Iznik');
        define('SITE_DESC', 'Making moderating easier');
        define('FAVICON_HOME', 'modtools');
        define('CHAT_HOST', 'iznik.modtools.org');
        define('MANIFEST_STARTURL', 'modtools');
        define('COOKIE_DOMAIN', 'modtools.org');
        define('MODTOOLS', TRUE);
        break;
    case 'dev.modtools.org':
    case 'modtools.org':
        define('SITE_NAME', 'Iznik');
        define('SITE_DESC', 'Making moderating easier');
        define('FAVICON_HOME', 'modtools');
        define('CHAT_HOST', 'modtools.org');
        define('MANIFEST_STARTURL', 'modtools');
        define('COOKIE_DOMAIN', 'modtools.org');
        define('MODTOOLS', TRUE);
        break;
    case 'iznik.ilovefreegle.org':
        define('SITE_NAME', 'Freegle');
        define('SITE_DESC', 'Online dating for stuff');
        define('FAVICON_HOME', 'user');
        define('CHAT_HOST', 'chat.ilovefreegle.org');
        define('MANIFEST_STARTURL', '');
        define('COOKIE_DOMAIN', 'ilovefreegle.org');
        define('MODTOOLS', FALSE);
        break;
}

# Image host domain - both for active, and archived.
#
# We archive to Azure CDN.
define('AZURE_CONNECTION_STRING', 'zzzz');
define('IMAGE_DOMAIN', 'zzzz');
define('IMAGE_ARCHIVED_DOMAIN', 'zzz');

# Domain for email addresses for our users
define('USER_DOMAIN', 'zzzz');

# Email submissions
define('GROUP_DOMAIN', 'zzzz');

# Contact emails
define('SUPPORT_ADDR', 'support@zzz');
define('INFO_ADDR', 'info@zzz');
define('GEEKS_ADDR', 'geeks@zzz');
define('BOARD_ADDR', 'board@zzz');
define('MENTORS_ADDR', 'mentors@zzz');
define('NEWGROUPS_ADDR', 'newgroups@zzz');
define('VOLUNTEERS_ADDR', 'volunteers@zzz');

define('NOREPLY_ADDR', 'noreply@zzz');

# Central mods mailing list, where we send periodic mails
define('CENTRAL_MAIL_TO', 'FreegleUK-Central@yahoogroups.com');
define('CENTRAL_MAIL_FROM', 'geeks@ilovefreegle.org');

# This speeds up load time
define('MINIFY', TRUE);

define('DONATION_TARGET', 1250);

# For test scripts
define('USER_TEST_SITE', 'https://iznik.ilovefreegle.org');
define('MOD_TEST_SITE', 'https://iznik.modtools.org');

