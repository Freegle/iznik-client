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
            $.ajax({
                url: API + 'session',
                type: 'PATCH',
                data: {
                    id: Iznik.Session.get('newuser'),
                    password: this.$('.js-pass').val()
                }, success: function(ret) {
                    if (ret.ret === 0) {
                        Router.navigate('', true);
                    }
                }
            })
        },

        render: function() {
            return(Iznik.Views.Page.prototype.render.call(this, {
                model: new Iznik.Model(Iznik.Session)
            }));
        }
    });
});