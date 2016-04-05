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

        wait: function() {
            console.log("Start long poll");
            // We have a long poll open to the server, which when it completes may prompt us to do some work on a
            // chat.  That way we get zippy messaging.
            //
            // TODO use a separate domain name to get round client-side limits on the max number of HTTP connections
            // to a single host.  We use a single connection rather than a per chat one for the same reason.
            var self = this;
            var me = Iznik.Session.get('me');
            var myid = me ? me.id : null;

            if (!myid) {
                // Not logged in, try later;
                _.delay(self.wait, 5000);
            } else {
                $.ajax({
                    url: window.location.protocol + '//' + window.location.hostname + '/subscribe/' + myid,
                    success: function(ret) {
                        console.log("Poll returned", ret);
                        if (ret.hasOwnProperty('text')) {
                            var data = ret.text;

                            if (data.hasOwnProperty('roomid')) {
                                // Activity on this room.  Refetch the mesages within it.
                                console.log("Refetch chat", data.roomid, self);
                                var chat = self.chatViews[data.roomid];
                                console.log("Got chat", chat);
                                chat.messages.fetch().then(function() {
                                    self.wait();
                                });
                            }
                        }
                    }, error: function() {
                        // Probably a network glitch.  Retry later.
                        _.delay(self.wait, 5000);
                    }
                });
            }
        },

        render: function() {
            console.log("Render chat wrapper");
            var self = this;
            self.$el.html(window.template(self.template)());
            $("#bodyEnvelope").append(self.$el);

            self.chatViews = [];
            self.chats = new Iznik.Collections.Chat.Rooms();
            self.chats.fetch().then(function() {
                self.chats.each(function(chat) {
                    var v = new Iznik.Views.Chat.Window({
                        model: chat
                    });
                    self.chatViews[chat.get('id')] = v;
                    v.render();
                })
            })

            self.wait();
        }
    });

                Iznik.Views.Chat.Window = Iznik.View.extend({
        template: 'chat_window',

        className: 'chat-window col-xs-4 col-md-3 col-lg-2 nopad',

        events: {
            'click .js-close': 'close',
            'keyup .js-message': 'keyUp'
        },

        keyUp: function(e) {
            var self = this;
            if (e.which === 13) {
                self.$('.js-message').prop('disabled', true);
                var message = this.$('.js-message').val();
                if (message.length > 0) {
                    self.listenToOnce(this.model, 'sent', function() {
                        self.messages.fetch().then(function() {
                            self.$('.js-message').val('');
                            self.$('.js-message').prop('disabled', false);
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
            var self = this;
            _.delay(function() {
                var msglist = self.$('.js-messages');
                var height = msglist[0].scrollHeight;
                msglist.scrollTop(height);
            }, 100);
        },

        render: function () {
            var self = this;
            self.$el.html(window.template(self.template)(self.model.toJSON2()));
            $("#chatWrapper").append(self.$el);

            self.$el.attr('id', 'chat-' + self.model.get('id'));

            // We position chat windows from the right, leftwards.
            var myind = self.model.collection.indexOf(self.model);
            self.$el.css('right', myind * 305);

            self.messages = new Iznik.Collections.Chat.Messages({
                roomid: self.model.get('id')
            });

            self.collectionView = new Backbone.CollectionView({
                el: self.$('.js-messages'),
                modelView: Iznik.Views.Chat.Message,
                collection: self.messages
            });

            self.messages.on('add', function() {
                self.scrollBottom();
                self.$('.chat-when').hide();
                self.$('.chat-when:last').show();
            })

            self.collectionView.render();
            self.messages.fetch();

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

