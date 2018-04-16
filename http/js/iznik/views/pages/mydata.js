define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/views/pages/pages',
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

                            _.each(self.model.get('memberships'), function(membership) {
                                var v = new Iznik.Views.MyData.Membership({
                                    model: new Iznik.Model(membership)
                                });
                                v.render();

                                self.$('.js-memberships').append(v.$el);
                            });

                            _.each(self.model.get('membershipshistory'), function(membership) {
                                var v = new Iznik.Views.MyData.MembershipHistory({
                                    model: new Iznik.Model(membership)
                                });
                                v.render();

                                self.$('.js-membershipshistory').append(v.$el);
                            });

                            _.each(self.model.get('searches'), function(search) {
                                var v = new Iznik.Views.MyData.Search({
                                    model: new Iznik.Model(search)
                                });
                                v.render();

                                self.$('.js-searches').append(v.$el);
                            });

                            _.each(self.model.get('alerts'), function(alert) {
                                var v = new Iznik.Views.MyData.Alert({
                                    model: new Iznik.Model(alert)
                                });
                                v.render();

                                self.$('.js-alerts').append(v.$el);
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
});