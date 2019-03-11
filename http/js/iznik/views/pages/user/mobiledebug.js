define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages'
], function ($, _, Backbone, Iznik) { // CC
    Iznik.Views.User.Pages.MobileDebug = Iznik.Views.Page.extend({

        template: "user_settings_mobiledebug",

        render: function () {
            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
              $('#js-mobilelog').val(window.alllog)
            });
            return p;
        }
    });


});
