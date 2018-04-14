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
});