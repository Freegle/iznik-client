define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/models/volunteering',
    'iznik/views/pages/user/pages',
    'iznik/views/group/volunteering'
], function ($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Pages.Volunteerings = Iznik.Views.Page.extend({
        template: 'user_volunteering_main',

        render: function () {
            var self = this;
            console.log("Render, group", self.options);

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                var v = new Iznik.Views.User.VolunteeringFull({
                    groupid: self.options.groupid
                });

                v.render().then(function() {
                    self.$('.js-volunteering').html(v.$el);
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.Volunteering = Iznik.Views.Page.extend({
        template: 'user_volunteering_singlemain',

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                var mod = new Iznik.Models.Volunteering({
                    id: self.options.id
                });

                mod.fetch().then(function() {
                    if (mod.get('title')) {
                        var v = new Iznik.Views.User.Volunteering.Single({
                            model: mod
                        });

                        v.render().then(function() {
                            self.$('.js-volunteering').html(v.$el);
                        });
                    } else {
                        var v = new Iznik.Views.User.Volunteering.Deleted();

                        v.render().then(function() {
                            self.$('.js-volunteering').html(v.$el);
                        });
                    }
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.Volunteering.Deleted = Iznik.View.extend({
        template: 'user_volunteering_deleted'
    });

    Iznik.Views.User.Volunteering.Single = Iznik.View.extend({
        template: 'user_volunteering_single',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);
            p.then(function() {
                self.$('.js-dates').empty();
                _.each(self.model.get('dates'), function(date) {
                    var start = (new moment(date.start)).format('ddd, Do MMM YYYY HH:mm');
                    var end = (new moment(date.end)).format('ddd, Do MMM YYYY HH:mm');
                    self.$('.js-dates').append(start + ' - ' + end + '<br />');
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.VolunteeringFull = Iznik.Views.User.VolunteeringSidebar.extend({
        template: 'user_volunteering_full',

        refetch: function() {
            var self = this;
            var args = {
                reset: true
            };

            // We might fetch all volunteering vacancies or those for a specific group.
            if (self.selected > 0) {
                args.data = {
                    groupid: self.selected
                }
            }

            self.volunteering.fetch(args).then(function() {
                self.$('.js-list').fadeIn('slow');
                if (self.volunteering.length == 0) {
                    self.$('.js-none').fadeIn('slow');
                }
            });
        },

        render: function () {
            var self = this;

            if (self.options.groupid) {
                // Not logged in - expect to be called for a specific group.
                self.selected = self.options.groupid;
            }

            // We extend the sidebar as that has some event handling we want, but we don't want its render.
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.volunteering = new Iznik.Collections.Volunteering();

                self.volunteeringView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.User.Volunteering,
                    collection: self.volunteering,
                    processKeyEvents: false
                });

                self.volunteeringView.render();

                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    if (loggedIn) {
                        // We can filter by group.
                        var v = new Iznik.Views.Group.Select({
                            systemWide: false,
                            all: true,
                            mod: false,
                            grouptype: 'Freegle',
                            id: 'volunteeringGroupSelect',
                            selected: self.options.groupid
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
