define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/user/schedule',
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Schedule = Iznik.Views.Page.extend({
        template: "user_schedule_main",

        events: {
        },

        render: function () {
            var self = this;

            self.model = new Iznik.Models.Schedule({
                id: self.options.id
            });

            var p = new Promise(function(resolve, reject) {
                self.model.fetch().then(function() {
                    // Find the other user.
                    var me = Iznik.Session.get('me');
                    var myid = me ? me.id : null;

                    var users = self.model.get('users');
                    _.each(users, function(user) {
                        if (user != myid) {
                            var user = new Iznik.Models.ModTools.User({
                                id: user
                            });

                            user.fetch().then(function() {
                                Iznik.Views.Page.prototype.render.call(self).then(function() {
                                    var v = new Iznik.Views.Help.Box();
                                    v.template = 'user_schedule_help';
                                    v.render().then(function (v) {
                                        self.$('.js-schedulehelp').html(v.el);
                                    });

                                    var v = new Iznik.Views.User.Schedule({
                                        model: user,
                                        id: self.model.get('id'),
                                        slots: self.model.get('schedule'),
                                        other: true,
                                        cancel: false
                                    });

                                    self.$('.js-schedule').html(v.$el);
                                    v.render().then(function() {
                                        resolve();
                                    });
                                });
                            })
                        }
                    });

                });
            });

            return(p);
        }
    });
});