define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'jquery-show-first',
    'iznik/views/pages/pages',
    'iznik/views/group/communityevents',
    'iznik/views/group/volunteering',
], function ($, _, Backbone, Iznik, moment) {
    Iznik.Views.MyData = Iznik.Views.Page.extend({
        template: 'mydata_main',

        modtools: MODTOOLS,

        noGoogleAds: true,

        render: function() {
            var self = this;

            self.wait = new Iznik.Views.PleaseWait({
                label: 'chat openChat'
            });
            self.wait.render();

            $.ajax({
                url: API + 'user',
                data: {
                    id: Iznik.Session.get('me').id,
                    export: true
                },
                success: function(ret) {
                    if (ret.ret === 0 && ret.export) {
                        var user = new Iznik.Model(ret.export.user);
                        self.model = user;

                        var p = Iznik.Views.Page.prototype.render.call(self);

                        p.then(function() {
                            if (Iznik.Session.isFreegleMod()) {
                                self.$('.js-modonly').show();
                            } else {
                                self.$('.js-modonly').hide();
                            }

                            self.$('.js-date').each((function() {
                                var m = new moment($(this).html().trim());
                                $(this).html(m.format('MMMM Do YYYY, h:mm:ss a'));
                            }));

                            _.each(self.model.get('invitations'), function(invite) {
                                var m = new moment(invite.date);
                                invite.date = m.format('MMMM Do YYYY, h:mm:ss a');
                                var v = new Iznik.Views.MyData.Invitation({
                                    model: new Iznik.Model(invite)
                                });
                                v.render();
                                self.$('.js-invitations').append(v.$el);
                            });

                            _.each(self.model.get('emails'), function(email) {
                                // No need to show emails which are our own domains.  The user didn't
                                // provide them.
                                if (!email.ourdomain) {
                                    var m = new moment(email.added);
                                    email.added = m.format('MMMM Do YYYY, h:mm:ss a');

                                    if (email.validated) {
                                        var m = new moment(email.validated);
                                        email.validated= m.format('MMMM Do YYYY, h:mm:ss a');
                                    }

                                    var v = new Iznik.Views.MyData.Email({
                                        model: new Iznik.Model(email)
                                    });
                                    v.render();

                                    self.$('.js-emails').append(v.$el);
                                }
                            });

                            _.each([
                                [ 'memberships', Iznik.Views.MyData.Membership, '.js-memberships' ],
                                [ 'membershipshistory', Iznik.Views.MyData.MembershipHistory, '.js-membershipshistory' ],
                                [ 'searches', Iznik.Views.MyData.Search, '.js-searches' ],
                                [ 'alerts', Iznik.Views.MyData.Alert, '.js-alerts' ],
                                [ 'donations', Iznik.Views.MyData.Donation, '.js-donations' ],
                                [ 'bans', Iznik.Views.MyData.Ban, '.js-bans' ],
                                [ 'spammers', Iznik.Views.MyData.Spammer, '.js-spammers' ],
                                [ 'images', Iznik.Views.MyData.Image, '.js-images' ],
                                [ 'notifications', Iznik.Views.MyData.Notification, '.js-notifications' ],
                                [ 'addresses', Iznik.Views.MyData.Address, '.js-addresses' ],
                                [ 'communityevents', Iznik.Views.MyData.CommunityEvent, '.js-communityevents' ],
                                [ 'volunteering', Iznik.Views.MyData.Volunteering, '.js-volunteerings' ],
                            ], function(view) {
                                    _.each(self.model.get(view[0]), function(mod) {
                                        var v = new view[1]({
                                            model: new Iznik.Model(mod)
                                        });
                                        v.render();

                                        self.$(view[2]).append(v.$el);
                                    });
                            });

                            self.$('.js-more').each(function() {
                                $(this).showFirst({
                                    controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
                                    count: 5
                                });
                            });

                            self.wait.close();
                        });
                    }
                }
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Invitation = Iznik.View.extend({
        template: 'mydata_invitation'
    });

    Iznik.Views.MyData.Email = Iznik.View.extend({
        template: 'mydata_email'
    });

    Iznik.Views.MyData.Membership = Iznik.View.extend({
        template: 'mydata_membership',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var freq = self.model.get('mysettings').emailfrequency;
                self.$('.js-emailfrequency option[value=' + freq + ']').prop('selected', true);
            });

            return(p);
        }
    });

    Iznik.Views.MyData.MembershipHistory = Iznik.View.extend({
        template: 'mydata_membershiphistory',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('added'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Search = Iznik.View.extend({
        template: 'mydata_search',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Alert = Iznik.View.extend({
        template: 'mydata_alert',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('responded'));
                self.$('.js-responded').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Donation = Iznik.View.extend({
        template: 'mydata_donation',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('timestamp'));
                self.$('.js-timestamp').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Ban = Iznik.View.extend({
        template: 'mydata_ban',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Spammer = Iznik.View.extend({
        template: 'mydata_spammer',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('added'));
                self.$('.js-added').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Image = Iznik.View.extend({
        template: 'mydata_image',

        className: 'inline'
    });

    Iznik.Views.MyData.Notification = Iznik.View.extend({
        template: 'mydata_notification',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('timestamp'));
                self.$('.js-timestamp').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Address = Iznik.View.extend({
        template: 'mydata_address',
    });

    Iznik.Views.MyData.CommunityEvent = Iznik.View.extend({
        template: 'mydata_communityevent',

        events: {
            'click .js-details': 'details'
        },

        details: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            var m = new Iznik.Models.CommunityEvent({
                id: this.model.get('id')
            });

            m.fetch().then(function() {
                var v = new Iznik.Views.User.CommunityEvent.Details({
                    model: self.model
                });

                v.render();
            });
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('added'));
                self.$('.js-added').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Volunteering = Iznik.View.extend({
        template: 'mydata_volunteering',

        events: {
            'click .js-details': 'details'
        },

        details: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            var m = new Iznik.Models.Volunteering({
                id: this.model.get('id')
            });

            m.fetch().then(function() {
                var v = new Iznik.Views.User.Volunteering.Details({
                    model: self.model
                });

                v.render();
            });
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('added'));
                self.$('.js-added').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });
});