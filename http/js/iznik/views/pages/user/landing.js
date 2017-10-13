define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/chat/chat',
    'iznik/models/donations',
    'iznik/views/pages/pages',
    'iznik/views/group/select',
    'jquery.dd'
], function($, _, Backbone, Iznik, ChatHolder) {
    Iznik.Views.User.Pages.Landing = Iznik.Views.Page.extend({
        template: "user_landing_main",
        footer: true,
        appButtons: true,

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);

            p.then(function(self) {
                // Add stories
                require(['iznik/models/membership'], function () {
                    self.collection = new Iznik.Collections.Members.Stories();

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-stories'),
                        modelView: Iznik.Views.User.Pages.Landing.Story,
                        collection: self.collection,
                        processKeyEvents: false
                    });

                    self.collectionView.render();

                    self.collection.fetch({
                        data: {
                            story: false,
                            limit: 3
                        }
                    });
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.Landing.Story = Iznik.View.Timeago.extend({
        template: "user_landing_story",

        tagName: 'li'
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

    Iznik.Views.User.Pages.Landing.Handbook = Iznik.Views.Page.extend({
        template: "user_landing_handbook",
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

    Iznik.Views.User.Pages.Landing.Why = Iznik.Views.Page.extend({
        template: "user_landing_why",
        footer: false,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Donate = Iznik.Views.Page.extend({
        template: "user_landing_donate",
        footer: true,
        noback: true,

        render: function() {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function () {
                var v = new Iznik.Views.DonationThermometer();
                v.render().then(function () {
                    self.$('.js-thermometer').html(v.$el);
                });
            });

            return(p);
        }
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

            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function () {
                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    if (loggedIn) {
                        var groups = Iznik.Session.get('groups');

                        if (groups.length >= 0) {
                            self.groupSelect = new Iznik.Views.Group.Select({
                                systemWide: false,
                                all: false,
                                mod: false,
                                choose: true,
                                grouptype: 'Freegle',
                                id: 'contactGroupSelect'
                            });

                            self.groupSelect.render().then(function() {
                                self.$('.js-groupselect').html(self.groupSelect.el);
                            });

                            self.$('.js-contactmods').show();
                        }
                    } else {
                        self.$('.js-signinfirst').show();
                    }
                });

                Iznik.Session.testLoggedIn();
                console.log("Tested");
            });

            return (p);
        }
    });
});