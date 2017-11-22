// Various things that - rightly or wrongly, probably wrongly - we expect to be globals.
require("expose-loader?jQuery!jquery");
require("expose-loader?$!jquery");
require("expose-loader?_!underscore");
require("expose-loader?Backbone!backbone");
require("expose-loader?Iznik!iznik/base");
require("expose-loader?Storage!persist-js");

global.Storage = new Persist.Store("Iznik");

// Global constants.
global.API = '/api';
global.YAHOOAPI = 'https://groups.yahoo.com/api/v1/';
global.YAHOOAPIv2 = 'https://groups.yahoo.com/api/v2/';

// import 'iznik/models/chat/chat'
import 'iznik/main';

