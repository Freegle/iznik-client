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
                        var waiting = false;
                        if (ret.hasOwnProperty('text')) {
                            var data = ret.text;

                            if (data.hasOwnProperty('roomid')) {
                                // Activity on this room.  Refetch the mesages within it.
                                // console.log("Refetch chat", data.roomid, self);
                                var chat = self.chats.get(data.roomid);
                                console.log("Got chat", chat);
                                var chatView = self.collectionView.viewManager.findByModel(chat);
                                console.log("Got chatView", chatView);
                                waiting = true;
                                chatView.messages.fetch().then(function() {
                                    self.wait();
                                });
                            }
                        }

                        if (!waiting) {
                            self.wait();
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
            var minimised = 0;
            var totalWidth = 0;
            var totalMax = 0;

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
                    } else {
                        totalWidth += chat.$el.outerWidth();
                        totalMax++;
                    }
                });

                console.log("Compare widths", totalWidth, totalMax, window.innerWidth);
                var max = window.innerWidth - $('#chatMinimised').outerWidth() - 5;
                if (totalWidth > max) {
                    // The chat windows we have open are too wide.  Make them narrower.
                    console.log("Chats too wide", max, totalWidth);
                    var width = Math.round(max / totalMax - 0.5) - 10;
                    console.log("New width", width);

                    self.collectionView.viewManager.each(function(chat) {
                        if (!chat.minimised) {
                            chat.$el.width(width);
                        }
                    });
                }
            }

            var maxHeight = 0;

            self.$('.chat-window').each(function() {
                maxHeight = maxHeight > $(this).height() ? maxHeight : $(this).height();
            });

            self.$('.chat-window').each(function() {
                $(this).css('margin-top', (maxHeight - $(this).outerHeight()) + 'px');
            });

            if (minimised == 0) {
                $('#chatMinimised').hide();
            } else {
                $('#chatMinimised .js-title').html("Chats (" + minimised + ")");
                $('#chatMinimised').show();
            }
        },

        updateCounts: function() {
            var self = this;
            var unseen = 0;
            self.chats.each(function(chat) {
                var chatView = self.collectionView.viewManager.findByModel(chat);
                if (chatView.minimised) {
                    unseen += chat.get('unseen');
                }
            });

            if (unseen > 0) {
                self.$('.js-count').html(unseen).show();
            } else {
                self.$('.js-count').html(unseen).hide();
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

                self.updateCounts();
                self.organise();
            });

            self.chats.each(function(chat) {
                // If the unread message count changes, we want to update it.
                self.listenTo(chat, 'change:unseen', self.updateCount);
            });

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
            this.options.chat.restore();
        },

        updateCount: function() {
            var self = this;
            var unseen = self.model.get('unseen');

            if (unseen > 0) {
                self.$('.js-count').html(unseen).show();
            } else {
                self.$('.js-count').html(unseen).hide();
            }

            self.trigger('countupdated', unseen);
        },

        render: function() {
            var self = this;
            self.$el.html(window.template(self.template)(self.model.toJSON2()));
            self.updateCount();

            // If the unread message count changes, we want to update it.
            self.listenTo(self.model, 'change:unseen', self.updateCount);

            return(self);
        }
    });

    Iznik.Views.Chat.Window = Iznik.View.extend({
        template: 'chat_window',

        tagName: 'li',

        className: 'chat-window nopad col-xs-4 col-md-3 col-lg-2',

        events: {
            'click .js-close, touchstart .js-close': 'remove',
            'click .js-minimise, touchstart .js-minimise': 'minimise',
            'focus .js-message': 'messageFocus',
            'keyup .js-message': 'keyUp'
        },

        removed: false,

        keyUp: function(e) {
            var self = this;
            if (e.which === 13) {
                self.$('.js-message').prop('disabled', true);
                var message = this.$('.js-message').val();
                if (message.length > 0) {
                    self.listenToOnce(this.model, 'sent', function(id) {
                        self.model.set('lastmsgseen', id);
                        self.$('.js-message').val('');
                        self.$('.js-message').prop('disabled', false);
                        self.$('.js-message').focus();
                        self.messageFocus();
                        self.messages.fetch().then();
                    });
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
            this.removed = true;
            this.$el.remove();
        },

        messageFocus: function() {
            var self = this;

            this.model.set('lastmsgseen', this.messages.at(this.messages.length - 1).get('id'));
            this.model.set('unseen', 0);
            _.delay(function() {
                self.$('.chat-message-unseen').removeClass('chat-message-unseen');
            }, 5000);
            this.updateCount();
        },

        minimise: function() {
            var self = this;
            _.defer(function() {
                self.$el.hide();
            });
            this.minimised = true;
            this.options.organise();

            try {
                localStorage.setItem(this.lsID() + '-minimised', 1);
            } catch (e) { window.alert(e.message)};

            this.trigger('minimised');
        },

        adjust: function() {
            var newHeight = this.$el.innerHeight() - this.$('.js-chatheader').outerHeight() - this.$('.js-chatfooter input').outerHeight();
            //console.log("Height", newHeight, this.$el.innerHeight() ,this.$('.js-chatheader'), this.$('.js-chatheader').outerHeight() , this.$('.js-chatfooter input').outerHeight());
            this.$('.js-leftpanel, .js-roster').height(newHeight);
        },

        setSize: function() {
            var self = this;

            try {
                // Restore any saved height
                //
                // On mobile we maximise the chat window, as the whole resizing thing is too fiddly.
                var height = localStorage.getItem('chat-' + self.model.get('id') + '-height');
                var width = localStorage.getItem('chat-' + self.model.get('id') + '-width');
                // console.log("Narrow?", isNarrow(), $(window).innerWidth());
                if (isNarrow()) {
                    // Just maximise it.
                    width = $(window).innerWidth() - $('#chatMinimised').outerWidth() - 5;
                }

                // console.log("Short?", isShort(), $(window).innerHeight(), $('.navbar').outerHeight(), $('#chatMinimised').outerHeight());
                if (isShort()) {
                    // Maximise it, except for the navbar and a bit of padding.
                    height = $(window).innerHeight() - $('.navbar').outerHeight() - 5;
                }

                if (height && width) {
                    // Make sure it's not stupidly small
                    self.$el.height(Math.max(height, 50));
                    self.$el.width(Math.max(width, 50));
                }

                var lpwidth = localStorage.getItem('chat-' + self.model.get('id') + '-lp');
                lpwidth = self.$el.width() - 60 < lpwidth ? (self.$el.width() - 60) : lpwidth;

                if (lpwidth) {
                    self.$('.js-leftpanel').width(lpwidth);
                }
            } catch (e) {}
        },

        restore: function() {
            var self = this;
            self.minimised = false;

            // We fetch the messages when restoring - no need before then.
            self.messages.fetch().then(function() {
                self.setSize();
                self.options.organise();
                self.$el.css('visibility', 'visible');
                self.$el.show();
                self.adjust();
                self.scrollBottom();

                try {
                    localStorage.setItem(self.lsID() + '-minimised', 0);
                } catch (e) {
                }

                self.trigger('restored');
            });
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
            self.adjust();
            self.options.organise();

            // Save the new height to local storage so that we can restore it next time.
            try {
                localStorage.setItem(this.lsID() + '-height', self.$el.height());
                localStorage.setItem(this.lsID() + '-width', self.$el.width());
            } catch (e) {}
        },

        panelSize: function(event, el, opt) {
            var self = this;

            // Save the new left panel width to local storage so that we can restore it next time.
            try {
                localStorage.setItem(this.lsID() + '-lp', self.$('.js-leftpanel').width());
            } catch (e) {}
        },

        roster: function() {
            // We update our presence and get the roster for the chat regularly.
            var self = this;

            if (!self.removed) {
                $.ajax({
                    url: API + 'chat/rooms/' + self.model.get('id'),
                    type: 'POST',
                    data: {
                        lastmsgseen: self.model.get('lastmsgseen')
                    },
                    success: function(ret) {
                        self.$('.js-roster').empty();
                        _.each(ret.roster, function(rost) {
                            var mod = new Iznik.Model(rost);
                            var v = new Iznik.Views.Chat.RosterEntry({
                                model: mod
                            });
                            self.$('.js-roster').append(v.render().el);
                        });

                        self.model.set('unseen', ret.unseen);
                    }, complete: function() {
                        _.delay(_.bind(self.roster, self), 30000);
                    }
                });
            }
        },

        updateCount: function() {
            var self = this;
            var unseen = self.model.get('unseen');

            if (unseen > 0) {
                self.$('.js-count').html(unseen).show();
            } else {
                self.$('.js-count').html(unseen).hide();
            }

            self.trigger('countupdated', unseen);
        },

        render: function () {
            var self = this;

            self.$el.attr('id', 'chat-' + self.model.get('id'));
            self.$el.addClass('chat-' + self.model.get('name'));

            self.$el.css('visibility', 'hidden');

            self.$el.html(window.template(self.template)(self.model.toJSON2()));

            self.updateCount();

            // If the unread message count changes, we want to update it.
            self.listenTo(self.model, 'change:unseen', self.updateCount);

            // If the window size changes, we will need to adapt.
            $(window).resize(function() {
                self.setSize();
                self.adjust();
            });

            var narrow = isNarrow();
            var minimise = true;

            try {
                // On mobile we start them all minimised as there's not much room.
                //
                // Default to minimised, which is what we get if the key is missing and returns null.
                var lsval = localStorage.getItem(self.lsID() + '-minimised');
                lsval = lsval === null ? lsval : parseInt(lsval);
                console.log("Consider min", lsval, lsval === null, narrow);

                if (lsval === null || lsval || narrow) {
                    console.log("Minimise");
                    minimise = true;
                } else {
                    console.log("Restore");
                    minimise = false;
                }
            } catch (e) {}

            self.messages = new Iznik.Collections.Chat.Messages({
                roomid: self.model.get('id')
            });

            self.collectionView = new Backbone.CollectionView({
                el: self.$('.js-messages'),
                modelView: Iznik.Views.Chat.Message,
                collection: self.messages,
                chatView: self,
                modelViewOptions: {
                    chatView: self,
                    chatModel: self.model
                }
            });

            self.messages.on('add', function() {
                self.scrollBottom();
                self.$('.chat-when').hide();
                self.$('.chat-when:last').show();
            });

            self.collectionView.render();

            minimise ? self.minimise() : self.restore();

            self.$el.resizable({
                handleSelector: '.js-grip',
                resizeWidthFrom: 'left',
                resizeHeightFrom: 'top',
                onDragEnd: _.bind(self.drag, self)
            });

            self.$(".js-leftpanel").resizable({
                handleSelector: ".splitter",
                resizeHeight: false,
                onDragEnd: _.bind(self.panelSize, self)
            });
            
            self.trigger('rendered');

            // Get the roster to see who's there.
            self.roster();
        }
    });

    Iznik.Views.Chat.Message = Iznik.View.extend({
        template: 'chat_message',

        render: function() {
            if (this.model.get('id')) {
                this.model.set('lastmsgseen', this.options.chatModel.get('lastmsgseen'));
                this.$el.html(window.template(this.template)(this.model.toJSON2()));
                this.$('.timeago').timeago();
                this.$el.fadeIn('slow');
            }
        }
    });

    Iznik.Views.Chat.Roster = Iznik.View.extend({
        template: 'chat_roster',
    });

    Iznik.Views.Chat.RosterEntry = Iznik.View.extend({
        template: 'chat_rosterentry',
    });
});

