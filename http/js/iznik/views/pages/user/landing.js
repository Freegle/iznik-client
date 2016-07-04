define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/chat/chat',
    'iznik/views/pages/pages',
    'iznik/views/group/select',
    'jquery.dd'
], function($, _, Backbone, Iznik, ChatHolder) {
    Iznik.Views.User.Pages.Landing = Iznik.Views.Page.extend({
        template: "user_landing_main",
        footer: true
    });

    Iznik.Views.User.Pages.Landing.About = Iznik.Views.Page.extend({
        template: "user_landing_about",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Terms = Iznik.Views.Page.extend({
        template: "user_landing_terms",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Privacy = Iznik.Views.Page.extend({
        template: "user_landing_privacy",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Disclaimer = Iznik.Views.Page.extend({
        template: "user_landing_disclaimer",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Donate = Iznik.Views.Page.extend({
        template: "user_landing_donate",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Contact = Iznik.Views.Page.extend({
        template: "user_landing_contact",
        noback: true,

        events: {
            'click .js-chat': 'chatMods'
        },

        chatMods: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var groupid = this.groupSelect.get();
            ChatHolder().openChatToMods(groupid);
        },

        render: function() {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function (self) {
                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    var groups = Iznik.Session.get('groups');

                    if (groups.length >= 0) {
                        self.groupSelect = new Iznik.Views.Group.Select({
                            systemWide: false,
                            all: false,
                            mod: false,
                            choose: true,
                            id: 'contactGroupSelect'
                        });

                        self.groupSelect.render().then(function() {
                            self.$('.js-groupselect').html(self.groupSelect.el);
                        });

                        self.$('.js-contactmods').show();
                    }
                });

                Iznik.Session.testLoggedIn();
            });

            return (p);
        }
    });
});