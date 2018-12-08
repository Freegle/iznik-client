if (window.location.hostname == 'iznik.ilovefreegle.org') {
    __webpack_public_path__ = 'https://freeglecdndbg.azureedge.net/';
} else if (window.location.hostname == 'dev.ilovefreegle.org') {
    __webpack_public_path__ = 'https://freeglecdndev.azureedge.net/';
} else if (window.location.hostname == 'www.ilovefreegle.org') {
    __webpack_public_path__ = 'https://freeglecdn.azureedge.net/';
}

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

require("smart-app-banner.css?a=1");
require("bootstrap.min.css");
require("bootstrap-theme.min.css");
require("glyphicons.css");
require("glyphicons-social.css");
require("bootstrap-select.min.css");
require("bootstrap-switch.min.css");
require("bootstrap-dropmenu.min.css");
require("bootstrap-notifications.min.css");
require("eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css");
require("dd.css");
require("bootstrap-fileinput/css/fileinput.css");

require("style.less");
require("user.css");

require("iznik/main");

