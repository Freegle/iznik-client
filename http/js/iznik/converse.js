define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Views.Converse = Iznik.View.extend({
        tweakTitles: function (self) {
            // The converse chat titles have info in them that we don't want.
            $('#conversejs .chat-title, #conversejs .open-room').each(function () {
                var matches = /(.*) \(.*\)/.exec($(this).html());
                if (matches) {
                    console.log("Change chat title", $(this).html(), matches[1]);
                    $(this).html(matches[1]);
                }
            })

            // The title when it's minimised isn't great.
            $('#conversejs .toggle-controlbox .conn-feedback').html('Chats');
            $('#online-count').hide();

            _.delay(self.tweakTitles, 1000, self);
        },

        selectRooms: function (self) {
            // We want the Rooms tab to show and the Contacts tab to be hidden.
            var rooms = $('#conversejs a[href="#chatrooms"]');
            if (rooms.length == 0) {
                _.delay(self.selectRooms, 200, self);
            } else {
                rooms.html('Chats');
                $('#conversejs a[href="#users"]').closest('li').hide();

                // Now select chats tab
                rooms.trigger('click');
            }
        },

        render: function () {
            this.Iznik = Iznik;
            var self = this;

            require(['converse'], function (converse) {
                self.converse = converse;
                var loc = window.location.protocol + '//' + window.location.host;
                var me = Iznik.Session.get('me');

                // Ok, let's chat.  We have a converse plugin to change it to behave how we want.  This needs
                // to be registered before converse is initialised.
                var Strophe = converse.env.Strophe,
                    $iq = converse.env.$iq,
                    $msg = converse.env.$msg,
                    $pres = converse.env.$pres,
                    $build = converse.env.$build,
                    b64_sha1 = converse.env.b64_sha1;

                converse.plugins.add('Iznik', {
                    initialize: function () {
                        converse.listen.on('ready', function (event) {
                            console.log("Converse ready");
                        });
                    },

                    overrides: {
                        onConnected: function () {
                            var self = this;
                            var converse = this._super.converse;
                            console.log("Connected", this);

                            require(['iznik/base', 'converse'], function(Iznik, converse_api) {
                                self._super.onConnected();
                                console.log("API is ", converse_api);

                                // Now join all our chat rooms.
                                var groups = Iznik.Session.get('groups');
                                var myname = Iznik.Session.get('me').displayname;
                                groups.each(function (group) {
                                    if ((group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                                        var settings = group.get('mysettings');
                                        var chaton = !settings.hasOwnProperty('showmessages') || settings['showmessages'];
                                        if (chaton) {
                                            var jid = group.get('jid');
                                            console.log("Open group", jid);
                                            var args = {
                                                'id': jid,
                                                'jid': jid,
                                                'name': group.get('nameshort').toLowerCase() + '_mods',
                                                'nick': myname,
                                                'chatroom': true,
                                                'box_id' : b64_sha1(jid)
                                            };
                                            chatroom = converse.chatboxviews.showChat(args);
                                            chatroom.minimize();
                                        }
                                    }
                                });
                            });
                        }
                    }
                });

                // Now we have our plugin in place, initialise converse itself.
                converse.initialize({
                    bosh_service_url: loc + ':5280/http-bind',

                    // - Don't use prebind, as the auto-reconnect doesn't work.  We don't load our page that often so
                    //   it's ok to log in each time.
                    // prebind: true,
                    // prebind_url: loc + '/prebind',
                    jid: me.jid,
                    password: me.token,
                    auto_login: true,
                    keepalive: false,
                    ping_interval: 25,

                    // - We control the list of chat rooms visible
                    hide_muc_server: true,
                    auto_list_rooms: true,
                    auto_join_on_invite: true,

                    // We are always logged in.
                    allow_logout: false,
                    allow_registration: false,

                    // We control the list of users who can be see.
                    allow_contact_requests: false,
                    allow_contact_removal: false,
                    allow_otr: false,
                    roster_groups: true,

                    // Too big to open by default on mobile.
                    show_controlbox_by_default: isMobile() ? false : true,
                    debug: false
                });
            });


            this.tweakTitles(this);
            this.selectRooms(this);
        }
    });
});