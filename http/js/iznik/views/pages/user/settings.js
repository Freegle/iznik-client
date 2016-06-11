define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Settings = Iznik.Views.Infinite.extend({
        template: "user_settings_main",

        events: {
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);

            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'user_settings_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                });

            });

            return (p);
        }
    });
});