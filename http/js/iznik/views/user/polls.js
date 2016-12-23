define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/views/infinite'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Poll = Iznik.View.extend({
        events: {
            'click .js-click': 'click'
        },

        click: function(e) {
            var self = this;
            var val = $(e.target).data('value');
            console.log("Click value", val, e.target);
            $.ajax({
                url: API + 'poll',
                type: 'POST',
                data: {
                    id: self.poll.id,
                    response: {
                        'click': val
                    }
                },
                success: function(ret) {
                    self.$el.fadeOut('slow');
                }
            });
        },

        render: function() {
            var self = this;

            // Get next poll for this user.
            var p = $.ajax({
                url: API + 'poll',
                type: 'GET',
                success: function(ret) {
                    if (ret.ret === 0 && ret.hasOwnProperty('poll')) {
                        self.poll = ret.poll;
                        self.$el.html( _.template(ret.poll.template));

                        // We might have Facebook-specific stuff, e.g. Share.
                        self.$('.js-facebookonly').hide();

                        if (Iznik.Session.hasFacebook() && self.$('.js-facebookonly').length > 0) {
                            self.$('.js-facebookonly').fadeIn('slow');
                        }
                    }
                }
            });

            return(p);
        }
    });
});