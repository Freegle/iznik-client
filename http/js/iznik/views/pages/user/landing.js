define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Landing = Iznik.Views.Page.extend({
        template: "user_landing_main",
        footer: true
    });
});