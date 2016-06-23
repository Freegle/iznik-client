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

    Iznik.Views.User.Pages.Landing.About = Iznik.Views.Page.extend({
        template: "user_landing_about",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Terms = Iznik.Views.Page.extend({
        template: "user_landing_terms",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Privacy = Iznik.Views.Page.extend({
        template: "user_landing_privacy",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Disclaimer = Iznik.Views.Page.extend({
        template: "user_landing_disclaimer",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Donate = Iznik.Views.Page.extend({
        template: "user_landing_donate",
        footer: true,
        noback: true
    });

    Iznik.Views.User.Pages.Landing.Contact = Iznik.Views.Page.extend({
        template: "user_landing_contact",
        footer: true,
        noback: true
    });
});