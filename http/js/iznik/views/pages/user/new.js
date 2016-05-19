define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.New = Iznik.Views.Page.extend({
        template: "user_new_main",
        
        events: {
            'click .js-setpass': 'setPass'
        },
        
        setPass: function() {
            var self = this;

            // We grab the new user id from where we saved it in post.js.
            Iznik.Session.save({
                id: Iznik.Session.get('newuser').id,
                password: this.$('.js-pass').val()
            }, {
                patch: true
            }).then(function() {
                self.$('.js-passstuff').slideUp();
            });
        },

        render: function() {
            Iznik.Views.Page.prototype.render.call(this, {
                model: new Iznik.Model(Iznik.Session)
            });
        }
    });
});