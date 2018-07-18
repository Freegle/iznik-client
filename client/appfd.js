var Raven = require('raven-js');
Raven.config(RAVEN_ID, {
    release: BUILD_TIME + '.' + GIT_COMMITHASH
}).install({
    extra: {
        BASE_URL: BASE_URL
    }
});

import 'persist-js';

require('viewport-units-buggyfill').init();

try {
    global.Storage = new Persist.Store("Iznik");
} catch (e) {
    // We will display something sensible to the user later in router.
    console.log("Storage exception", e);
}

import "smart-app-banner.css?a=1";
import "bootstrap.min.css";
import "bootstrap-theme.min.css";
import "glyphicons.css";
import "glyphicons-social.css";
import "bootstrap-select.min.css";
import "bootstrap-switch.min.css";
import "bootstrap-dropmenu.min.css";
import "bootstrap-notifications.min.css";
import "eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css";
import "dd.css";
import "bootstrap-fileinput/css/fileinput.css";

import 'style.less';
import 'user.css';

import 'iznik/main';

