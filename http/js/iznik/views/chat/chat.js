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
                        var waiting = false;
                        if (ret.hasOwnProperty('text')) {
                            var data = ret.text;

                            if (data.hasOwnProperty('roomid')) {
                                // Activity on this room.  Refetch the mesages within it.
                                // console.log("Refetch chat", data.roomid, self);
                                var chat = self.chats.get(data.roomid);
                                var chatView = self.collectionView.viewManager.findByModel(chat);
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
            this.$el.hide();
            delete this.chatViews[chat.model.get('id')];
        },

        organise: function(changeminimised) {
            // This organises our chat windows so that:
            // - they're at the bottom, padded at the top to ensure that
            // - they're not wider or taller than the space we have.
            //
            // The code is a bit complex
            // - partly because the algorithm is a bit complicated
            // - partly because for performance reasons we need to avoid using methods like width(), which are
            //   expensive, and use the CSS properties instead - which aren't, but which are returned with a
            //   px we need to trim.
            //
            // This approach speeds up this function by at least a factor of ten.
            var self = this;
            var start = (new Date()).getMilliseconds();
            var minimised = 0;
            var totalOuter = 0;
            var totalWidth = 0;
            var totalMax = 0;
            var maxHeight = 0;
            var minHeight = 1000;

            var windowInnerHeight = $(window).innerHeight();
            var navbarOuterHeight = $('.navbar').outerHeight();

            if (changeminimised) {
                $('#js-notifchat ul').empty();
            }

            if (self.collectionView) {
                self.collectionView.viewManager.each(function(chat) {
                    if (chat.minimised) {
                        // Not much to do - either just count, or create if we're asked to.
                        minimised++;

                        if (changeminimised) {
                            var v = new Iznik.Views.Chat.Minimised({
                                model: chat.model,
                                chat: chat
                            });
                            $('#js-notifchat ul').append(v.render().el);
                        }
                    } else {
                        // We can get the properties we're interested in with a single call, which is quicker.  This also
                        // allows us to remove the px crud.
                        var cssorig = chat.$el.css(['height', 'width', 'margin-left', 'margin-right', 'margin-top']);
                        var css = [];

                        // Remove the px and make sure they're ints.
                        _.each(cssorig, function(val, prop) {
                            css[prop] = parseInt(val.replace('px', ''));
                        });

                        // We use this later to see if we need to shrink.
                        totalOuter += css.width + css['margin-left'] + css['margin-right'];
                        totalWidth += css.width;
                        totalMax++;

                        // Make sure it's not stupidly tall or short.
                        // console.log("Consider height", css.height, windowInnerHeight, navbarOuterHeight, windowInnerHeight - navbarOuterHeight - 5);
                        height = Math.min(css.height, windowInnerHeight - navbarOuterHeight - 5);
                        height = Math.max(height, 100);
                        maxHeight = Math.max(height, maxHeight);
                        // console.log("Height", height, css.height, windowInnerHeight, navbarOuterHeight);

                        if (css.height != height) {
                            css.height = height;
                            chat.$el.css('height', height.toString() + 'px');
                        }
                    }
                });

                // console.log("Checked height", (new Date()).getMilliseconds() - start);

                var max = window.innerWidth;

                console.log("Consider width", totalOuter, max);

                if (totalOuter > max) {
                    // The chat windows we have open are too wide.  Make them narrower.
                    var reduceby = Math.round((totalOuter - max) / totalMax + 0.5);
                    console.log("Chats too wide", max, totalOuter, totalWidth, reduceby);
                    var width = (Math.round(totalWidth / totalMax + 0.5) - reduceby);
                    console.log("New width", width);

                    self.collectionView.viewManager.each(function(chat) {
                        if (!chat.minimised) {
                            if (chat.$el.css('width') != width) {
                                console.log("Set new width ", chat.$el.css('width'), width);
                                chat.$el.css('width', width.toString() + 'px');
                            }
                        }
                    });
                }

                // console.log("Checked width", (new Date()).getMilliseconds() - start);
                // console.log("Got max height", (new Date()).getMilliseconds() - start);

                // Now consider changing the margins on the top to ensure the chat window is at the bottom of the
                // screen.
                self.collectionView.viewManager.each(function(chat) {
                    if (!chat.minimised) {
                        var height = parseInt(chat.$el.css('height').replace('px', ''));
                        var newmargin = (maxHeight - height).toString() + 'px';
                        // console.log("Checked margin", (new Date()).getMilliseconds() - start);
                        // console.log("Consider new margin", maxHeight, height, chat.$el.css('height'), chat.$el.css('margin-top'), newmargin);

                        if (chat.$el.css('margin-top') != newmargin) {
                            chat.$el.css('margin-top', newmargin);
                        }
                    }
                });
            }

            // console.log("Organised", (new Date()).getMilliseconds() - start);
        },

        updateCounts: function() {
            var self = this;
            var unseen = 0;
            self.chats.each(function(chat) {
                var chatView = self.collectionView.viewManager.findByModel(chat);
                if (chatView && chatView.minimised) {
                    unseen += chat.get('unseen');
                }
            });

            if (unseen > 0) {
                $('#js-notifchat .js-totalcount').html(unseen).show();
            } else {
                $('#js-notifchat .js-totalcount').html(unseen).hide();
            }
        },

        render: function() {
            var self = this;

            self.$el.css('visibility', 'hidden');
            self.$el.html(window.template(self.template)());
            $("#bodyEnvelope").append(self.$el);

            self.chats = new Iznik.Collections.Chat.Rooms();
            self.chats.fetch({
                data: {
                    modtools: self.options.modtools
                }
            }).then(function() {
                if (self.chats.length > 0) {
                    self.chats.each(function(chat) {
                        // If the unread message count changes, we want to update it.
                        self.listenTo(chat, 'change:unseen', self.updateCounts);
                    });

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-chats'),
                        modelView: Iznik.Views.Chat.Window,
                        collection: self.chats,
                        modelViewOptions: {
                            'organise': _.bind(self.organise, self),
                            'updateCounts':  _.bind(self.updateCounts, self)
                        }
                    });

                    self.collectionView.render();

                    self.organise(true);
                    self.$el.css('visibility', 'visible');
                } else {
                    $('#js-notifchat').css('visibility', 'hidden');
                }
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

        className: 'chat-window nopad nomarginleft nomarginbot nomarginright col-xs-4 col-md-3 col-lg-2',

        events: {
            'click .js-close, touchstart .js-close': 'remove',
            'click .js-minimise, touchstart .js-minimise': 'minimise',
            'focus .js-message': 'messageFocus',
            'keyup .js-message': 'keyUp',
            'change .js-status': 'status'
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
                        self.model.set('unseen', 0);

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

            // We've seen all the messages.
            this.model.set('lastmsgseen', this.messages.at(this.messages.length - 1).get('id'));
            this.model.set('unseen', 0);

            // Tell the server now, in case they navigate away before the next roster timer.
            self.updateRoster(self.statusWithOverride('Online'));

            // New messages are in bold - keep them so for a few seconds, to make it easy to see new stuff,
            // then revert.
            _.delay(function() {
                self.$('.chat-message-unseen').removeClass('chat-message-unseen');
            }, 5000)
            this.updateCount();
        },

        minimise: function() {
            var self = this;
            _.defer(function() {
                self.$el.hide();
            });
            this.minimised = true;
            this.options.organise(true);
            this.options.updateCounts();

            self.updateRoster('Away');

            try {
                localStorage.setItem(this.lsID() + '-minimised', 1);
            } catch (e) { window.alert(e.message)};

            this.trigger('minimised');
        },

        adjust: function() {
            var self = this;
            var newHeight = this.$el.innerHeight() - this.$('.js-chatheader').outerHeight() - this.$('.js-chatfooter input').outerHeight();
            // console.log("Height", newHeight, this.$el.innerHeight() ,this.$('.js-chatheader'), this.$('.js-chatheader').outerHeight() , this.$('.js-chatfooter input').outerHeight());
            this.$('.js-leftpanel, .js-roster').height(newHeight);

            var lpwidth = self.$('.js-leftpanel').width();
            lpwidth = self.$el.width() - 60 < lpwidth ? (self.$el.width() - 60) : lpwidth;
            lpwidth = Math.max(self.$el.width() - 250, lpwidth);
            self.$('.js-leftpanel').width(lpwidth);
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
                    width = $(window).innerWidth();
                }

                // console.log("Short?", isShort(), $(window).innerHeight(), $('.navbar').outerHeight(), $('#js-notifchat').outerHeight());
                if (isShort()) {
                    // Maximise it.
                    height = $(window).innerHeight();
                }

                if (height && width) {
                    console.log("Set size", width, height);
                    self.$el.height(height);
                    self.$el.width(width);
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

            // Restore the window first, so it feels zippier.
            self.setSize();
            self.options.organise(true);
            this.options.updateCounts();

            _.defer(function() {
                self.$el.css('visibility', 'visible');
                self.$el.show();
                self.adjust();
            });

            self.updateRoster(self.statusWithOverride('Online'));

            try {
                localStorage.setItem(self.lsID() + '-minimised', 0);
            } catch (e) {
            }

            // We fetch the messages when restoring - no need before then.
            console.log("Fetch messages");
            self.messages.fetch().then(function() {
                console.log("Fetched");
                self.scrollBottom();
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

        dragend: function(event, el, opt) {
            var self = this;

            this.options.organise(false);
            self.trigger('resized');
            self.adjust();
            self.scrollBottom();

            // Save the new height to local storage so that we can restore it next time.
            try {
                localStorage.setItem(this.lsID() + '-height', self.$el.height());
                localStorage.setItem(this.lsID() + '-width', self.$el.width());
            } catch (e) {}
        },

        drag: function(event, el, opt) {
            var now = (new Date()).getMilliseconds();

            // We don't want to allow the resize 

            if (now - this.lastdrag > 20) {
                // We will need to remargin any other chats.  Don't do this too often as it makes dragging laggy.
                this.options.organise(false);
            }

            this.lastdrag = (new Date()).getMilliseconds();

        },

        panelSize: function(event, el, opt) {
            var self = this;

            // Save the new left panel width to local storage so that we can restore it next time.
            try {
                localStorage.setItem(this.lsID() + '-lp', self.$('.js-leftpanel').width());
            } catch (e) {}

            self.adjust();
            self.scrollBottom();
        },

        status: function() {
            // We can override appearing online to show something else.
            var status = this.$('.js-status').val();
            try {
                localStorage.setItem('mystatus', status);
            } catch (e) {}

            this.updateRoster(status);
        },

        updateRoster: function(status) {
            var self = this;
            $.ajax({
                url: API + 'chat/rooms/' + self.model.get('id'),
                type: 'POST',
                data: {
                    lastmsgseen: self.model.get('lastmsgseen'),
                    status: status
                },
            });
        },

        statusWithOverride: function(status) {
            if (status == 'Online') {
                // We are online, but may have overridden this to appear something else.
                try {
                    var savestatus = localStorage.getItem('mystatus');
                    status = savestatus ? savestatus : status;
                } catch (e) {}
            }

            return(status);
        },

        roster: function() {
            // We update our presence and get the roster for the chat regularly.
            var self = this;

            if (!self.removed) {
                $.ajax({
                    url: API + 'chat/rooms/' + self.model.get('id'),
                    type: 'POST',
                    data: {
                        lastmsgseen: self.model.get('lastmsgseen'),
                        status: self.statusWithOverride(self.minimised ? 'Away' : 'Online')
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
            // console.log("Update count", unseen);

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

            try {
                var status = localStorage.getItem('mystatus');

                if (status) {
                    self.$('.js-status').val(status);
                }
            } catch (e) {}

            self.updateCount();

            // If the unread message count changes, we want to update it.
            self.listenTo(self.model, 'change:unseen', self.updateCount);

            // If the window size changes, we will need to adapt.
            $(window).resize(function() {
                self.setSize();
                self.adjust();
                self.options.organise(false);
                self.scrollBottom();
            });

            var narrow = isNarrow();
            var minimise = true;

            try {
                // On mobile we start them all minimised as there's not much room.
                //
                // Default to minimised, which is what we get if the key is missing and returns null.
                var lsval = localStorage.getItem(self.lsID() + '-minimised');
                lsval = lsval === null ? lsval : parseInt(lsval);

                if (lsval === null || lsval || narrow) {
                    minimise = true;
                } else {
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
            });

            self.collectionView.render();

            self.$el.resizable({
                handleSelector: '#chat-' + self.model.get('id') + ' .js-grip',
                resizeWidthFrom: 'left',
                resizeHeightFrom: 'top',
                onDrag: _.bind(self.drag, self),
                onDragEnd: _.bind(self.dragend, self)
            });

            self.$(".js-leftpanel").resizable({
                handleSelector: ".splitter",
                resizeHeight: false,
                onDragEnd: _.bind(self.panelSize, self)
            });

            minimise ? self.minimise() : self.restore();

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
                this.$('.timeago').show();
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

