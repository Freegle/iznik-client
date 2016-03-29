<?php
# This file should be suitably modified, then go into /etc/iznik.conf
define('SQLDB', 'iznik');
define('SQLUSER', 'zzzz');
define('SQLPASSWORD', 'zzzz');
define('PASSWORD_SALT', 'zzzz');
define('MODERATOR_EMAIL', 'zzzz');

# We can query Trash Nothing to get real email addresses for their users.
define('TNKEY', 'zzzzz');

# We can use push notifications
define('GOOGLE_PROJECT', 'zzz');
define('GOOGLE_PUSH_KEY', 'zzzz');
define('GOOGLE_VISION_KEY', 'zzz');

# We use beanstalk for backgrounding.
define('PHEANSTALK_SERVER', '127.0.0.1');

switch($_SERVER['HTTP_HOST']) {
    case 'iznik.modtools.org':
    case 'dev.modtools.org':
    case 'modtools.org':
        define('SITE_NAME', 'Iznik');
        define('SITE_DESC', 'Making moderating easier');
        define('MANIFEST', TRUE);
        break;
    case 'iznik.ilovefreegle.org':
        define('SITE_NAME', 'Freegle');
        define('SITE_DESC', 'Online dating for stuff');
        define('MANIFEST', FALSE);
        break;
}

# Image host domain
define('IMAGE_DOMAIN', 'zzzz');

# Domain for email addresses for our users
define('USER_DOMAIN', 'zzzz');

# Contact emails
define('SUPPORT_ADDR', 'support@zzz');
define('INFO_ADDR', 'info@zzz');
define('NOREPLY_ADDR', 'noreply@zzz');

# This speeds up load time
define('MINIFY', TRUE);
