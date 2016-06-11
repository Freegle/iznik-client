define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/help'
], function($, _, Backbone, Iznik) {
    // We extend WhereAmI to get the location-choosing code.
    Iznik.Views.User.Pages.Settings = Iznik.Views.User.Pages.WhereAmI.extend({
        template: "user_settings_main",

        getLocation: function() {
            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        render: function () {
            var p = Iznik.Views.User.Pages.WhereAmI.prototype.render.call(this, {
                model: new Iznik.Model(Iznik.Session.get('settings'))
            });

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