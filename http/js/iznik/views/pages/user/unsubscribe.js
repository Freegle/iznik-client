define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Unsubscribe = Iznik.Views.Page.extend({
        template: "user_unsubscribe_main",

        events: {
            'click .js-leave': "leave",
            'click .js-findme': 'findme',
            'click .js-forget': 'forget'
        },

        forget: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function () {
                var v = new Iznik.Views.Confirm({
                    model: new Iznik.Model(Iznik.Session.get('me'))
                });

                v.template = 'user_unsubscribe_confirm';

                self.listenToOnce(v, 'confirmed', function() {
                    var p = Iznik.Session.forget();

                    p.then(function() {
                        (new Iznik.Views.User.Pages.Unsubscribe.Forgotten()).render();
                    });

                    p.catch(function(ret) {
                        var v = new Iznik.Views.User.Pages.Unsubscribe.ForgetFail({
                            model: new Iznik.Model({
                                status: ret.status
                            })
                        });
                        v.render();
                    });
                });

                v.render();
            });

            Iznik.Session.forceLogin();
        },

        findme: function() {
            var self = this;
            self.email = self.$('.js-email').val().trim();

            if (self.email.length > 0) {
                self.addAndShow();
            }
        },

        leave: function() {
            var self = this;

            var groupid = self.$('#groupid').val();

            var v = new Iznik.Views.Confirm({
                model: self.model
            });

            self.listenToOnce(v, 'confirmed', function() {
                $.ajax({
                    url: API + 'memberships',
                    type: 'POST',
                    headers: {
                        'X-HTTP-Method-Override': 'DELETE'
                    },
                    data: {
                        email: self.email,
                        groupid: groupid
                    }, complete: function() {
                        window.location.reload();
                    }
                })
            });

            v.render();
        },

        addAndShow: function() {
            var self = this;

            self.$('.js-nogroups').hide();

            // We fetch the groups for this email.  This is public even if we're not logged in - there's a privacy
            // leak there, but the benefit in allowing confused users who can't log in to unsubscribe outweighs it.
            // And details of which reuse groups you belong to is hardly on a par with your bank details.
            $.ajax({
                url: API + 'memberships',
                type: 'GET',
                data: {
                    email: self.email,
                    grouptype: 'Freegle'
                },
                success: function(ret) {
                    if (ret.ret == 0) {
                        if (ret.memberships.length > 0) {
                            _.each(ret.memberships, function(membership) {
                                self.$('select').append('<option value="' + membership.id + '" />');
                                self.$('select option:last').html(membership.namedisplay);
                            });
                            self.$('.js-groups').show();
                            self.$('.js-getemail').hide();
                            self.$('.js-nogroups').hide();
                            self.$('.js-toomany').show();
                        } else {
                            self.$('.js-leave').hide();
                            self.$('.js-nogroups').show();
                            self.$('.js-toomany').hide();
                        }
                    } else {
                        self.$('.js-nogroups').fadeIn('slow');
                    }
                }
            })
        },

        render: function() {
            var self = this;

            Iznik.Views.Page.prototype.render.call(self).then(function() {
                self.groups = Iznik.Session.get('groups');

                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    if (loggedIn) {
                        self.email = Iznik.Session.get('me').email;
                        self.addAndShow();
                    } else {
                        try {
                            var email = Storage.get('myemail');
                            if (email) {
                                self.$('.js-email').val(email);
                            }
                        } catch (e) {
                        }

                        self.$('.js-getemail').show();
                    }
                });

                Iznik.Session.testLoggedIn([
                    'me',
                    'groups'
                ]);
            });

            return(this);
        }
    });

    Iznik.Views.User.Pages.Unsubscribe.Forgotten = Iznik.Views.Modal.extend({
        template: 'user_unsubscribe_forgotten'
    });

    Iznik.Views.User.Pages.Unsubscribe.ForgetFail = Iznik.Views.Modal.extend({
        template: 'user_unsubscribe_forgetfail'
    });
});