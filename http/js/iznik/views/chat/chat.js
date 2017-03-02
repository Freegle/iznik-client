define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'autosize',
    'moment',
    'iznik/models/chat/chat',
    'iznik/models/message',
    'iznik/models/user/user',
    'jquery-resizable',
    'jquery-visibility',
    'fileinput'
], function ($, _, Backbone, Iznik, autosize, moment) {
    // This is a singleton view.
    var instance;

    Iznik.Views.Chat.Holder = Iznik.View.extend({
        template: 'chat_holder',

        id: "chatHolder",

        organise: function () {
            // This organises our chat windows so that:
            // - they're at the bottom, padded at the top to ensure that
            // - they're not wider or taller than the space we have.
            // - they're not too narrow.
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

            if (Iznik.openChats) {
                Iznik.openChats.viewManager.each(function (chat) {
                    if (chat.minimised) {
                        // Not much to do - either just count, or create if we're asked to.
                        minimised++;
                    } else {
                        // We can get the properties we're interested in with a single call, which is quicker.  This also
                        // allows us to remove the px crud.
                        var cssorig = chat.$el.css(['height', 'width', 'margin-left', 'margin-right', 'margin-top']);
                        var css = [];

                        // Remove the px and make sure they're ints.
                        _.each(cssorig, function (val, prop) {
                            css[prop] = parseInt(val.replace('px', ''));
                        });

                        // Matches style.
                        css.width = css.width ? css.width : 300;

                        // We use this later to see if we need to shrink.
                        totalOuter += css.width + css['margin-left'] + css['margin-right'];
                        //console.log("Chat width", chat.$el.prop('id'), css.width, css['margin-left'], css['margin-right']);
                        totalWidth += css.width;
                        totalMax++;

                        // Make sure it's not stupidly tall or short.  We let the navbar show unless we're really short,
                        // which happens when on-screen keyboards open up.
                        // console.log("Consider height", css.height, windowInnerHeight, navbarOuterHeight, windowInnerHeight - navbarOuterHeight - 5);
                        height = Math.min(css.height, windowInnerHeight - (isVeryShort() ? 0 : navbarOuterHeight) - 10);
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

                var max = window.innerWidth - (isSM() ? 0 : 100);

                //console.log("Consider width", totalOuter, max);

                if (totalOuter > max) {
                    // The chat windows we have open are too wide.  Make them narrower.
                    var reduceby = Math.round((totalOuter - max) / totalMax + 0.5);
                    // console.log("Chats too wide", max, totalOuter, totalWidth, reduceby);
                    var width = (Math.floor(totalWidth / totalMax + 0.5) - reduceby);
                    //console.log("New width", width);

                    if (width < 300) {
                        // This would be stupidly narrow for a chat.  Close the oldest one.
                        var toclose = null;
                        var oldest = null;
                        var count = 0;
                        Iznik.openChats.viewManager.each(function (chat) {
                            if (!chat.minimised) {
                                count++;
                                if (!oldest || chat.restoredAt < oldest) {
                                    toclose = chat;
                                    oldest = chat.restoredAt;
                                }
                            }
                        });

                        //console.log("COnsider close", toclose);
                        if (toclose && count > 1) {
                            toclose.minimise();

                            // Organise again now that's gone.
                            _.defer(_.bind(self.organise, self));
                        }
                    } else {
                        Iznik.openChats.viewManager.each(function (chat) {
                            if (!chat.minimised) {
                                if (chat.$el.css('width') != width) {
                                    // console.log("Set new width ", chat.$el.css('width'), width);
                                    chat.$el.css('width', width.toString() + 'px');
                                }
                            }
                        });
                    }
                }

                // console.log("Checked width", (new Date()).getMilliseconds() - start);
                // console.log("Got max height", (new Date()).getMilliseconds() - start);

                // Now consider changing the margins on the top to ensure the chat window is at the bottom of the
                // screen.
                Iznik.openChats.viewManager.each(function (chat) {
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
            } else {
                console.log("No chats to organise");
            }

            // console.log("Organised", (new Date()).getMilliseconds() - start);
        },

        updateCounts: function () {
            var self = this;
            var unseen = 0;
            console.log("update Chat counts");

            Iznik.Session.chats.each(function (chat) {
                unseen += chat.get('unseen');
            });
            /* CC Iznik.Session.chats.each(function (chat) {
                var chatView = Iznik.activeChats.viewManager.findByModel(chat);
                unseen += chat.get('unseen');
                console.log("Unseen", unseen, chat);
            });*/

            // We'll adjust the count in the window title.
            var title = document.title;
            var match = /\(.*\) (.*)/.exec(title);
            title = match ? match[1] : title;

            // This if test improves browser performance by avoiding unnecessary show/hides.
            $('.js-chattotalcount').each(function() {
                if ($(this).html() != unseen) {
                    if (unseen > 0) {
                        $(this).html(unseen).show();
                        document.title = '(' + unseen + ') ' + title;
                    } else {
                        $(this).empty().hide();
                        document.title = title;
                    }
                }
            });

            self.showMin();

            if (mobilePush) {
                mobilePush.setApplicationIconBadgeNumber(function () { }, function () { }, unseen);
                /*var msg = new Date();
                msg = msg.toLocaleTimeString() + " C " + unseen + "<br/>";
                badgeconsole += msg;
                $('#badgeconsole').html(badgeconsole);*/
            }
        },

        updateCountTimer: function() {
            // Fallback to ensure the count gets updated.
            var self = this;
            self.updateCounts();
            _.delay(_.bind(self.updateCountTimer, self), 30000);
        },

        openChatToMods: function (groupid) {
            var self = this;

            $.ajax({
                type: 'PUT',
                url: API + 'chat/rooms',
                data: {
                    chattype: 'User2Mod',
                    groupid: groupid
                }, success: function (ret) {
                    if (ret.ret == 0) {
                        self.fetchAndRestore(ret.id);
                    }
                }
            });
        },

        openChatToUser: function (userid) {
            var self = this;

            var v = new Iznik.Views.PleaseWait({
                label: 'chat openChat'
            });
            v.render();

            if (userid != Iznik.Session.get('me').id) {
                // We want to open a direct message conversation with this user.  See if we already know which
                // chat this is because we've spoken to them before.
                var found = false;

                Iznik.Session.chats.each(function (chat) {
                    var user1 = chat.get('user1');
                    var user2 = chat.get('user2');

                    if (user1 && user1.id === userid || user2 && user2.id === userid) {
                        // We do.  Open it.
                        var chatView = Iznik.openChats.viewManager.findByModel(chat);
                        v.close();
                        chatView.restore();
                        found = true;
                    }
                });

                if (!found) {
                    $.ajax({
                        type: 'PUT',
                        url: API + 'chat/rooms',
                        data: {
                            userid: userid
                        }, success: function (ret) {
                            if (ret.ret == 0) {
                                self.fetchAndRestore(ret.id);
                            }

                            v.close();
                        }
                    });
                }
            }
        },

        showMin: function () {
            // No point showing the chat icon if we've nothing to show - will just encourage people to click
            // on something which won't do anything.
            if (Iznik.Session.chats && Iznik.Session.chats.length > 0) {
                $('#js-notifchat').show();
            } else {
                $('#js-notifchat').hide();
            }
        },

        waitForView: function(chatid) {
            var self = this;
            var retry = true;
            var chat = Iznik.Session.chats.get(chatid);

            if (chat) {
                var chatView = Iznik.openChats.viewManager.findByModel(chat);
                // console.log("Looked for view", chatid, chatView, chat);

                if (chatView) {
                    retry = false;
                    chatView.restore();
                    chatView.focus();
                }
            }

            if (retry) {
                window.setTimeout(_.bind(self.waitForView, self), 200, chat.get('id'));
            }
        },

        fetchAndRestore: function(id) {
            // Fetch the chat, wait for the corresponding view to be present in the view manager (there might be a lag)
            // and then restore it.
            var self = this;

            var chat = new Iznik.Models.Chat.Room({
                id: id
            });

            chat.fetch().then(function() {
                Iznik.Session.chats.add(chat, {
                    merge: true
                });

                self.waitForView(id);
            });
        },

        fetchedChats: function () {
            var self = this;

            // This can be called multiple times.
            if (!self.chatsFetched) {
                self.chatsFetched = true;
                Iznik.openChats = new Backbone.CollectionView({
                    el: self.$('.js-chats'),
                    modelView: Iznik.Views.Chat.Active,
                    collection: Iznik.Session.chats,
                    reuseModelViews: false, // Solves some weird problems with messages being repeated
                    modelViewOptions: {
                        organise: _.bind(self.organise, self),
                        updateCounts: _.bind(self.updateCounts, self),
                        modtools: self.options.modtools
                    }
                });

                Iznik.openChats.render();

                self.waitDOM(self, function () {
                    self.organise();
                    Iznik.Session.trigger('chatsfetched');
                });

                self.organise();
                self.updateCounts();
            }
        },

        render: function () {
            var self = this;
            var p;

            // We might already be rendered, as we're outside the body content that gets zapped when we move from
            // page to page.
            console.log("Page render - got holder?", $('#chatHolder').length);
            if ($('#chatHolder').length == 0) {
                self.$el.css('visibility', 'hidden');

                p = Iznik.View.prototype.render.call(self).then(function (self) {
                    $("#bodyEnvelope").append(self.$el);

                    Iznik.Session.chats.on('add', function (chat) {
                        // We have a new chat.  If the unread message count changes, we want to update it.
                        self.listenTo(chat, 'change:unseen', self.updateCounts);
                    });

                    var cb = _.bind(self.fetchedChats, self);

                    Iznik.Session.chats.fetch({
                        cached: cb
                    }).then(cb);
                });

                $(document).on('hide', function () {
                    self.tabActive = false;
                });

                $(document).on('show', function () {
                    self.tabActive = true;
                });
            } else {
                self.updateCounts();
                p = resolvedPromise(self);
            }

            if (!self.windowResizeListening) {
                // If the window size changes, we will need to adapt.
                self.windowResizeListening = true;
                $(window).resize(function () {
                    self.organise();
                });
            }

            if (!self.countTimerRunning) {
                self.countTimerRunning = true;
                _.delay(_.bind(self.updateCountTimer, self), 30000);
            }

            return (p);
        }
    });

    Iznik.Views.Chat.Active = Iznik.View.extend({
        template: 'chat_active',

        tagName: 'li',

        className: 'chat-window nopad nomarginleft nomarginbot nomarginright col-xs-4 col-md-3 col-lg-2',

        events: {
            'click .js-remove, touchstart .js-remove': 'removeIt',
            'click .js-minimise, touchstart .js-minimise': 'minimise',
            'click .js-report, touchstart .js-report': 'report',
            'click .js-enter': 'enter',
            'focus .js-message': 'messageFocus',
            'click .js-promise': 'promise',
            'click .js-info': 'info',
            'click .js-photo': 'photo',
            'click .js-send': 'send',
            'click .js-fullscreen': 'fullscreen',
            'keyup .js-message': 'keyUp',
            'change .js-status': 'status'
        },

        removed: false,

        minimised: true,

        enter: function(e) {
            var v = new Iznik.Views.Chat.Enter();
            v.render();
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        },

        keyUp: function (e) {
            var self = this;
            var enterSend = null;
            try {
                enterSend = Storage.get('chatentersend');
                if (enterSend !== null) {
                    enterSend = parseInt(enterSend);
                }
            } catch (e) {};

            if (e.which === 13) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                if (e.altKey || e.shiftKey || enterSend === 0) {
                    // They've used the alt/shift trick, or we know they don't want to send.
                    self.$('.js-message').val(self.$('.js-message').val() + "\n");
                } else  {
                    if (enterSend !== 0 && enterSend !== 1) {
                        // We don't know what they want it to do.  Ask them.
                        var v = new Iznik.Views.Chat.Enter();
                        self.listenToOnce(v, 'modalClosed', function() {
                            // Now we should know.
                            try {
                                enterSend = parseInt(Storage.get('chatentersend'));
                            } catch (e) {};

                            if (enterSend) {
                                self.send();
                            } else {
                                self.$('.js-message').val(self.$('.js-message').val() + "\n");
                            }
                        });
                        v.render();
                    } else {
                        self.send();
                    }
                }
            }  
        },

        getLatestMessages: function() {
            var self = this;

            if (!self.fetching) {
                self.fetching = true;
                self.fetchAgain = false;

                // Get the full set of messages back.  This will replace any temporary
                // messages added, and also ensure we don't miss any that arrived while we
                // were sending ours.
                self.messages.fetch({
                    remove: true
                }).then(function () {
                    self.fetching = false;
                    if (self.fetchAgain) {
                        // console.log("Fetch messages again");
                        self.getLatestMessages();
                    } else {
                        // console.log("Fetched and no more");
                        self.options.updateCounts();
                        self.scrollBottom();
                    }
                });
            } else {
                // We are currently fetching, but would like to do so again.  Queue another fetch to happen
                // once this completes.  That avoids a car crash of fetches happening when there are a lot of
                // messages being sent and we're not keeping up.
                // console.log("Fetch again later");
                self.fetchAgain = true;
            }
        },

        send: function () {
            var self = this;
            var message = this.$('.js-message').val();

            // Don't allow people to send > as it will lead to the message being stripped as a possible reply.
            // TODO Allow this by recording the origin of the message as being on the platform.
            message = message.replace('>', '');

            if (message.length > 0) {
                // We get called back when the message has actually been sent to the server.
                self.listenToOnce(this.model, 'sent', function () {
                    self.getLatestMessages();
                });

                self.model.send(message);

                // Create another model with a fake id and add it to the collection.  This will populate our view
                // views while we do the real save in the background.  Makes us look fast.
                var prelast = self.messages.last();
                var nextid = prelast ? (prelast.get('id') + 1) : 1;
                var tempmod = new Iznik.Models.Chat.Message({
                    id: nextid,
                    chatid: self.model.get('id'),
                    message: message,
                    date: (new Date()).toISOString(),
                    sameaslast: true,
                    sameasnext: true,
                    seenbyall: 0,
                    type: 'Default',
                    user: Iznik.Session.get('me')
                });

                self.messages.add(tempmod);

                // We have initiated the send, so set up for the next one.
                self.$('.js-message').val('');
                self.$('.js-message').focus();
                self.messageFocus();

                // If we've grown the textarea, shrink it.
                self.$('textarea').css('height', '');
            }
        },

        lsID: function () {
            return ('chat-' + this.model.get('id'));
        },

        zapViews: function () {
            Iznik.Session.chats.remove({
                id: this.model.get('id')
            });
        },

        removeIt: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            var v = new Iznik.Views.Confirm({
                model: self.model
            });
            v.template = 'chat_remove';

            self.listenToOnce(v, 'confirmed', function () {
                // This will close the chat, which means it won't show in our list until we recreate it.  The messages
                // will be preserved.
                self.removed = true;
                self.$el.hide();
                try {
                    // Remove the local storage, otherwise it will clog up with info for chats we don't look at.
                    Storage.remove(this.lsID() + '-open');
                    Storage.remove(this.lsID() + '-height');
                    Storage.remove(this.lsID() + '-width');

                    // Also refetch the chats, so that our cached copy gets updated.
                    Iznik.Session.chats.fetch();
                } catch (e) {
                    console.error(e.message)
                }
                ;
                self.updateRoster('Closed', _.bind(self.zapViews, self), true);
            });

            v.render();

        },

        focus: function () {
            this.$('.js-message').click();
        },

        noop: function () {

        },

        promise: function () {
            // Promise a message to someone.
            var self = this;

            // Get our offers.
            self.offers = new Iznik.Collections.Message(null, {
                collection: 'Approved',
                modtools: false
            });

            self.offers.fetch({
                data: {
                    fromuser: Iznik.Session.get('me').id,
                    types: ['Offer'],
                    limit: 100
                }
            }).then(function () {
                if (self.offers.length > 0) {
                    // The message we want to suggest as the one to promise is any last message mentioned in this chat.
                    var msgid = null;
                    _.each(self.model.get('refmsgids'), function(m) {
                        msgid = m;
                    });

                    var msg = null;
                    self.offers.each(function (offer) {
                        if (offer.get('id') == msgid) {
                            msg = offer;
                        }
                    });

                    var v = new Iznik.Views.User.Message.Promise({
                        model: new Iznik.Model({
                            message: msg ? msg.toJSON2() : null,
                            user: self.model.get('user1').id != Iznik.Session.get('me').id ?
                                self.model.get('user1') : self.model.get('user2')
                        }),
                        offers: self.offers
                    });

                    self.listenToOnce(v, 'promised', function () {
                        msg.fetch();
                        self.model.trigger('promised');
                    });

                    v.render();
                }
            });
        },

        info: function () {
            var self = this;

            var v = new Iznik.Views.Chat.UserInfo({
                model: new Iznik.Model(self.model.get('user1').id != Iznik.Session.get('me').id ?
                        self.model.get('user1') : self.model.get('user2'))
            });

            v.render();
        },

        messageFocus: function () {
            var self = this;
            self.model.allseen();
            self.updateCount();
        },

        stayHidden: function() {
            if (this.minimised) {
                this.$el.hide();
                _.delay(_.bind(this.stayHidden, this), 5000);
            }
        },

        minimise: function (quick) {
            var self = this;
            this.minimised = true;
            this.stayHidden();

            if (!quick) {
                this.waitDOM(self, self.options.organise);
            }

            this.options.updateCounts();

            try {
                // Remove the local storage, otherwise it will clog up with info for chats we don't look at.
                Storage.remove(this.lsID() + '-open');
                Storage.remove(this.lsID() + '-height');
                Storage.remove(this.lsID() + '-width');
            } catch (e) {
                console.error(e.message)
            }

            this.trigger('minimised');
        },

        report: function () {
            var groups = Iznik.Session.get('groups');

            if (groups.length > 0) {
                // We only take reports from a group member, so that we have somewhere to send it.
                // TODO Give an error or pass to support?
                (new Iznik.Views.Chat.Report({
                    chatid: this.model.get('id')
                })).render();
            }
        },

        adjust: function () {
            var self = this;

            if (self.inDOM()) {
                // The text area shouldn't grow too high, but should go above a single line if there's room.
                var maxinpheight = self.$el.innerHeight() - this.$('.js-chatheader').outerHeight();
                var mininpheight = Math.round(self.$el.innerHeight() * .15);
                self.$('textarea').css('max-height', maxinpheight);
                self.$('textarea').css('min-height', mininpheight);

                var chatwarning = this.$('.js-chatwarning');
                var warningheight = chatwarning.length > 0 ? (chatwarning.css('display') == 'none' ? 0 : chatwarning.outerHeight()) : 0;
                var newHeight = this.$el.innerHeight() - this.$('.js-chatheader').outerHeight() - this.$('.js-chatfooter textarea').outerHeight() - this.$('.js-chatfooter .js-buttons').outerHeight() - warningheight;
                newHeight = Math.round(newHeight);

                // console.log("Adjust on", this.$el, newHeight);
                // console.log("Height", newHeight, this.$el.innerHeight(), this.$('.js-chatheader').outerHeight(), this.$('.js-chatfooter textarea').outerHeight(), this.$('.js-chatfooter .js-buttons').outerHeight(), warningheight);
                this.$('.js-leftpanel, .js-roster').height(newHeight);

                var width = self.$el.width();

                if (self.model.get('chattype') == 'Mod2Mod' || self.model.get('chattype') == 'Group') {
                    // Group chats have a roster.
                    var lpwidth = self.$('.js-leftpanel').width();
                    lpwidth = self.$el.width() - 60 < lpwidth ? (width - 60) : lpwidth;
                    lpwidth = Math.max(self.$el.width() - 250, lpwidth);
                    self.$('.js-leftpanel').width(lpwidth);
                } else {
                    // Others
                    self.$('.js-leftpanel').width('100%');
                }
            }
        },

        setSize: function () {
            var self = this;

            try {
                // Restore any saved height
                //
                // On mobile we maximise the chat window, as the whole resizing thing is too fiddly.
                var height = Storage.get('chat-' + self.model.get('id') + '-height');
                var width = Storage.get('chat-' + self.model.get('id') + '-width');
                if (isSM()) {
                    // Just maximise it.
                    width = $(window).innerWidth();
                    console.log("Small, maximimise", width);
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

                if (!isSM()) {
                    var lpwidth = Storage.get('chat-' + self.model.get('id') + '-lp');
                    lpwidth = self.$el.width() - 60 < lpwidth ? (self.$el.width() - 60) : lpwidth;

                    if (lpwidth) {
                        console.log("Restore chat width to", lpwidth);
                        self.$('.js-leftpanel').width(lpwidth);
                    }
                }
            } catch (e) {
            }
        },

        fullscreen: function () {
            // Save off any chat text.
            var message = this.$('.js-message').val();

            if (message) {
                // Save this off so we can restore it in the fullscreen.
                try {
                    Storage.set('lastchatmsg', message);
                    Storage.set('lastchatid', this.model.get('id'));
                } catch (e) {}
            }

            this.minimise();
            Router.navigate('/chat/' + this.model.get('id'), true);
        },

        restore: function (large) {
            var self = this;
            self.restoredAt = (new Date()).getTime();
            self.minimised = false;

            // Input text autosize
            // console.log("Autosize on " + self.model.get('id') + " " + self.doneAutosize);
            if (!self.doneAutosize) {
                self.doneAutosize = true;
                autosize(self.$('textarea'));
            }

            if (!self.options.modtools) {
                self.$('.js-privacy').hide();
            } else {
                self.$('.js-promise').hide();
            }

            if (large) {
                // We want a larger and more prominent chat.
                try {
                    Storage.set(this.lsID() + '-height', Math.floor(window.innerHeight * 2 / 3));
                    Storage.set(this.lsID() + '-width', Math.floor(window.innerWidth * 2 / 3));
                } catch (e) {
                }
            }

            // Restore the window first, so it feels zippier.
            self.setSize();
            self.waitDOM(self, self.options.organise);
            self.options.updateCounts();

            _.defer(function () {
                self.$el.css('visibility', 'visible');
                self.$el.show();
                self.adjust();
            });

            try {
                Storage.set(self.lsID() + '-open', 1);
            } catch (e) {
            }

            // We fetch the messages when restoring - no need before then.
            var v = new Iznik.Views.PleaseWait({
                label: 'chat restore'
            });
            v.render();
            self.messages.fetch({
                remove: true
            }).then(function () {
                // We've just opened this chat - so we have had a decent chance to see any unread messages.
                v.close();
                self.messageFocus();
                self.scrollBottom();
                self.trigger('restored');
            });

            self.$('.js-chatwarning').show();

            window.setTimeout(_.bind(function () {
                this.$('.js-chatwarning').slideUp('slow', _.bind(function() {
                    this.adjust();
                }, this));
            }, self), 30000);

            if (!self.windowResizeListening) {
                // If the window size changes, we will need to adapt.
                self.windowResizeListening = true;
                $(window).resize(function () {
                    self.setSize();
                    self.adjust();
                    self.options.organise();
                    self.scrollBottom();
                });
            }

            if (!self.madeResizable) {
                self.madeResizable = true;

                self.$el.resizable({
                    handleSelector: '#chat-active-' + self.model.get('id') + ' .js-grip',
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
            }

            _.delay(_.bind(self.adjustTimer, self), 5000);
        },

        adjustTimer: function() {
            // We run this to handle resizing due to onscreen keyboards.
            var self = this;

            if (!self.minimised) {
                self.adjust();
                _.delay(_.bind(self.adjustTimer, self), 5000);
            }
        },

        scrollTimer: null,
        scrollTo: 0,

        scrollBottom: function () {
            // Tried using .animate(), but it seems to be too expensive for the browser, so leave that for now.
            var self = this;
            var msglist = self.$('.js-messages');

            if (msglist.length > 0) {
                var height = msglist[0].scrollHeight;

                if (self.scrollTimer && self.scrollTo < height) {
                    // We have a timer outstanding to scroll to somewhere less far down that we now want to.  No point
                    // in doing that.
                    // console.log("Clear old scroll timer",  self.model.get('id'), self.scrollTo, height);
                    clearTimeout(self.scrollTimer);
                    self.scrollTimer = null;
                    self.scrollToStopAt = null;
                }

                // We want to scroll immediately, and gradually over the next few seconds for when things haven't quite
                // finished rendering yet.
                msglist.scrollTop(height);
                // console.log("Scroll now to ", self.model.get('id'), height);

                self.scrollTo = height;
                self.scrollToStopAt = self.scrollToStopAt ? self.scrollToStopAt : (new Date()).getTime() + 5000;

                if ((new Date()).getTime() < self.scrollToStopAt) {
                    self.scrollTimer = setTimeout(_.bind(self.scrollBottom, self), 1000);
                }
            }
        },

        dragend: function (event, el, opt) {
            var self = this;

            this.options.organise();
            self.trigger('resized');
            self.adjust();
            self.scrollBottom();

            // Save the new height to local storage so that we can restore it next time.
            try {
                Storage.set(this.lsID() + '-height', self.$el.height());
                Storage.set(this.lsID() + '-width', self.$el.width());
            } catch (e) {
            }
        },

        drag: function (event, el, opt) {
            var now = (new Date()).getMilliseconds();

            // We don't want to allow the resize 

            if (now - this.lastdrag > 20) {
                // We will need to remargin any other chats.  Don't do this too often as it makes dragging laggy.
                this.options.organise();
            }

            this.lastdrag = (new Date()).getMilliseconds();

        },

        panelSize: function (event, el, opt) {
            var self = this;

            // Save the new left panel width to local storage so that we can restore it next time.
            try {
                Storage.set(this.lsID() + '-lp', self.$('.js-leftpanel').width());
            } catch (e) {
            }

            self.adjust();
            self.scrollBottom();
        },

        status: function () {
            // We can override appearing online to show something else.
            //
            // TODO Obsolete pending rework of status.
            var status = this.$('.js-status').val();
            try {
                Storage.set('mystatus', status);
            } catch (e) {
            }
        },

        openChat: function (chatid) {
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                ChatHolder().fetchAndRestore(chatid);
            });
        },

        countHidden: true,

        updateCount: function () {
            var self = this;
            var unseen = self.model.get('unseen');
            // console.log("Update count", unseen);

            // For performance reasons we avoid doing show/hide unless we need to.
            if (unseen > 0) {
                self.$('.js-count').html(unseen).show();
                self.countHidden = false;

                if (self.messages) {
                    self.messages.fetch({
                        remove: true
                    });
                }
            } else if (!self.countHidden) {
                // When we call this from render, it's already hidden.
                self.$('.js-count').html(unseen).hide();
                self.countHidden = true;
            }

            self.trigger('countupdated', unseen);
        },

        render: function () {
            var self = this;

            // console.log("Render chat", self.model.get('id'), self); console.trace();

            if (!self.rendered) {
                self.rendered = true;
                self.$el.attr('id', 'chat-active-' + self.model.get('id'));
                self.$el.addClass('chat-' + self.model.get('name'));

                self.$el.css('visibility', 'hidden');

                self.messages = new Iznik.Collections.Chat.Messages({
                    roomid: self.model.get('id')
                });

                var p = Iznik.View.prototype.render.call(self);
                p.then(function (self) {
                    try {
                        var status = Storage.get('mystatus');

                        if (status) {
                            self.$('.js-status').val(status);
                        }
                    } catch (e) {
                    }

                    self.updateCount();

                    // If the unread message count changes, we want to update it.
                    self.listenTo(self.model, 'change:unseen', self.updateCount);

                    var minimise = true;

                    try {
                        // On mobile we start them all minimised as there's not much room, unless one has been forced open.
                        //
                        // Otherwise default to minimised, which is what we get if the key is missing and returns null.
                        var open = Storage.get(self.lsID() + '-open');
                        open = (open === null) ? open : parseInt(open);

                        if (!open || (open != 2 && isSM())) {
                            minimise = true;
                        } else {
                            minimise = false;

                            // Make sure we don't force open.
                            Storage.set(self.lsID() + '-open', 1);
                        }
                    } catch (e) {
                    }

                    self.$('.js-messages').empty();

                    self.messageViews = new Backbone.CollectionView({
                        el: self.$('.js-messages'),
                        modelView: Iznik.Views.Chat.Message,
                        collection: self.messages,
                        chatView: self,
                        comparator: 'id',
                        modelViewOptions: {
                            chatView: self,
                            chatModel: self.model
                        }
                    });

                    // As new messages are added, we want to show them.  This also means when we first render, we'll
                    // scroll down to the latest messages.
                    self.listenTo(self.messageViews, 'add', function (modelView) {
                        self.listenToOnce(modelView, 'rendered', function () {
                            self.scrollBottom();
                            // _.delay(_.bind(self.scrollBottom, self), 5000);
                        });
                    });

                    self.messageViews.render();

                    // During the render we don't need to reorganise - we do that when we have a chat open
                    // that we then minimise, to readjust the remaining windows.
                    minimise ? self.minimise(true) : self.restore();

                    // The minimised chat can signal to us that we should restore.
                    self.listenTo(self.model, 'restore', self.restore);

                    self.trigger('rendered');

                    // Photo upload button
                    self.$('.js-photo').fileinput({
                        uploadExtraData: {
                            imgtype: 'ChatMessage',
                            chatmessage: 1
                        },
                        showUpload: false,
                        allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                        uploadUrl: API + 'image',
                        resizeImage: true,
                        maxImageWidth: 800,
                        browseIcon: '',
                        browseLabel: '',
                        browseClass: 'clickme glyphicons glyphicons-camera text-muted gi-2x',
                        showCaption: false,
                        showRemove: false,
                        showCancel: false,
                        showPreview: true,
                        showUploadedThumbs: false,
                        dropZoneEnabled: false,
                        buttonLabelClass: '',
                        fileActionSettings: {
                            showZoom: false,
                            showRemove: false,
                            showUpload: false
                        },
                        layoutTemplates: {
                            footer: '<div class="file-thumbnail-footer">\n' +
                            '    {actions}\n' +
                            '</div>'
                        },
                    });

                    self.$('.js-photo').on('fileimagesresized', function (event) {
                        // Upload as soon as we have it.  Add an entry for the progress bar.
                        $('.file-preview, .kv-upload-progress').hide();
                        var prelast = self.messages.last();
                        var nextid = prelast ? (prelast.get('id') + 1) : 1;
                        nextid = _.isNaN(nextid) ? 1 : nextid;
                        var tempmod = new Iznik.Models.Chat.Message({
                            id: nextid,
                            roomid: self.model.get('id'),
                            date: (new Date()).toISOString(),
                            type: 'Progress',
                            user: Iznik.Session.get('me')
                        });

                        self.messages.add(tempmod);
                        self.$('.js-photo').fileinput('upload');
                    });

                    self.$('.js-photo').on('fileuploaded', function (event, data) {
                        console.log("Uploaded", event, data);
                        var ret = data.response;

                        // Create a chat message to hold it.
                        var tempmod = new Iznik.Models.Chat.Message({
                            roomid: self.model.get('id'),
                            imageid: ret.id
                        });

                        tempmod.save().then(function() {
                            // Fetch the messages again to pick up this new one.
                            self.messages.fetch();
                        });
                    });
                });
            } else {
                return(resolvedPromise(self));
            }

            return (p);
        }
    });

    Iznik.Views.Chat.Message = Iznik.View.extend({
        events: {
            'click .js-viewchat': 'viewChat',
            'click .chat-when': 'msgZoom',
            'click .js-imgzoom': 'imageZoom'
        },

        imageZoom: function() {
            var self = this;
            var v = new Iznik.Views.Chat.Message.PhotoZoom({
                model: self.model
            });

            v.render();
        },

        viewChat: function () {
            var self = this;

            var chat = new Iznik.Models.Chat.Room({
                id: self.model.get('refchatid')
            });

            chat.fetch().then(function () {
                var v = new Iznik.Views.Chat.Modal({
                    model: chat
                });

                v.render();
            });
        },

        msgZoom: function() {
            var self = this;
            var v = new Iznik.Views.Chat.Message.Zoom({
                model: self.model
            });
            v.render();
        },

        render: function () {
            var self = this;
            var p;
            //console.log("Render chat message", this.model.get('id'));

            if (this.model.get('id')) {
                var message = this.model.get('message');
                if (message) {
                    // Remove duplicate newlines.  Make sure we have a string - might not if the message was just a digit.
                    message += '';
                    message = message.replace(/\n\s*\n\s*\n/g, '\n\n');

                    // Strip HTML tags
                    message = strip_tags(message, '<a>');

                    // Insert some wbrs to allow us to word break long words (e.g. URLs).
                    // It might have line breaks in if it comes originally from an email.
                    message = wbr(message, 20).replace(/(?:\r\n|\r|\n)/g, '<br />');

                    this.model.set('message', message);
                }

                var group = this.options.chatModel.get('group');
                var myid = Iznik.Session.get('me').id;
                this.model.set('group', group);
                this.model.set('myid', myid);

                var d = Math.floor(moment().diff(moment(self.model.get('date'))) / 1000);
                self.model.set('secondsago', d);

                // Decide if this message should be on the left or the right.
                //
                // For group messages, our messages are on the right.
                // For conversations:
                // - if we're one of the users then our messages are on the right
                // - otherwise user1 is on the left and user2 on the right.
                var userid = this.model.get('user').id;
                var u1 = this.options.chatModel.get('user1');
                var user1 = u1 ? u1.id : null;
                var u2 = this.options.chatModel.get('user1');
                var user2 = u2 ? u2.id : null;

                if (group) {
                    this.model.set('left', userid != myid);
                } else if (myid == user1 || myid == user2) {
                    this.model.set('left', userid != myid);
                } else {
                    this.model.set('left', userid == user1);
                }

                //console.log("Consider left", userid, myid, user1, user2, this.model.get('left'));

                this.model.set('lastmsgseen', this.options.chatModel.get('lastmsgseen'));

                // This could be a simple chat message, or something more complex.
                var tpl;

                switch (this.model.get('type')) {
                    case 'ModMail':
                        tpl = this.model.get('refmsg') ? 'chat_modmail' : 'chat_message';
                        break;
                    case 'Interested':
                        tpl = this.model.get('refmsg') ? 'chat_interested' : 'chat_message';
                        break;
                    case 'Completed':
                        tpl = 'chat_completed';
                        break;
                    case 'Promised':
                        tpl = this.model.get('refmsg') ? 'chat_promised' : 'chat_message';
                        break;
                    case 'Reneged':
                        tpl = this.model.get('refmsg') ? 'chat_reneged' : 'chat_message';
                        break;
                    case 'ReportedUser':
                        tpl = 'chat_reported';
                        break;
                    case 'Progress':
                        tpl = 'chat_progress';
                        break;
                    case 'Image':
                        tpl = 'chat_image';
                        break;
                    default:
                        tpl = 'chat_message';
                        break;
                }

                this.template = tpl;

                p = Iznik.View.prototype.render.call(this);
                p.then(function (self) {
                    if (self.model.get('type') == 'ModMail' && self.model.get('refmsg')) {
                        // ModMails may related to a message which has been rejected.  If so, add a button to
                        // edit and resend.
                        var msg = self.model.get('refmsg');
                        var groups = msg.groups;

                        _.each(groups, function (group) {
                            if (group.collection == 'Rejected') {
                                self.$('.js-rejected').show();
                            }
                        });
                    }
                    self.$('.timeago').timeago();
                    self.$('.timeago').show();

                    // New messages are in bold - keep them so for a few seconds, to make it easy to see new stuff,
                    // then revert.
                    _.delay(_.bind(function () {
                        this.$('.chat-message-unseen').removeClass('chat-message-unseen');
                    }, self), 60000);

                    self.$el.fadeIn('slow');
                });
            } else {
                p = resolvedPromise(this);
            }

            return (p);
        }
    });

    Iznik.Views.Chat.Message.Zoom = Iznik.Views.Modal.extend({
        template: 'chat_messagezoom',

        render: function() {
            var self = this;
            var p = Iznik.Views.Modal.prototype.render.call(self);
            p.then(function () {
                var date = new moment(self.model.get('date'));
                self.$('.js-date').html(date.format('DD-MMM-YY HH:mm'));
            });

            return (p);
        }
    });

    Iznik.Views.Chat.Message.PhotoZoom = Iznik.Views.Modal.extend({
        template: 'chat_photozoom'
    });

    Iznik.Views.Chat.Roster = Iznik.View.extend({
        template: 'chat_roster'
    });

    Iznik.Views.Chat.RosterEntry = Iznik.View.extend({
        template: 'chat_rosterentry',

        events: {
            'click .js-click': 'dm'
        },

        dm: function () {
            var self = this;

            if (self.model.get('id') != Iznik.Session.get('me').id) {
                // We want to open a direct message conversation with this user.
                $.ajax({
                    type: 'PUT',
                    url: API + 'chat/rooms',
                    data: {
                        userid: self.model.get('userid')
                    }, success: function (ret) {
                        if (ret.ret == 0) {
                            self.trigger('openchat', ret.id);
                        }
                    }
                })
            }
        }
    });

    Iznik.Views.Chat.Enter = Iznik.Views.Modal.extend({
        template: 'chat_enter',
        
        events: {
            'click .js-send': 'send',
            'click .js-newline': 'newline'
        },
        
        send: function() {
            try {
                Storage.set('chatentersend', 1);
            } catch (e) {}
            this.close();
        },
        
        newline: function() {
            try {
                Storage.set('chatentersend', 0);
            } catch (e) {}
            this.close();
        }
    });
    
    Iznik.Views.Chat.Report = Iznik.Views.Modal.extend({
        template: 'chat_report',

        events: {
            'click .js-report': 'report'
        },

        report: function () {
            var self = this;
            var reason = self.$('.js-reason').val();
            var message = self.$('.js-message').val();
            var groupid = self.groupSelect.get();

            if (reason != '' && message != '') {
                Iznik.Session.chats.reportPerson(groupid, self.options.chatid, reason, message).then(function(chatid) {
                    instance.fetchAndRestore(chatid);
                });
                self.close();
            }
        },

        render: function () {
            var self = this;
            var p = Iznik.Views.Modal.prototype.render.call(self);
            p.then(function () {
                var groups = Iznik.Session.get('groups');

                if (groups.length >= 0) {
                    self.groupSelect = new Iznik.Views.Group.Select({
                        systemWide: false,
                        all: false,
                        mod: false,
                        choose: true,
                        id: 'reportGroupSelect'
                    });

                    self.groupSelect.render().then(function () {
                        self.$('.js-groupselect').html(self.groupSelect.el);
                    });
                }
            });

            return (p);
        }
    });

    Iznik.Views.Chat.Modal = Iznik.Views.Modal.extend({
        template: 'chat_modal',

        render: function () {
            // Open a modal containing the chat messages.
            var self = this;
            var p = Iznik.Views.Modal.prototype.render.call(self);
            p.then(function () {
                self.messages = new Iznik.Collections.Chat.Messages({
                    roomid: self.model.get('id')
                });

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-messages'),
                    modelView: Iznik.Views.Chat.Message,
                    collection: self.messages,
                    modelViewOptions: {
                        chatModel: self.model
                    }
                });

                console.log("Chat modal", self.$('.js-messages').length, self.messages, self.model);
                self.collectionView.render();
                self.messages.fetch({
                    remove: true
                });
            });

            return (p);
        }
    });

    Iznik.Views.Chat.UserInfo = Iznik.Views.Modal.extend({
        template: 'chat_userinfo',

        render: function () {
            var self = this;
            var userid = self.model.get('id');

            self.model = new Iznik.Models.ModTools.User({
                id: userid
            });

            var p = self.model.fetch({
                data: {
                    info: true
                }
            });

            p.then(function() {
                Iznik.Views.Modal.prototype.render.call(self).then(function () {
                    var mom = new moment(self.model.get('added'));
                    self.$('.js-since').html(mom.format('DD-MMM-YY'));
                });
            });

            return (p);
        }
    });

    return function (options) {
        if (!instance) {
            instance = new Iznik.Views.Chat.Holder(options);
        }

        return instance;
    }
});
