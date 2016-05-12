define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Unsubscribe = Iznik.Views.Page.extend({
        template: "user_unsubscribe_main"
    });
});