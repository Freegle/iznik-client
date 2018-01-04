define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/user/visualise'
], function ($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.RecentFreegles  = Iznik.Views.Page.extend({
        template: 'user_visualise_main',

        title: 'Recent Freegles',

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                var v = new Iznik.Views.Visualise.Map();
                v.render();
                self.$('.js-visualise').html(v.$el);
            });
            return (p);
        }
    });
});
