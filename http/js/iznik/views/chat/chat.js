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
    'jquery-visibility'
], function ($, _, Backbone, Iznik, autosize, moment) {
    // This is a singleton view.
    var instance;

    Iznik.Views.Chat.Holder = Iznik.View.extend({
        template: 'chat_holder',

        id: "chatHolder",

        bulkUpdateRunning: false,

        tabActive: true,

        minimiseall: function () {
            Iznik.activeChats.viewManager.each(function (chat) {
                chat.minimise();
            });

            // Close the dropdown.  This helps if there is nothing to do - at least something happens.
            $('#notifchatdropdown').hide();
        },

        allseen: function () {
            Iznik.minimisedChats.viewManager.each(function (chat) {
                try {
                    if (chat.model.get('unseen') > 0) {
                        chat.allseen();

                        if (!chat.minimised && typeof chat.statusWithOverride == 'function') {
                            // This may exist for open chats but not minimised.
                            chat.updateRoster(chat.statusWithOverride('Online'), chat.noop, true);
                        }
                    }
                } catch (e) {
                    console.error("Failed to process chat", chat, e.message);
                }
            });
            $('#notifchatdropdownlist').empty();
            Iznik.minimisedChats.render();
        },

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

        wait: function () {
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

                function callwait() {   // CC
                    self.wait.call(self);
                }

                if (navigator.connection.type === Connection.NONE) {    // CC
                    _.delay(callwait, 5000);
                    return;
                }

                if (!myid) {
                    // Not logged in, try later;
                    _.delay(callwait, 5000);    // CC
                } else if (!self.waiting) {
                    self.waiting = true;

                    var chathost = $('meta[name=iznikchat]').attr("content");

                    $.ajax({
                        url: 'https://' + chathost + '/subscribe/' + myid, // CC
                        global: false, // don't trigger ajaxStart
                        success: function (ret) {
                            self.waiting = false;
                            //console.log("Received notif", ret);

                            if (ret && ret.hasOwnProperty('text')) {
                                var data = ret.text;

                                if (data) {
                                    if (data.hasOwnProperty('newroom')) {
                                        // We have been notified that we are now in a new chat.  Pick it up.
                                        Iznik.Session.chats.fetch().then(function () {
                                            // Now that we have the chat, update our status in it.
                                            var chat = Iznik.Session.chats.get(data.newroom);

                                            // If the unread message count changes in the new chat, we want to update.
                                            self.listenTo(chat, 'change:unseen', self.updateCounts);
                                            self.updateCounts();

                                            if (chat) {
                                                var chatView = Iznik.activeChats.viewManager.findByModel(chat);
                                                if (!chatView.minimised) {
                                                    chatView.updateRoster(chatView.statusWithOverride('Online'), chatView.noop);
                                                }
                                            }

                                            Iznik.Session.chats.trigger('newroom', data.newroom);
                                        });
                                    } else if (data.hasOwnProperty('roomid')) {
                                        // Activity on this room.  If the chat is active, then we refetch the messages
                                        // within it so that they are displayed.  If it's not, then we don't want
                                        // to keep fetching messages - the notification count will get updated by
                                        // the roster poll.
                                        var chat = new Iznik.Models.Chat.Room({
                                            id: data.roomid
                                        });

                                        chat.fetch({
                                            remove: true
                                        }).then(function() {
                                            // Make sure we have this chat in our collection - might not have picked
                                            // it up yet.
                                            Iznik.Session.chats.add(chat, { merge: true });

                                            // View should now be present.
                                            var chatView = Iznik.activeChats.viewManager.findByModel(chat);

                                            if (chatView && !chatView.minimised) {
                                                self.waiting = true;
                                                chatView.messages.fetch();
                                            }
                                        });
                                    }
                                }
                            }

                            if (!self.waiting) {
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

        fallback: function () {
            var self = this;

            if (self.inDOM()) {
                // Although we should be notified of new chat messages via the wait() function, this isn't guaranteed.  So
                // we have a fallback poll to pick up any lost messages.  This will return the last message we've seen
                // in each chat - so we scan first to remember the old ones.  That way we can decide whether we need
                // to refetch the chat.
                var lastseens = [];
                Iznik.Session.chats.each(function (chat) {
                    lastseens[chat.get('id')] = chat.get('lastmsgseen');
                });

                Iznik.Session.chats.fetch().then(function () {
                    // First make sure that the minimised chat list and counts are up to date.
                    self.updateCounts();

                    self.createMinimised();

                    // Now work out which chats if any we need to refetch.
                    self.fallbackFetch = [];
                    Iznik.Session.chats.each(function (chat) {
                        if (lastseens[chat.get('id')] != chat.get('lastmsgseen')) {
                            console.log("Need to refresh", chat);
                            self.fallbackFetch.push(chat);
                        }
                    });

                    if (self.fallbackFetch.length > 0) {
                        // Don't want to fetch them all in a single blat, though, as that is mean to the server and
                        // not needed for a background fallback.
                        var delay = 30000;
                        var i = 0;

                        (function fallbackOne() {
                            if (i < self.fallbackFetch.length) {
                                self.fallbackFetch[i].fetch();
                                i++;
                                _.delay(fallbackOne, delay);
                            } else {
                                // Reached end.
                                _.delay(_.bind(self.fallback, self), self.fallbackInterval);
                            }
                        })();
                    } else {
                        // None to fetch - just wait for next time.
                        _.delay(_.bind(self.fallback, self), self.fallbackInterval);
                    }
                });
            } else {
                self.destroyIt();
            }
        },

        bulkUpdateRoster: function () {
            var self = this;

            if (self.tabActive) {
                var updates = [];
                Iznik.Session.chats.each(function (chat) {
                    var status = chat.get('rosterstatus');

                    if (status && status != 'Away') {
                        // There's no real need to tell the server that we're in Away status - it will time us out into
                        // that anyway.  This saves a lot of update calls in the case where we're loading the page
                        // and minimising many chats, e.g. if we're a mod on many groups.
                        updates.push({
                            id: chat.get('id'),
                            status: status,
                            lastmsgseen: chat.get('lastmsgseen')
                        });
                    }
                });

                if (updates.length > 0) {
                    // We put the restart of the timer into success/error as complete can get called
                    // multiple times in the event of retry, leading to timer explosion.
                    $.ajax({
                        url: API + 'chatrooms',
                        type: 'POST',
                        data: {
                            'rosters': updates
                        }, success: function (ret) {
                            // Update the returned roster into each active chat.
                            Iznik.activeChats.viewManager.each(function (chat) {
                                var roster = ret.rosters[chat.model.get('id')];
                                if (!_.isUndefined(roster)) {
                                    chat.lastRoster = roster;
                                }
                            });

                            _.delay(_.bind(self.bulkUpdateRoster, self), 25000);
                        }, error: function (a,b,c) {
                            _.delay(_.bind(self.bulkUpdateRoster, self), 25000);
                        }
                    });
                } else {
                    // No statuses to update.
                    _.delay(_.bind(self.bulkUpdateRoster, self), 25000);
                }
            } else {
                // Tab not active - nothing to do.
                _.delay(_.bind(self.bulkUpdateRoster, self), 25000);
            }
        },

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

            if (Iznik.activeChats) {
                Iznik.activeChats.viewManager.each(function (chat) {
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
                        Iznik.activeChats.viewManager.each(function (chat) {
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
                        Iznik.activeChats.viewManager.each(function (chat) {
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
                Iznik.activeChats.viewManager.each(function (chat) {
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

            // The drop-down menu needs to be scrollable, and so we put a max-height on it.
            $('#notifchatdropdown').css('max-height', windowInnerHeight - navbarOuterHeight);

            // console.log("Organised", (new Date()).getMilliseconds() - start);
        },

        unseenCount: -1,

        updateCounts: function () {
            var self = this;
            var unseen = 0;
            console.log("updateCounts");
            if (Iznik.activeChats) {
                Iznik.Session.chats.each(function (chat) {
                    var chatView = Iznik.activeChats.viewManager.findByModel(chat);
                    unseen += chat.get('unseen');
                    console.log("Unseen", unseen, chat);
                });
            }
            /*{
                var msg = new Date();
                msg = msg.toLocaleTimeString() + " U " + unseen + ' ' + self.unseenCount + "<br/>";
                badgeconsole += msg;
                $('#badgeconsole').html(badgeconsole);
            }*/

            // We'll adjust the count in the window title.
            var title = document.title;
            var match = /\(.*\) (.*)/.exec(title);
            title = match ? match[1] : title;

            // This if text improves browser performance by avoiding unnecessary show/hides.
            if (self.unseenCount != unseen) {
                self.unseenCount = unseen;

                if (unseen > 0) {
                    $('#dropdownmenu').find('.js-totalcount').html(unseen).show();
                    $('#js-notifchat').find('.js-totalcount').html(unseen).show();
                    document.title = '(' + unseen + ') ' + title;
                } else {
                    $('#dropdownmenu').find('.js-totalcount').html(unseen).hide();
                    $('#js-notifchat').find('.js-totalcount').html(unseen).hide();
                    document.title = title;
                }

                this.showMin();

                if (mobilePush) {
                    mobilePush.setApplicationIconBadgeNumber(function () { }, function () { }, unseen);
                    /*var msg = new Date();
                    msg = msg.toLocaleTimeString() + " C " + unseen + "<br/>";
                    badgeconsole += msg;
                    $('#badgeconsole').html(badgeconsole);*/
                }
            }
        },

        reportPerson: function (groupid, chatid, reason, message) {
            var self = this;

            $.ajax({
                type: 'PUT',
                url: API + 'chat/rooms',
                data: {
                    chattype: 'User2Mod',
                    groupid: groupid
                }, success: function (ret) {
                    if (ret.ret == 0) {
                        // Now create a report message.
                        var msg = new Iznik.Models.Chat.Message({
                            roomid: ret.id,
                            message: message,
                            reportreason: reason,
                            refchatid: chatid
                        });
                        msg.save().then(function () {
                            // Now open the chat so that the user sees what's happening.
                            self.fetchAndRestore(ret.id);
                        });
                    }
                }
            });
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
                        var chatView = Iznik.activeChats.viewManager.findByModel(chat);
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

        filter: '',
        searchChats: null,
        searchTimer: null,

        searchKey: function () {
            var self = this;

            self.filter = $('#notifchatdropdown').find('.js-search').val();

            // Apply the filter immediately - if we get matches on the name or snippet that will look zippy.
            Iznik.minimisedChats.reapplyFilter('visibleModels');

            if (self.filter.length > 2) {
                // Now search on the sever.  But delay this to allow for extra keystrokes - avoids hitting
                // the server many times.
                if (self.searchTimer) {
                    clearTimeout(self.searchTimer);
                }

                self.searchChats = new Iznik.Collections.Chat.Rooms({
                    search: self.filter
                });

                self.searchTimer = setTimeout(function() {
                    self.searchChats.fetch().then(function() {
                        Iznik.minimisedChats.reapplyFilter('visibleModels');
                    });
                }, 500);
            }
        },

        searchFilter: function (model) {
            var self = this;
            var filt = self.filter.toLowerCase();
            var snippet = model.get('snippet') ? model.get('snippet') : '';

            var ret = (self.filter.length === 0 ||
            snippet.toLowerCase().indexOf(filt) !== -1 ||
            model.get('name').toLowerCase().indexOf(filt) !== -1);

            if (!ret && self.searchChats) {
                ret = self.searchChats.get(model.get('id'));
            }

            return (ret);
        },

        createMinimised: function () {
            var self = this;

            $('#notifchatdropdownlist').empty();
            Iznik.minimisedChats = new Backbone.CollectionView({
                el: $('#notifchatdropdownlist'),
                modelView: Iznik.Views.Chat.Minimised,
                collection: Iznik.Session.chats,
                visibleModelsFilter: _.bind(self.searchFilter, self),
                modelViewOptions: {
                    organise: _.bind(self.organise, self),
                    updateCounts: _.bind(self.updateCounts, self),
                    modtools: self.options.modtools
                }
            });

            self.listenTo(Iznik.Session.chats, 'change:lastdate', function(model) {
                // We want to reach to this by making sure the changed chat is near the top of the list of
                // minimised chats, so that it's easier to find.  This achieves that...but it's a bit of a
                // hack.  Probably the architecturally right way to do this would be to trigger a sort
                // on the collection, which ought to sort the collectionview.  But what seems to happen
                // is that this causes any open chats to be closed when they're detached.  I've spent enough
                // time failing to work out why - so we do it this way.
                var view = Iznik.minimisedChats.viewManager.findByModel(model);
                view.$el.detach();
                $('#notifchatdropdownlist').prepend(view.$el);
            });

            $('#notifchatdropdownlist').empty();
            Iznik.minimisedChats.render();

            $('#js-notifchat').click(function (e) {
                Router.navigate("#chat"); // CC
                var display = $('#notifchatdropdown').css('display');

                if (display === 'none') {
                    $('#notifchatdropdown').show();
                } else {
                    $('#notifchatdropdown').hide();
                }
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            });

            $(document).click(function (e) {
                // If we click outside the chat dropdown, hide it.
                if (!$(e.target).closest('#notifchatdropdown').length) {
                    $('#notifchatdropdown').hide();
                }

                // If we click outside the dropdown menu, hide that.
                $('.navbar-collapse').collapse('hide');
            });

            // Not within this DOM.
            $('.js-minimiseall').on('click', self.minimiseall);
            $('.js-allseen').on('click', self.allseen);
            $('.js-search').on('keyup', _.bind(self.searchKey, self));

            self.showMin();
        },

        waitForView: function(chatid) {
            var self = this;
            var retry = true;
            var chat = Iznik.Session.chats.get(chatid);

            if (chat) {
                var chatView = Iznik.activeChats.viewManager.findByModel(chat);
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
                Iznik.activeChats = new Backbone.CollectionView({
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

                Iznik.activeChats.render();

                self.waitDOM(self, function () {
                    self.createMinimised();
                    self.organise();
                    Iznik.Session.trigger('chatsfetched');
                });

                self.organise();

            }
        },

        render: function () {
            var self = this;
            var p;

            // We might already be rendered, as we're outside the body content that gets zapped when we move from
            // page to page.
            if ($('#chatHolder').length == 0) {
                self.$el.css('visibility', 'hidden');

                Iznik.Session.chats = new Iznik.Collections.Chat.Rooms({
                    modtools: Iznik.Session.get('modtools')
                });

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

                    if (!self.bulkUpdateRunning) {
                        // We update the roster for all chats periodically.
                        self.bulkUpdateRunning = true;
                        _.delay(_.bind(self.bulkUpdateRoster, self), 25000);
                    }

                    // Now ensure we are told about new messages.
                    self.wait();
                    _.delay(_.bind(self.fallback, self), self.fallbackInterval);
                });

                $(document).on('hide', function () {
                    self.tabActive = false;
                });

                $(document).on('show', function () {
                    self.tabActive = true;
                });
            } else {
                p = resolvedPromise(self);
            }

            if (!self.windowResizeListening) {
                // If the window size changes, we will need to adapt.
                self.windowResizeListening = true;
                $(window).resize(function () {
                    self.organise();
                });
            }

            return (p);
        }
    });

    Iznik.Views.Chat.Minimised = Iznik.View.Timeago.extend({
        template: 'chat_minimised',

        tagName: 'li',

        className: 'clickme padleftsm',

        events: {
            'click': 'click'
        },

        click: function () {
            // The maximised chat view is listening on this.
            this.model.trigger('restore', this.model.get('id'));
        },

        allseen: function () {
            var self = this;

            if (self.model.get('unseen') > 0) {
                // We have to get the messages to find out which the last one is.
                self.messages = new Iznik.Collections.Chat.Messages({
                    roomid: self.model.get('id')
                });
                self.messages.fetch({
                    remove: true
                }).then(function () {
                    if (self.messages.length > 0) {
                        var lastmsgseen = self.messages.at(self.messages.length - 1).get('id');
                        $.ajax({
                            url: API + 'chat/rooms/' + self.model.get('id'),
                            type: 'POST',
                            data: {
                                lastmsgseen: lastmsgseen,
                                status: 'Away'
                            }
                        });

                        self.model.set('unseen', 0);
                        self.model.set('lastmsgseen', lastmsgseen);
                    }
                });
            }
        },

        updateCount: function () {
            var self = this;
            var unseen = self.model.get('unseen');
            var current = self.$('.js-count').html();

            // Don't do DOM manipulations unless we need to as that's a performance killer.
            if (unseen != current) {
                if (unseen > 0) {
                    self.$('.js-count').html(unseen).show();
                } else {
                    self.$('.js-count').html(unseen).hide();
                }
            }

            self.trigger('countupdated', unseen);
        },

        render: function () {
            var p = Iznik.View.Timeago.prototype.render.call(this);
            p.then(function (self) {
                self.updateCount();

                // If the unread message count changes, we want to update it.
                if (!self.unseenListen) {
                    self.unseenListen = true;
                    self.listenTo(self.model, 'change:unseen', self.updateCount);
                }

                if (!self.snippetListen) {
                    self.snippetListen = true;
                    self.listenTo(self.model, 'change:snippet', self.render);
                }
            });

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
            'click .js-send': 'send',
            'click .js-large': 'large',
            'click .js-small': 'small',
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

        allseen: function () {
            if (this.messages.length > 0) {
                this.model.set('lastmsgseen', this.messages.at(this.messages.length - 1).get('id'));
                // console.log("Now seen chat message", this.messages.at(this.messages.length - 1).get('id'));
            }
            this.model.set('unseen', 0);
        },

        messageFocus: function () {
            var self = this;

            // We've seen all the messages.
            self.allseen();

            // Tell the server now, in case they navigate away before the next roster timer.
            self.updateRoster(self.statusWithOverride('Online'), self.noop, true);

            this.updateCount();
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

            self.updateRoster('Away', self.noop);

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

            // The text area shouldn't grow too high, but should go above a single line if there's room.
            var maxinpheight = self.$el.innerHeight() - this.$('.js-chatheader').outerHeight();
            var mininpheight = Math.round(self.$el.innerHeight() * .15);
            self.$('textarea').css('max-height', maxinpheight);
            self.$('textarea').css('min-height', mininpheight);

            var chatwarning = this.$('.js-chatwarning');
            var warningheight = chatwarning.length > 0 ? (chatwarning.css('display') == 'none' ? 0 : chatwarning.outerHeight()) : 0;
            var newHeight = this.$el.innerHeight() - this.$('.js-chatheader').outerHeight() - this.$('.js-chatfooter').outerHeight() - warningheight;
            var newHeight = this.$el.innerHeight() - this.$('.js-chatheader').outerHeight() - this.$('.js-chatfooter textarea').outerHeight() - this.$('.js-chatfooter .js-buttons').outerHeight() - warningheight;
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

        large: function () {
            this.$el.width($(window).innerWidth());
            this.$el.height($(window).innerHeight() - $('.navbar').outerHeight());

            this.$('.js-large').hide();
            this.$('.js-small').show();

            this.adjust();
            this.scrollBottom();
        },

        small: function () {
            this.$el.width(Math.floor(0.3 * $(window).innerWidth()));
            this.$el.height(Math.floor(0.3 * $(window).innerHeight()));

            this.$('.js-large').show();
            this.$('.js-small').hide();

            this.adjust();
            this.scrollBottom();
        },

        restore: function (large) {
            var self = this;
            self.restoredAt = (new Date()).getTime();
            self.minimised = false;

            // Hide the chat list if it's open.
            $('#notifchatdropdown').hide();

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

            self.updateRoster(self.statusWithOverride('Online'), self.noop);

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
            var status = this.$('.js-status').val();
            try {
                Storage.set('mystatus', status);
            } catch (e) {
            }

            this.updateRoster(status, this.noop);
        },

        updateRoster: function (status, callback, force) {
            var self = this;
            // console.log("Update roster", self.model.get('id'), status, force);

            // Save the current status in the chat for the next bulk roster update to the server.
            // console.log("Set roster status", status, self.model.get('id'));
            self.model.set('rosterstatus', status);

            if (force) {
                // We want to make sure the server knows right now.
                $.ajax({
                    url: API + 'chat/rooms/' + self.model.get('id'),
                    type: 'POST',
                    data: {
                        lastmsgseen: self.model.get('lastmsgseen'),
                        status: status
                    }, success: function (ret) {
                        if (ret.ret === 0) {
                            self.lastRoster = ret.roster;
                            self.lastUnseen = ret.lastunseen
                        }

                        callback(ret);
                    }
                });
            } else {
                // console.log("Suppress update", self.lastRoster);
                callback({
                    ret: 0,
                    status: 'Update delayed',
                    roster: self.lastRoster,
                    unseen: self.lastUnseen
                });
            }
        },

        statusWithOverride: function (status) {
            if (status == 'Online') {
                // We are online, but may have overridden this to appear something else.
                try {
                    var savestatus = Storage.get('mystatus');
                    status = savestatus ? savestatus : status;
                } catch (e) {
                }
            }

            return (status);
        },

        openChat: function (chatid) {
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                ChatHolder().fetchAndRestore(chatid);
            });
        },

        rosterUpdated: function (ret) {
            var self = this;

            if (ret.ret === 0) {
                if (!_.isUndefined(ret.roster)) {
                    self.$('.js-roster').empty();
                    // console.log("Roster", ret.roster);
                    _.each(ret.roster, function (rost) {
                        var mod = new Iznik.Model(rost);
                        var v = new Iznik.Views.Chat.RosterEntry({
                            model: mod,
                            modtools: self.options.modtools
                        });
                        self.listenTo(v, 'openchat', self.openChat);
                        v.render().then(function (v) {
                            self.$('.js-roster').append(v.el);
                        })
                    });
                }

                if (!_.isUndefined(ret.unseen)) {
                    // console.log("Set unseen from", self.model.get('unseen'), ret.unseen, ret);
                    self.model.set('unseen', ret.unseen);
                }
            }

            _.delay(_.bind(self.roster, self), 30000);
        },

        roster: function () {
            // We update our presence and get the roster for the chat regularly if the chat is open.  If it's
            // minimised, we don't - the server will time us out as away.  We'll still; pick up any new messages on
            // minimised chats via the long poll, and the fallback.
            var self = this;

            if (!self.removed && !self.minimised) {
                self.updateRoster(self.statusWithOverride('Online'),
                    _.bind(self.rosterUpdated, self));
            }
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

                    // Get the roster to see who's there.
                    self.roster();
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
            'click .chat-when': 'msgZoom'
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
                instance.reportPerson(groupid, self.options.chatid, reason, message);
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
