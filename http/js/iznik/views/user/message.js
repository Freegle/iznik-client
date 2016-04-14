define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Message = Iznik.View.extend({
        className: "panel panel-info marginbotsm",

        events: {
            'click .js-caretup': 'caretup',
            'click .js-caretdown': 'caretdown'
        },

        caretup: function() {
            this.$('.js-caretup').hide();
            this.$('.js-caretdown').show();
        },

        caretdown: function() {
            this.$('.js-caretdown').hide();
            this.$('.js-caretup').show();
        },

        render: function() {
            var self = this;

            // Make sure any URLs in the message break.
            this.model.set('textbody', wbr(this.model.get('textbody'), 20));

            Iznik.View.prototype.render.call(self);
            var groups = self.model.get('groups');

            _.each(groups, function(group) {
                var v = new Iznik.Views.User.Message.Group({
                    model: new Iznik.Model(group)
                });
                self.$('.js-groups').append(v.render().el);
            });

            _.each(self.model.get('attachments'), function (att) {
                var v = new Iznik.Views.User.Message.Photo({
                    model: new Iznik.Model(att)
                });

                self.$('.js-attlist').append(v.render().el);
            });

            return(this);
        }
    });

    Iznik.Views.User.Message.Group = Iznik.View.extend({
        template: "user_message_group",

        render: function() {
            Iznik.View.prototype.render.call(this);
            this.$('.timeago').timeago();
            return(this);
        }
    });

    Iznik.Views.User.Message.Photo = Iznik.View.extend({
        tagName: 'li',

        template: 'user_message_photo'
    });
});