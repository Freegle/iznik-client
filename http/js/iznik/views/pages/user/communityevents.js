define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/views/pages/user/pages',
    'iznik/views/group/communityevents'
], function ($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Pages.CommunityEvents = Iznik.Views.Page.extend({
        template: 'user_communityevents_main',

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                var v = new Iznik.Views.User.CommunityEventsFull({
                    groupid: self.options.groupid
                });

                v.render().then(function() {
                    self.$('.js-events').html(v.$el);
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.CommunityEventsFull = Iznik.Views.User.CommunityEventsSidebar.extend({
        template: 'user_communityevents_full',

        refetch: function() {
            var self = this;
            var args = {
                reset: true
            };

            // We might fetch all events or those for a specific group.
            if (self.selected > 0) {
                args.data = {
                    groupid: self.selected
                }
            }

            self.events.fetch(args).then(function() {
                self.$('.js-list').fadeIn('slow');
                if (self.events.length == 0) {
                    self.$('.js-none').fadeIn('slow');
                }
            });
        },

        render: function () {
            var self = this;

            // We extend the sidebar as that has some event handling we want, but we don't want its render.
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.events = new Iznik.Collections.CommunityEvent();

                self.eventsView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.User.CommunityEvent,
                    collection: self.events
                });

                self.eventsView.render();

                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    if (loggedIn) {
                        // We can filter by group.
                        var v = new Iznik.Views.Group.Select({
                            systemWide: false,
                            all: true,
                            mod: false,
                            grouptype: 'Freegle',
                            id: 'eventsGroupSelect'
                        });

                        self.listenTo(v, 'selected', function(selected) {
                            self.selected = selected;
                            self.refetch();
                        });

                        // Render after the listen to as that are called during render.
                        v.render().then(function(v) {
                            self.$('.js-groupselect').html(v.el);
                        });
                    } else if (self.options.groupid) {
                        // Not logged in - expect to be called for a specific group.
                        self.selected = self.options.groupid;
                        self.refetch();
                    }
                });

                Iznik.Session.testLoggedIn();

            });

            return(p);
        }
    });

});
