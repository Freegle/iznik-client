define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Views.Converse = Iznik.View.extend({
        tweakTitles: function(self) {
            // The converse chat titles have info in them that we don't want.
            $('#conversejs .chat-title, #conversejs .open-room').each(function() {
                var matches = /(.*) \(.*\)/.exec($(this).html());
                if (matches) {
                    console.log("Change chat title", $(this).html(), matches[1]);
                    $(this).html(matches[1]);
                }
            })

            _.delay(self.tweakTitles, 1000, self);
        },

        selectRooms: function(self) {
            // We want the Rooms tab to show and the Contacts tab to be hidden.
            var rooms = $('#conversejs a[href="#chatrooms"]');
            if (rooms.length == 0) {
                _.delay(self.selectRooms, 200, self);
            } else {
                rooms.html('Chats');
                $('#conversejs a[href="#users"]').closest('li').hide();
                rooms.trigger('click');
            }
        },

        render: function() {
            require(['converse'], function (converse) {
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
                    allow_otr: false,
                    auto_list_rooms: true,
                    show_controlbox_by_default: isMobile() ? false : true,
                    roster_groups: true,
                    ping_interval: 25,
                    debug: false
                });
            });

            this.tweakTitles(this);
            this.selectRooms(this);
        }
    });
});