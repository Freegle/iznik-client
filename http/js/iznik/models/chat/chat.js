define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'jquery-visibility'
], function($, _, Backbone, Iznik) {
    // We have a singleton collection for chats.  This is to avoid hitting the server too much, and to ensure that
    // everything stays in step.
    var instance;

    function log() {
        console.log.apply(this, arguments);
    }

    Iznik.Models.Chat.Room = Iznik.Model.extend({
        urlRoot: API + 'chat/rooms',

        sending: [],

        send: function(message) {
            var self = this;

            // Create a model for the message.
            var msg = new Iznik.Models.Chat.Message({
                message: message,
                roomid: this.get('id')
            });

            // Add it to our sending queue
            self.sending.push(msg);
            self.sendQueue();
        },

        sendQueue: function() {
            var self = this;

            // Try to send any queued messages.
            var msg = self.sending.pop();
            msg.save({
                error: function() {
                    // Failed - retry later in case transient network issue.
                    self.sending.unshift($msg);
                    _.delay(_.bind(self.sendQueue, self), 10000);
                }
            }).then(function() {
                // Maintain the lastmsgseen flag.  We might send multiple messages which complete in
                // different order, so don't go backwards.
                var lastmsg = msg.get('lastmsgseen');
                self.set('lastmsgseen', Math.max(lastmsg, msg.get('id')));
                self.set('unseen', 0);

                if (self.sending.length > 0) {
                    // We have another message to send.
                    _.delay(_.bind(self.sendQueue, self), 100);
                } else {
                    // We have sent them all.
                    self.trigger('sent');
                }
            });
        },

        parse: function (ret) {
            // We might either be called from a collection, where the chat is at the top level, or
            // from getting an individual chat, where it's not.
            if (ret.hasOwnProperty('chatroom')) {
                return (ret.chatroom);
            } else {
                return (ret);
            }
        },

        seen: function() {
            var self = this;
            
            if (self.get('unseen') > 0) {
                // We have seen the last message, and there are no unseen ones left.
                var messages = self.get('messages');

                if (messages.length > 0) {
                    self.set('lastmsgseen', messages.at(messages.length - 1).get('id'));
                }

                self.set('unseen', 0);
            }
        },
    });

    Iznik.Collections.Chat.Rooms = Iznik.Collection.extend({
        fallbackInterval: 300000,

        rosterUpdateInterval: 25000,

        rosterUpdating: false,

        tabActive: true,

        waiting: false,

        filter: null,

        url: function() {
            // We might be searching.
            return(API + 'chat/rooms' + (this.options.search ? ("?search=" + encodeURIComponent(this.options.search)) : ''));
        },

        model: Iznik.Models.Chat.Room,

        initialize: function() {
            var self = this;

            log("ChatRoom Collection initialise");

            // The chat host is passed from the server.
            self.chathost = $('meta[name=iznikchat]').attr("content");

            self.listenTo(self, 'add', function (chat) {
                // We also want to sort if the unseen value changes, so that the chats with messages appear at the
                // top.
                self.listenTo(chat, 'change:unseen', self.sort);
            });

            // We want to know when the tab is active, as this affects how often we hit the server.
            $(document).on('hide', function () {
                log("Tab hidden");
                self.tabActive = false;
            });

            $(document).on('show', function () {
                log("Tab shown");
                self.tabActive = true;
            });

            self.startup();
        },

        startup: function() {
            var self = this;

            if (Iznik.Session) {
                // Start our poll for new info.
                self.wait();

                // Start our fallback fetch for new info in case the poll doesn't tell us.
                _.delay(_.bind(self.fallback, self), self.fallbackInterval);

                // Start our periodic roster update.
                _.delay(_.bind(self.bulkUpdateRoster, self), self.rosterUpdateInterval);
            } else {
                // We're still starting up.
                _.delay(_.bind(self.startup, self), 200);
            }
        },

        comparator: function(a, b) {
            // Sort by date of last message, if exists.
            if (!a.get('lastdate')) {
                return 1
            } else if (!b.get('lastdate')) {
                return -1
            } else {
                return (new Date(b.get('lastdate')).getTime()) - new Date(a.get('lastdate')).getTime()
            }
        },

        fetch: function(options) {
            options = !options ? {} : options;
            if (!options.hasOwnProperty('data')) {
                options.data = {};
            }

            // This happens on a timer; maybe we've lost our session. So pass the persistent one.
            try {
                sess = Storage.get('session');
                if (sess) {
                    parsed = JSON.parse(sess);

                    if (parsed.hasOwnProperty('persistent')) {
                        options.data.persistent = parsed.persistent;
                    }
                }
            } catch (e) {
                console.error("testLoggedIn exception", e.message);
            }

            // Which chat types we fetch depends on whether we're in ModTools or the User i/f.
            options.data.chattypes = (Iznik.Session && Iznik.Session.get('modtools')) ? [ 'Mod2Mod', 'User2Mod', 'Group' ] : [ 'User2User', 'User2Mod', 'Group' ];
            options.processData = true;

            if (!options.hasOwnProperty('cached')) {
                // We always want to cache the return value, even if no cached callback is passed, so that we cache it
                // for later.  Setting a callback (albeit null) achieves that.
                options.cached = nullFn;
            }

            return Iznik.Collection.prototype.fetch.call(this, options);
        },

        parse: function(ret) {
            return(ret.chatrooms);
        },

        allseen: function () {
            var self = this;
            self.each(function (chat) {
                chat.seen();
            });
        },

        wait: function () {
            // We have a long poll open to the server, which when it completes may prompt us to do some work on a
            // chat.  That way we get zippy messaging.
            //
            // TODO use a separate domain name to get round client-side limits on the max number of HTTP connections
            // to a single host.  We use a single connection rather than a per chat one for the same reason.
            var self = this;
            log("Start chat wait");

            if (Iznik.Session) {

            }
            var me = Iznik.Session.get('me');
            var myid = me ? me.id : null;

            if (!myid) {
                // Not logged in, try later;
                _.delay(_.bind(self.wait, self), 5000);
            } else if (!self.waiting) {
                // We only want one outstanding poll for this instance.
                self.waiting = true;

                $.ajax({
                    url: window.location.protocol + '//' + self.chathost + '/subscribe/' + myid,
                    global: false, // don't trigger ajaxStart to avoid showing a busy icon all the time
                    success: function (ret) {
                        self.waiting = false;
                        log("Received notif", ret);

                        if (ret && ret.hasOwnProperty('text')) {
                            var data = ret.text;

                            if (data) {
                                if (data.hasOwnProperty('newroom')) {
                                    // We have been notified that we are now in a new chat.  Pick it up.
                                    var chat = new Iznik.Models.Chat.Room({
                                        id: data.newroom
                                    });

                                    chat.fetch({
                                        remove: true
                                    }).then(function() {
                                        self.add(chat, { merge: true });
                                    });
                                } else if (data.hasOwnProperty('roomid')) {
                                    // Activity on this room.  Fetch it.
                                    //
                                    // Make sure we have this chat in our collection - might not have picked
                                    // it up yet - timing windows.
                                    var chat = new Iznik.Models.Chat.Room({
                                        id: data.roomid
                                    });

                                    chat.fetch({
                                        remove: true
                                    }).then(function() {
                                        self.add(chat, { merge: true });
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
        },

        waitError: function () {
            // This can validly happen when we switch pages, because we abort outstanding requests
            // and hence our long poll.
            // TODO Do we get tidied?
            log("Wait error", this);
            // Probably a network glitch.  Retry later.
            _.delay(_.bind(this.wait, this), 1000);
        },

        fallback: function () {
            var self = this;
            log("Chat fallback");

            // Although we should be notified of new chat messages via the wait() function, this isn't guaranteed.  So
            // we have a fallback poll to pick up any lost messages.  This will return the last message we've seen
            // in each chat - so we scan first to remember the old ones.  That way we can decide whether we need
            // to refetch the chat.
            var lastseens = [];
            self.each(function (chat) {
                lastseens[chat.get('id')] = chat.get('lastmsgseen');
            });

            self.fetch().then(function () {
                // Now work out which chats if any we need to refetch.
                self.fallbackFetch = [];
                self.each(function (chat) {
                    if (lastseens[chat.get('id')] != chat.get('lastmsgseen')) {
                        log("Need to refresh", chat);
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
        },

        setStatus: function(status, force) {
            var self = this;

            if (status == 'Online') {
                // We are online, but may have overridden this to appear something else.
                try {
                    var savestatus = Storage.get('mystatus');
                    status = savestatus ? savestatus : status;
                } catch (e) {}
            }

            self.status = status;

            if (force) {
                self.bulkUpdateRoster();
            }
        },

        bulkUpdateRoster: function () {
            var self = this;

            log("bulkUpdateRoster");

            if (!self.rosterUpdating) {
                if (self.tabActive) {
                    var updates = [];
                    self.each(function (chat) {
                        if (self.status != 'Away') {
                            // There's no real need to tell the server that we're in Away status - it will time us out into
                            // that anyway.  This saves a lot of update calls if we're a mod on many groups.
                            updates.push({
                                id: chat.get('id'),
                                status: self.status,
                                lastmsgseen: chat.get('lastmsgseen')
                            });
                        }
                    });

                    if (updates.length > 0) {
                        // We put the restart of the timer into success/error as complete can get called
                        // multiple times in the event of retry, leading to timer explosion.
                        log("Got updates", updates);
                        self.rosterUpdating = true;

                        $.ajax({
                            url: API + 'chatrooms',
                            type: 'POST',
                            data: {
                                'rosters': updates
                            }, success: function (ret) {
                                // Update the returned roster into each active chat.
                                self.rosterUpdating = false;
                                self.each(function (chat) {
                                    var roster = ret.rosters[chat.get('id')];
                                    if (!_.isUndefined(roster)) {
                                        chat.set('roster', roster);
                                    }
                                });

                                _.delay(_.bind(self.bulkUpdateRoster, self), self.rosterUpdateInterval);
                            }, error: function (a,b,c) {
                                self.rosterUpdating = false;
                                _.delay(_.bind(self.bulkUpdateRoster, self), self.rosterUpdateInterval);
                            }
                        });
                    } else {
                        // No statuses to update.
                        log("No statuses to update");
                        _.delay(_.bind(self.bulkUpdateRoster, self), self.rosterUpdateInterval);
                    }
                } else {
                    // Tab not active.  We don't update the roster because there is no activity to pass on.
                    log("Tab not active");
                    _.delay(_.bind(self.bulkUpdateRoster, self), self.rosterUpdateInterval);
                }
            }
        },

        reportPerson: function (groupid, chatid, reason, message) {
            var self = this;

            var p = new Promise(function(resolve, reject) {
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
                                resolve(ret.id);
                            });
                        }
                    }
                });
            });

            return(p);
        }
    });

    Iznik.Models.Chat.Message = Iznik.Model.extend({
        urlRoot: function() {
            return(API + 'chat/rooms/' + this.get('roomid') + '/messages')
        },

        parse: function (ret) {
            // We might either be called from a collection, where the message is at the top level, or
            // from getting an individual message, where it's not.
            if (ret.hasOwnProperty('chatmessage')) {
                return (ret.chatmessage);
            } else {
                return (ret);
            }
        }
    });

    Iznik.Collections.Chat.Messages = Iznik.Collection.extend({
        url: function() {
            return(API + 'chat/rooms/' + this.options.roomid + '/messages')
        },

        model: Iznik.Models.Chat.Message,

        comparator: 'id',

        parse: function(ret) {
            var msgs = ret.chatmessages;

            // Fill in the users - each message has the user object below it for our convenience, even though the server
            // returns them in a separate object for bandwidth reasons.
            _.each(msgs, function(msg, index, list) {
                msg.user = ret.chatusers[msg.userid];
            });

            return(msgs);
        }
    });

    Iznik.Collections.Chat.Review = Iznik.Collection.extend({
        url: API + 'chatmessages',

        model: Iznik.Models.Chat.Message,

        comparator: 'id',

        parse: function(ret) {
            var msgs = ret.chatmessages;
            return(msgs);
        }
    });

    Iznik.Collections.Chat.Report = Iznik.Collection.extend({
        url: API + 'chatmessages',

        model: Iznik.Models.Chat.Message,

        comparator: 'id',

        parse: function(ret) {
            var msgs = ret.chatmeports;
            return(msgs);
        }
    });
});