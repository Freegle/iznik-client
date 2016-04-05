console.log("Load chat window");
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
            'click .js-close': 'close',
            'keyup .js-message': 'keyUp'
        },

        keyUp: function(e) {
            var self = this;
            if (e.which === 13) {
                var message = this.$('.js-message').val();
                if (message.length > 0) {
                    self.listenToOnce(this.model, 'sent', function() {
                        self.collection.fetch().then(function() {
                            self.$('.js-message').val('');
                        })
                    })
                    this.model.send(message);
                }

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        },

        close: function() {
            this.$el.remove();
        },

        scrollBottom: function() {
            console.log("scrollBottom");
            var self = this;
            _.delay(function() {
                console.log("Scroll bottom");
                var msglist = self.$('.js-messages');
                var height = msglist[0].scrollHeight;
                console.log("Scroll to", height);
                msglist.scrollTop(height);
            }, 100);
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

            self.collection.on('add', function() {
                self.scrollBottom();
                self.$('.chat-when').hide();
                self.$('.chat-when:last').show();
            })

            self.collectionView.render();

            self.scrollBottom();
        }
    });

    Iznik.Views.Chat.Message = Iznik.View.extend({
        template: 'chat_message',

        render: function() {
            this.$el.html(window.template(this.template)(this.model.toJSON2()));
            this.$('.timeago').timeago();
            this.$el.fadeIn('slow');
        }
    });

});

