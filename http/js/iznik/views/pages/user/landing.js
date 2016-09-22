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

    Iznik.Views.User.Pages.Landing.Mobile = Iznik.Views.Page.extend({
        template: "user_landing_mobile",
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
            var self = this;
            console.log("Render contact");

            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function () {
                console.log("Test logged");
                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    console.log("Logged in", loggedIn);
                    if (loggedIn) {
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
                    } else {
                        console.log("Show");
                        self.$('.js-signinfirst').show();
                    }
                });

                Iznik.Session.testLoggedIn();
                console.log("Tested");
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.Landing.Maintenance = Iznik.Views.Page.extend({  // CC
      template: "user_landing_maintenance",
      footer: true,
      noback: true
    });

});