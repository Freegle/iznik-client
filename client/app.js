require('persist-js');
global.Storage = new Persist.Store("Iznik");

require("smart-app-banner.css?a=1");
require("bootstrap.min.css");
require("bootstrap-theme.min.css");
require("glyphicons.css");
require("glyphicons-social.css");
require("bootstrap-select.min.css");
require("bootstrap-switch.min.css");
require("bootstrap-dropmenu.min.css");
require("bootstrap-notifications.min.css");
require("datepicker3.css");
require("bootstrap-datepicker/dist/css/bootstrap-datepicker.css");
require("dd.css");
require("bootstrap-fileinput/css/fileinput.css");

require('style.css');
// TODO ModTools css when appropriate
require('user.css');
                                                
import 'iznik/main';

