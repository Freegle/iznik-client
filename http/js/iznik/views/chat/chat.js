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

        waitError: function () {
            // This can validly happen when we switch pages, because we abort outstanding requests
            // and hence our long poll.  So before restarting, check that this view is still in the
            // DOM.
            console.log("Wait error", this);
            if (this.inDOM()) {
                // Probably a network glitch.  Retry later.
                this.wait();
            } else {
                this.destroyIt();
            }
        },

        wait: function() {
            // We have a long poll open to the server, which when it completes may prompt us to do some work on a
            // chat.  That way we get zippy messaging.
            //
            // TODO use a separate domain name to get round client-side limits on the max number of HTTP connections
            // to a single host.  We use a single connection rather than a per chat one for the same reason.
            var self = this;

            if (self.inDOM()) {
                // This view is still in the DOM.  If not, then we need to die.
                var me = Iznik.Session.get('me');
                var myid = me ? me.id : null;

                if (!myid) {
                    // Not logged in, try later;
                    _.delay(self.wait, 5000);
                } else {
                    var chathost = $('meta[name=iznikchat]').attr("content");

                    $.ajax({
                        url: window.location.protocol + '//' + chathost + '/subscribe/' + myid,
                        global: false, // don't trigger ajaxStart
                        success: function (ret) {
                            var waiting = false;
                            if (ret && ret.hasOwnProperty('text')) {
                                var data = ret.text;

                                if (data.hasOwnProperty('newroom')) {
                                    // We have been notified that we are now in a new chat.  Pick it up.
                                    Iznik.Session.chats.fetch({
                                        modtools: self.options.modtools
                                    }).then(function() {
                                        // Now that we have the chat, update our status in it.
                                        var chat = Iznik.Session.chats.get(data.newroom);

                                        // If the unread message count changes in the new chat, we want to update.
                                        self.listenTo(chat, 'change:unseen', self.updateCounts);
                                        self.updateCounts();

                                        if (chat) {
                                            var chatView = Iznik.activeChats.viewManager.findByModel(chat);
                                            chatView.updateRoster(chatView.statusWithOverride('Online'), chatView.noop);
                                        }

                                        Iznik.Session.chats.trigger('newroom', data.newroom);
                                    });
                                } else if (data.hasOwnProperty('roomid')) {
                                    // Activity on this room.  If the chat is active, then we refetch the mesages
                                    // within it so that they are displayed.  If it's not, then we don't want
                                    // to keep fetching messages - the notification count will get updated by
                                    // the roster poll.
                                    var chat = Iznik.Session.chats.get(data.roomid);

                                    // It's possible that we haven't yet fetched the model for this chat.
                                    if (chat) {
                                        // console.log("Notification", self, chat, data);
                                        var chatView = Iznik.activeChats.viewManager.findByModel(chat);

                                        if (!chatView.minimised) {
                                            waiting = true;
                                            chatView.messages.fetch().then(function () {
                                                // Wait for the next one.  Slight timing window here but the fallback
                                                // protects us from losing messages forever.
                                                self.wait();

                                                // Also fetch the chat, because the number of unread messages in it will
                                                // update counts in various places.
                                                chat.fetch();
                                            });
                                        }
                                    }
                                }
                            }

                            if (!waiting) {
                                self.wait();
                            }
                        }, error: _.bind(self.waitError, self)
                    });
                }
            } else {
                self.destroyIt();
            }
        },
        
        fallbackInterval: 300000,
        
        fallback: function() {
            // Although we should be notified of new chat messages via the wait() function, this isn't guaranteed.  So
            // we have a fallback poll to pick up any lost messages.
            var self = this;
            
            if (self.inDOM()) {
                Iznik.Session.chats.each(function(chat) {
                    chat.fetch();
                });
                
                _.delay(_.bind(self.fallback, self), self.fallbackInterval);
            } else {
                self.destroyIt();
            }
        },

        organise: function() {
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

            if (Iznik.activeChats) {
                Iznik.activeChats.viewManager.each(function(chat) {
                    if (chat.minimised) {
                        // Not much to do - either just count, or create if we're asked to.
                        minimised++;
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

                        // Make sure it's not stupidly tall or short.  We let the navbar show unless we're really short,
                        // which happens when on-screen keyboards open up.
                        // console.log("Consider height", css.height, windowInnerHeight, navbarOuterHeight, windowInnerHeight - navbarOuterHeight - 5);
                        height = Math.min(css.height, windowInnerHeight - (isVeryShort() ? 0 : navbarOuterHeight) - 5);
                        // console.log("Consider shortness", height, css.height, windowInnerHeight, isVeryShort() ? 0 : navbarOuterHeight, navbarOuterHeight);
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

                // console.log("Consider width", totalOuter, max);

                if (totalOuter > max) {
                    // The chat windows we have open are too wide.  Make them narrower.
                    var reduceby = Math.round((totalOuter - max) / totalMax + 0.5);
                    // console.log("Chats too wide", max, totalOuter, totalWidth, reduceby);
                    var width = (Math.round(totalWidth / totalMax + 0.5) - reduceby);
                    // console.log("New width", width);

                    Iznik.activeChats.viewManager.each(function(chat) {
                        if (!chat.minimised) {
                            if (chat.$el.css('width') != width) {
                                // console.log("Set new width ", chat.$el.css('width'), width);
                                chat.$el.css('width', width.toString() + 'px');
                            }
                        }
                    });
                }

                // console.log("Checked width", (new Date()).getMilliseconds() - start);
                // console.log("Got max height", (new Date()).getMilliseconds() - start);

                // Now consider changing the margins on the top to ensure the chat window is at the bottom of the
                // screen.
                Iznik.activeChats.viewManager.each(function(chat) {
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
            
            // The drop-down menu needs to be scrollable, and so we put a max-height on it.
            $('#notifchatdropdown').css('max-height', windowInnerHeight - navbarOuterHeight);

            // console.log("Organised", (new Date()).getMilliseconds() - start);
        },

        updateCounts: function() {
            var self = this;
            var unseen = 0;
            Iznik.Session.chats.each(function(chat) {
                var chatView = Iznik.activeChats.viewManager.findByModel(chat);
                unseen += chat.get('unseen');
            });

            // We'll adjust the count in the window title.
            var title = document.title;
            var match = /\(.*\) (.*)/.exec(title);
            title = match ? match[1] : title;

            if (unseen > 0) {
                $('#js-notifchat .js-totalcount').html(unseen).show();
                document.title = '(' + unseen + ') ' + title;
            } else {
                $('#js-notifchat .js-totalcount').html(unseen).hide();
                document.title = title;
            }

            this.showMin();
        },

        openChat: function(userid) {
            if (userid != Iznik.Session.get('me').id) {
                // We want to open a direct message conversation with this user.
                $.ajax({
                    type: 'PUT',
                    url: API + 'chat/rooms',
                    data: {
                        userid: userid
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            Iznik.Session.chats.fetch({
                                remove: false
                            }).then(function() {
                                // Defer to give the CollectionView time to respond.
                                _.defer(function() {
                                    var chatmodel = Iznik.Session.chats.get(ret.id);
                                    var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                                    chatView.restore();
                                })
                            });
                        }
                    }
                })
            }
        },

        showMin: function() {
            // No point showing the chat icon if we've nothing to show - will just encourage people to click
            // on something which won't do anything.
            if (Iznik.Session.chats.length > 0) {
                $('#js-notifchat').show();
            } else {
                $('#js-notifchat').hide();
            }

            $('#js-notifications').hide();
        },

        render: function() {
            var self = this;

            // We might already be rendered, as we're outside the body content that gets zapped when we move from
            // page to page.
            if ($('#chatHolder').length == 0) {
                self.$el.css('visibility', 'hidden');
                self.$el.html(window.template(self.template)());
                $("#bodyEnvelope").append(self.$el);

                Iznik.Session.chats = new Iznik.Collections.Chat.Rooms();
                Iznik.Session.chats.fetch({
                    data: {
                        modtools: self.options.modtools
                    }
                }).then(function () {
                    Iznik.Session.chats.each(function (chat) {
                        // If the unread message count changes, we want to update it.
                        self.listenTo(chat, 'change:unseen', self.updateCounts);
                    });

                    Iznik.activeChats = new Backbone.CollectionView({
                        el: self.$('.js-chats'),
                        modelView: Iznik.Views.Chat.Active,
                        collection: Iznik.Session.chats,
                        modelViewOptions: {
                            organise: _.bind(self.organise, self),
                            updateCounts: _.bind(self.updateCounts, self),
                            modtools: self.options.modtools
                        }
                    });

                    Iznik.activeChats.render();

                    // Defer as container not yet in DOM.
                    _.defer(function() {
                        Iznik.minimisedChats = new Backbone.CollectionView({
                            el: $('#notifchatdropdown'),
                            modelView: Iznik.Views.Chat.Minimised,
                            collection: Iznik.Session.chats,
                            modelViewOptions: {
                                organise: _.bind(self.organise, self),
                                updateCounts: _.bind(self.updateCounts, self),
                                modtools: self.options.modtools
                            }
                        });

                        Iznik.minimisedChats.render();
                    })

                    self.organise();
                    self.showMin();
                });

                // Now ensure we are told about new messages.
                self.wait();
                _.delay(_.bind(self.fallback, self), self.fallbackInterval);
            }
        }
    });

    Iznik.Views.Chat.Minimised = Iznik.View.extend({
        template: 'chat_minimised',

        tagName: 'li',

        events: {
            'click': 'click'
        },

        click: function() {
            // The maximised chat view is listening on this.
            this.model.trigger('restore', this.model.get('id'));
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

    Iznik.Views.Chat.Active = Iznik.View.extend({
        template: 'chat_active',

        tagName: 'li',

        className: 'chat-window nopad nomarginleft nomarginbot nomarginright col-xs-4 col-md-3 col-lg-2',

        events: {
            'click .js-remove, touchstart .js-remove': 'removeIt',
            'click .js-minimise, touchstart .js-minimise': 'minimise',
            'focus .js-message': 'messageFocus',
            'click .js-promise': 'promise',
            'keyup .js-message': 'keyUp',
            'change .js-status': 'status'
        },

        removed: false,

        rosterUpdatedAt: 0,

        keyUp: function(e) {
            var self = this;
            if (e.which === 13) {
                self.$('.js-message').prop('disabled', true);
                var message = this.$('.js-message').val();
                if (message.length > 0) {
                    self.listenToOnce(this.model, 'sent', function(id) {
                        self.model.set('lastmsgseen', id);
                        self.model.set('unseen', 0);
                        self.options.updateCounts();

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

        zapViews: function() {
            Iznik.Session.chats.remove({
                id: this.model.get('id')
            });
        },

        removeIt: function() {
            // This will close the chat, which means it won't show in our list until we recreate it.  The messages
            // will be preserved.
            this.removed = true;
            this.$el.hide();
            this.updateRoster('Closed', _.bind(this.zapViews, this));
        },

        focus: function() {
            this.$('.js-message').click();
        },

        noop: function() {

        },

        promise: function() {
            // Promise a message to someone.
            var self = this;

            // Get our offers.
            self.offers = new Iznik.Collections.Message(null, {
                collection: 'Approved'
            });

            self.offers.fetch({
                data: {
                    fromuser: Iznik.Session.get('me').id,
                    types: ['Offer'],
                    limit: 100
                }
            }).then(function() {
                if (self.offers.length > 0) {
                    // The message we want to suggest as the one to promise is any last message mentioned in this chat.
                    var msgid = _.last(self.model.get('refmsgids'));

                    var msg = null;
                    self.offers.each(function(offer) {
                        if (offer.get('id') == msgid) {
                            msg = offer;
                        }
                    });

                    var v = new Iznik.Views.User.Message.Promise({
                        model: new Iznik.Model({
                            message: msg ? msg.toJSON2() : null,
                            user: self.model.get('user1').id != Iznik.Session.get('me').id ?
                                self.model.get('user1'): self.model.get('user2')
                        }),
                        offers: self.offers
                    });

                    self.listenToOnce(v, 'promised', function() {
                        msg.fetch();
                    });

                    v.render();
                }
            });
        },

        messageFocus: function() {
            var self = this;

            // We've seen all the messages.
            if (this.messages.length > 0) {
                this.model.set('lastmsgseen', this.messages.at(this.messages.length - 1).get('id'));
            }
            this.model.set('unseen', 0);

            // Tell the server now, in case they navigate away before the next roster timer.
            self.updateRoster(self.statusWithOverride('Online'), self.noop);

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
            this.options.organise();
            this.options.updateCounts();

            self.updateRoster('Away', self.noop);

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

            if (self.model.get('group')) {
                // Group chats have a roster.
                var lpwidth = self.$('.js-leftpanel').width();
                lpwidth = self.$el.width() - 60 < lpwidth ? (self.$el.width() - 60) : lpwidth;
                lpwidth = Math.max(self.$el.width() - 250, lpwidth);
                self.$('.js-leftpanel').width(lpwidth);
            } else {
                // Conversations don't.
                self.$('.js-leftpanel').width('100%');
            }
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
                    // console.log("Set size", width, height);
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
            self.options.organise();
            this.options.updateCounts();

            _.defer(function() {
                self.$el.css('visibility', 'visible');
                self.$el.show();
                self.adjust();
            });

            self.updateRoster(self.statusWithOverride('Online'), self.noop);

            try {
                localStorage.setItem(self.lsID() + '-minimised', 0);
            } catch (e) {
            }

            // We fetch the messages when restoring - no need before then.
            self.messages.fetch().then(function() {
                self.scrollBottom();

                // We've just opened this chat - so we have had a decent chance to see any unread messages.
                self.messageFocus();

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

            this.options.organise();
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
                this.options.organise();
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

            this.updateRoster(status, this.noop);
        },

        updateRoster: function(status, callback) {
            var self = this;

            // We make sure we don't update the server too often unless the status changes, whatever the user
            // is doing with this chat.  This helps reduce server load for large numbers of clients.
            var now = (new Date()).getTime();
            // console.log("Consider roster update", status, self.rosterUpdatedStatus, now, self.rosterUpdatedAt, now - self.rosterUpdatedAt);

            if (status != self.rosterUpdatedStatus || now - self.rosterUpdatedAt > 25000) {
                // console.log("Issue roster update");
                $.ajax({
                    url: API + 'chat/rooms/' + self.model.get('id'),
                    type: 'POST',
                    data: {
                        lastmsgseen: self.model.get('lastmsgseen'),
                        status: status
                    }, success: function(ret) {
                        if (ret.ret === 0) {
                            self.rosterUpdatedAt = (new Date()).getTime();
                            self.rosterUpdatedStatus = status;
                            self.lastRoster = ret.roster;
                        }

                        callback(ret);
                    }
                });
            } else {
                // console.log("Suppress update", self.lastRoster);
                callback({
                    ret: 0,
                    status: 'Update suppressed',
                    roster: self.lastRoster
                });
            }
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

        openChat: function(chatid) {
            Iznik.Session.chats.fetch().then(function() {
                var chatmodel = Iznik.Session.chats.get(chatid);
                var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                chatView.restore();
            });
        },

        rosterUpdated: function(ret) {
            var self = this;

            if (ret.ret === 0) {
                self.$('.js-roster').empty();
                _.each(ret.roster, function(rost) {
                    var mod = new Iznik.Model(rost);
                    var v = new Iznik.Views.Chat.RosterEntry({
                        model: mod,
                        modtools: self.options.modtools
                    });
                    self.listenTo(v, 'openchat', self.openChat);
                    self.$('.js-roster').append(v.render().el);
                });

                self.model.set('unseen', ret.unseen);
            }

            _.delay(_.bind(self.roster, self), 30000);
        },

        roster: function() {
            // We update our presence and get the roster for the chat regularly if the chat is open.  If it's
            // minimised, we don't - the server will time us out as away.  We'll still; pick up any new messages on
            // minimised chats via the long poll, and the fallback.
            var self = this;

            if (!self.removed && !self.minimised) {
                self.updateRoster(self.statusWithOverride('Online'),
                    _.bind(self.rosterUpdated, self));
            }
        },

        updateCount: function() {
            var self = this;
            var unseen = self.model.get('unseen');
            // console.log("Update count", unseen);

            if (unseen > 0) {
                self.$('.js-count').html(unseen).show();

                if (self.messages) {
                    self.messages.fetch();
                }
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

            if (!self.options.modtools) {
                self.$('.js-privacy').hide();
            } else {
                self.$('.js-promise').hide();
            }

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
                self.options.organise();
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

            self.messageViews = new Backbone.CollectionView({
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

            self.messageViews.render();

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

            // The minimised chat can signal to us that we should restore.
            self.listenTo(self.model, 'restore', self.restore);

            self.trigger('rendered');

            // Get the roster to see who's there.
            self.roster();
        }
    });

    Iznik.Views.Chat.Message = Iznik.View.extend({
        render: function() {
            if (this.model.get('id')) {
                // Insert some wbrs to allow us to word break long words (e.g. URLs).
                var message = this.model.get('message');
                if (message) {
                    message = this.model.set('message', wbr(message, 20));
                }

                this.model.set('group', this.options.chatModel.get('group'));
                this.model.set('myid', Iznik.Session.get('me').id);

                this.model.set('lastmsgseen', this.options.chatModel.get('lastmsgseen'));

                // This could be a simple chat message, or something more complex.
                var tpl;

                switch (this.model.get('type')) {
                    case 'Interested': tpl = 'chat_interested'; break;
                    case 'Promised': tpl = 'chat_promised'; break;
                    case 'Reneged': tpl = 'chat_reneged'; break;
                    default: tpl = 'chat_message'; break;
                }

                this.$el.html(window.template(tpl)(this.model.toJSON2()));

                this.$('.timeago').timeago();
                this.$('.timeago').show();
                this.$el.fadeIn('slow');
            }
        }
    });

    Iznik.Views.Chat.Roster = Iznik.View.extend({
        template: 'chat_roster'
    });

    Iznik.Views.Chat.RosterEntry = Iznik.View.extend({
        template: 'chat_rosterentry',

        events: {
            'click .js-click': 'dm'
        },

        dm: function() {
            var self = this;

            if (self.model.get('id') != Iznik.Session.get('me').id) {
                // We want to open a direct message conversation with this user.
                $.ajax({
                    type: 'PUT',
                    url: API + 'chat/rooms',
                    data: {
                        userid: self.model.get('userid')
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            self.trigger('openchat', ret.id);
                        }
                    }
                })
            }
        }
    });

    // This is a singleton view.
    var instance;

    return function(options) {
        if (!instance) {
            instance = new Iznik.Views.Chat.Holder(options);
        }

        return instance;
    }
});

