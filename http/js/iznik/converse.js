define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Views.Converse = Iznik.View.extend({
        render: function() {
            // converse is hard to start, for reasons I don't understand and which may be my fault, or
            // may relate to its use of the Almond loader rather than the Require one.  After experimenting
            // with various options, the one that works is to use the full build, and this require to
            // trigger the load, but to be aware that the load callback may not be passed the module, and
            // therefore do a timer loop until the global has been defined.
            console.log("Start converse", require);
            var oldRequire = require;
            require(['converse'], function () {
                function reallyStart() {
                    console.log("Consider converse start", window.converse);
                    if (_.isUndefined(window.converse)) {
                        console.log("Not yet, retry");
                        setTimeout(reallyStart, 5000);
                    } else {
                        console.log("Gotcha", require);
                        var loc = window.location.protocol + '//' + window.location.host;
                        var me = Iznik.Session.get('me');
                        converse.initialize({
                            prebind: true,
                            prebind_url: loc + '/prebind',
                            bosh_service_url: loc + ':5280/http-bind',
                            jid: me.jid,
                            keepalive: true,
                            hide_muc_server: true,
                            allow_logout: false,
                            allow_registration: false,
                            allow_contact_requests: false,
                            allow_contact_removal: false,
                            auto_list_rooms: true,
                            show_controlbox_by_default: isMobile() ? false : true,
                            roster_groups: true,
                            ping_interval: 25,
                            debug: false
                        });
                        _.defer(function() {
                            console.log("Restore require");
                            require = oldRequire;
                        }, 60000);
                    }
                }

                reallyStart();
            });
            console.log("Started");
        }
    });
});