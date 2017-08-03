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
            'click .js-click': 'click',
            'click .js-submit': 'submit'
        },

        submit: function() {
            var self = this;
            var form = self.$('form').serializeArray();
            console.log("Form", form);

            $.ajax({
                url: API + 'poll',
                type: 'POST',
                data: {
                    id: self.poll.id,
                    response: form
                },
                success: function(ret) {
                    self.$el.fadeOut('slow');
                }
            });
        },

        click: function(e) {
            var self = this;
            var val = $(e.target).closest('.js-click').data('value');

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
                        self.delegateEvents();

                        // We might have Facebook-specific stuff, e.g. Share.
                        self.$('.js-facebookonly').hide();

                        if (Iznik.Session.hasFacebook() && self.$('.js-facebookonly').length > 0) {
                            self.$('.js-facebookonly').fadeIn('slow');
                        }

                        // Record that we've shown it - then we know that if they don't click.
                        $.ajax({
                            url: API + 'poll',
                            type: 'POST',
                            data: {
                                id: self.poll.id,
                                shown: true
                            }
                        });
                    }
                }
            });

            return(p);
        }
    });
});