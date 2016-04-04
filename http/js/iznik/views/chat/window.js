define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/chat/chat'
], function($, _, Backbone, Iznik) {
    Iznik.Views.Chat.Holder = Iznik.View.extend({
        template: 'chat_holder',

        className: 'row-fluid',

        render: function() {
            var self = this;
            self.$el.html(window.template(self.template)(self.model.toJSON2()));
            $("#chatWrapper").append(self.$el);

            self.collectionView = new Backbone.CollectionView({
                el: self.$('.js-messages'),
                modelView: Iznik.Views.Chat.Window,
                collection: self.collection
            });

            self.collectionView.render();
        }
    });
    
    Iznik.Views.Chat.Window = Iznik.View.extend({
        template: 'chat_window',

        className: 'chat-window col-xs-6 col-md-4 col-lg-2 nopad',

        events: {
            'click .js-close': 'close'
        },

        close: function() {
            this.$el.remove();
        },

        render: function () {
            var self = this;
            self.$el.html(window.template(self.template)(self.model.toJSON2()));
            $("#chatWrapper").append(self.$el);
            self.$el.attr('id', 'chat-' + self.model.get('id'));

            self.collectionView = new Backbone.CollectionView({
                el: self.$('.js-messages'),
                modelView: Iznik.Views.Chat.Message,
                collection: self.collection
            });

            self.collectionView.render();
        }
    });

    Iznik.Views.Chat.Message = Iznik.View.extend({
        template: 'chat_message',

        render: function() {
            this.$el.html(window.template(this.template)(this.model.toJSON2()));
            this.$('.timeago').timeago();
        }
    });

});

