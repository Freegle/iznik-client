define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/chat/chat',
    'jquery-resizable'
], function($, _, Backbone, Iznik) {
    Iznik.Views.Chat.Holder = Iznik.View.extend({
        template: 'chat_holder',

        id: "chatHolder",

        wait: function() {
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
                                var chat = self.chats.get(data.roomid);
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

        removeView: function(chat) {
            console.log("Remove chat", this, chat.model.get('id'));
            this.$el.hide();
            delete this.chatViews[chat.model.get('id')];
        },

        organise: function() {
            var self = this;
            var maxHeight = 0;
            self.$('.chat-window').each(function() {
                maxHeight = maxHeight > $(this).height() ? maxHeight : $(this).height();
            });

            self.$('.chat-window').each(function() {
                $(this).css('margin-top', (maxHeight - $(this).outerHeight()) + 'px');
            });

            var minimised = 0;
            $('#chatMinimised ul').empty();

            if (self.collectionView) {
                self.collectionView.viewManager.each(function(chat) {
                    if (chat.minimised) {
                        minimised++;
                        var v = new Iznik.Views.Chat.Minimised({
                            model: chat.model,
                            chat: chat
                        });
                        $('#chatMinimised ul').append(v.render().el);
                    }
                });
            }

            if (minimised == 0) {
                $('#chatMinimised').hide();
            } else {
                $('#chatMinimised .js-title').html("Chats (" + minimised + ")");
                $('#chatMinimised').show();
            }
        },

        render: function() {
            var self = this;

            self.$el.html(window.template(self.template)());
            $("#bodyEnvelope").append(self.$el);

            self.chats = new Iznik.Collections.Chat.Rooms();
            self.chats.fetch().then(function() {
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-chats'),
                    modelView: Iznik.Views.Chat.Window,
                    collection: self.chats,
                    modelViewOptions: {
                        'organise': _.bind(self.organise, self)
                    }
                });

                self.collectionView.render();
                self.organise();
            })

            self.wait();
        }
    });

    Iznik.Views.Chat.Minimised = Iznik.View.extend({
        template: 'chat_minimised',

        tagName: 'li',

        events: {
            'click': 'click'
        },

        click: function() {
            console.log("Rstore", this.options.chat);
            this.options.chat.restore();
        }
    });

    Iznik.Views.Chat.Window = Iznik.View.extend({
        template: 'chat_window',

        tagName: 'li',

        className: 'chat-window col-xs-4 col-md-3 col-lg-2 nopad',

        events: {
            'click .js-close, touchstart .js-close': 'remove',
            'click .js-minimise, touchstart .js-minimise': 'minimise',
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

        lsID: function() {
            return('chat-' + this.model.get('id'));
        },

        remove: function() {
            this.trigger('removed', this);
            this.$el.remove();
        },

        minimise: function() {
            this.$el.hide();
            this.minimised = true;
            this.trigger('minimised');
            this.options.organise();

            try {
                localStorage.setItem(this.lsID() + '-minimised', true);
            } catch (e) { window.alert(e.message)};
        },

        restore: function() {
            this.$el.show();
            this.minimised = false;
            this.trigger('restored');
            this.options.organise();

            try {
                localStorage.removeItem(this.lsID() + '-minimised');
            } catch (e) {}
        },

        scrollBottom: function() {
            var self = this;
            _.delay(function() {
                var msglist = self.$('.js-messages');
                var height = msglist[0].scrollHeight;
                msglist.scrollTop(height);
            }, 100);
        },

        drag: function(event, el, opt) {
            var self = this;

            // We will need to remargin any other chats.
            self.trigger('resized');
            self.options.organise();

            // Save the new height to local storage so that we can restore it next time.
            try {
                localStorage.setItem(this.lsID() + '-height', self.$el.height());
                localStorage.setItem(this.lsID() + '-width', self.$el.width());
            } catch (e) {}
        },

        render: function () {
            var self = this;

            // Hide until we've finished rendering and deciding which are minimised.
            self.$el.css('visibility', 'hidden');

            self.messages = new Iznik.Collections.Chat.Messages({
                roomid: self.model.get('id')
            });

            self.messages.fetch().then(function() {
                self.$el.html(window.template(self.template)(self.model.toJSON2()));
                var mobile = isMobile();

                try {
                    // Restore any saved height
                    var height = localStorage.getItem('chat-' + self.model.get('id') + '-height');
                    var width = localStorage.getItem('chat-' + self.model.get('id') + '-width');

                    if (height && width) {
                        self.$el.height(height);
                        self.$el.width(width);
                    }

                    // On mobile we start them all minimised as there's not much room.
                    console.log("Minimise?", localStorage.getItem(self.lsID() + '-minimised'), mobile);
                    if (localStorage.getItem(self.lsID() + '-minimised')|| mobile) {
                        self.minimise();
                    }
                } catch (e) {}

                self.$el.attr('id', 'chat-' + self.model.get('id'));

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-messages'),
                    modelView: Iznik.Views.Chat.Message,
                    collection: self.messages
                });

                self.messages.on('add', function() {
                    self.scrollBottom();
                    self.$('.chat-when').hide();
                    self.$('.chat-when:last').show();
                });

                self.collectionView.render();

                self.scrollBottom();

                self.$el.resizable({
                    handleSelector: '.js-grip',
                    resizeWidthFrom: 'left',
                    resizeHeightFrom: 'top',
                    onDrag: _.bind(self.drag, self)
                });

                self.trigger('rendered');
                _.defer(function() {
                    self.options.organise();
                    self.$el.css('visibility', 'visible');
                });
            });
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

