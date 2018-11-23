let NchanSubscriber = require("nchan");

define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'jquery-visibility'
], function($, _, Backbone, Iznik) {
    // We have a single send queue shared across chat rooms.
    var trigger = new Iznik.Model();
    var sending = [];

    function log() {
        try {
            console.log.apply(this, arguments);
        } catch (e) {}
    }

    function sendQueue() {
        var self = this;

        if (!_.isUndefined(Storage)) {
            try {
                // Try to send any queued messages.
                sending = JSON.parse(Storage.get('chatqueue'));
                sending = sending ? sending : [];

                if (sending && sending.length) {
                    var msg = new Iznik.Models.Chat.Message(sending.shift());
                    msg.save({
                        error: function() {
                            // Failed - retry later in case transient network issue.
                            _.delay(_.bind(sendQueue, self), 10000);
                        }
                    }).then(function() {
                        // We've sent it successfully.
                        Storage.set('chatqueue', JSON.stringify(sending));

                        if (sending.length > 0) {
                            // We have another message to send.
                            _.delay(_.bind(sendQueue, self), 100);
                        }

                        trigger.trigger('sent', msg);
                    });
                }
            } catch (e) {
                console.log("Probably no suitable storage", e);
            }
        } else {
            // Not got Storage yet - try again later.
            _.delay(_.bind(sendQueue, self), 10000);
        }
    }

    Iznik.Models.Chat.Room = Iznik.Model.extend({
        urlRoot: API + 'chat/rooms',

        initialize: function() {
            // If the last message unseen changes, we want to tell the server.
            var self = this;

            self.listenTo(self, 'change:lastmsgseen', function (model, value, options) {
                if (self._previousAttributes.hasOwnProperty('lastmsgseen')) {
                    // We get called during the initial fetch when we're setting the attribute for the first
                    // time - we don't want to hit the server in that case.
                    console.log("Change lastmsgseen", value, self._previousAttributes);
                    $.ajax({
                        url: API + 'chatrooms',
                        type: 'POST',
                        data: {
                            id: self.get('id'),
                            'lastmsgseen': value
                        }
                    });
                }
            });

            self.listenTo(trigger, 'sent', function(msg) {
                if (msg.get('roomid') == self.get('id')) {
                    self.trigger('sent');

                    // Maintain the lastmsgseen flag.  We might send multiple messages which complete in
                    // different order, so don't go backwards.
                    var lastmsg = msg.get('lastmsgseen');
                    self.set('lastmsgseen', lastmsg ? Math.max(lastmsg, msg.get('id')) : msg.get('id'));
                    self.set('unseen', 0);
                }
            });
        },

        send: function(message) {
            var self = this;

            // Escape any emojis.
            message = twemoji.replace(message, function(emoji) {
                return '\\\\u' + twemoji.convert.toCodePoint(emoji) + '\\\\u';
            });

            // Create a model for the message.
            var msg = {
                message: message,
                roomid: this.get('id')
            };

            // Add it to our sending queue
            sending.push(msg);

            // Save to local storage so we don't lose it, for example if the network flakes out and our app is killed.
            Storage.set('chatqueue', JSON.stringify(sending));

            sendQueue();
        },

        otherUser: function() {
            var u1 = this.get('user1');
            var user1 = u1 ? u1.id : null;
            var u2 = this.get('user2');
            var user2 = u2 ? u2.id : null;

            var myid = Iznik.Session.get('me').id;
            var other = user1 == myid ? user2 : user1;
            return(other);
        },

        otherUserMod: function() {
            var u1 = this.get('user1');
            var user1 = u1 ? u1.id : null;
            var u2 = this.get('user2');
            var user2 = u2 ? u2.id : null;

            var myid = Iznik.Session.get('me').id;
            var other = user1 == myid ? u2 : u1;
            return(other);
        },

        otherUserSpammer: function() {
            var u = this.otherUserMod();
            return(u && u.spammer);
        },

        nudge: function(userid) {
            var self = this;

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: API + 'chat/rooms',
                    type: 'POST',
                    data: {
                        id: self.get('id'),
                        action: 'Nudge'
                    }, success: function(ret) {
                        if (ret.ret === 0) {
                            resolve();
                        }
                    }
                });
            });

            return(p);
        },

        modnote: function(message) {
            var self = this;

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: API + 'chatmessages',
                    type: 'POST',
                    data: {
                        roomid: self.get('id'),
                        message: message,
                        modnote: true
                    }, success: function(ret) {
                        if (ret.ret === 0) {
                            resolve();
                        }
                    }
                });
            });

            return(p);
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

        close: function() {
            var self = this;

            return($.ajax({
                url: API + 'chatrooms',
                type: 'POST',
                data: {
                    id: self.get('id'),
                    status: 'Closed'
                }, success: function(ret) {
                }
            }));
        },

        block: function() {
            var self = this;

            return($.ajax({
                url: API + 'chatrooms',
                type: 'POST',
                data: {
                    id: self.get('id'),
                    status: 'Blocked'
                }, success: function(ret) {
                }
            }));
        },

        allseen: function() {
            var self = this;

            if (self.get('unseen') > 0) {
                // Record that we have seen the last message, and there are no unseen ones left.
                $.ajax({
                    url: API + 'chatrooms',
                    type: 'POST',
                    data: {
                        id: self.get('id'),
                        'lastmsgseen': self.get('lastmsg')
                    }, complete: function() {
                        self.fetch();
                    }
                });
            }
        }
    });

    Iznik.Collections.Chat.Rooms = Iznik.Collection.extend({
        fallbackInterval: 300000,

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

            // We want to know when the tab is active, as this affects how often we hit the server.
            $(document).on('hide', function () {
                self.tabActive = false;
            });

            $(document).on('show', function () {
                self.tabActive = true;
            });

            self.startup();
        },

        startup: function() {
            var self = this;

            if (Iznik.Session) {
                // Start our poll for new info.
                // DON'T POLL ON MOBILE APP: self.wait();

                // Start our fallback fetch for new info in case the poll doesn't tell us.
                _.delay(_.bind(self.fallback, self), self.fallbackInterval);
            } else {
                // We're still starting up.
                _.delay(_.bind(self.startup, self), 200);
            }
        },

        comparator: function(a, b) {
            // Sort by unseen, and then date of last message, if exists.
            var aunseen = a.get('unseen');
            var bunseen = b.get('unseen');
            if (aunseen && !bunseen) {
                return -1;
            } else if (bunseen && !aunseen) {
                return 1;
            } else {
                if (!a.get('lastdate')) {
                    return 1
                } else if (!b.get('lastdate')) {
                    return -1
                } else {
                    return (new Date(b.get('lastdate')).getTime()) - new Date(a.get('lastdate')).getTime()
                }
            }
        },

        fetch: function(options) {
            options = !options ? {} : options;
            if (!options.hasOwnProperty('data')) {
                options.data = {};
            }

            // This happens on a timer; maybe we've lost our session. So pass the persistent one.
            try {
                var sess = Storage.get('session');
                if (sess) {
                    var parsed = JSON.parse(sess);

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

            return Iznik.Collection.prototype.fetch.call(this, options);
        },

        parse: function(ret) {
            return(ret.chatrooms);
        },

        allseen: function () {
            var self = this;
            $.ajax({
                type: 'POST',
                url: API + 'chat/rooms',
                data: {
                    action: 'AllSeen'
                }, success: function (ret) {
                    self.fetch();
                }
            });
        },

        nchan: null,

        wait: function () {
            // We get notifications from the server (via websockets or long poll), which may prompt us to do some work
            // on a chat.  That way we get zippy messaging.
            //
            // We use a single connection rather than a per-chat one to avoid using too many client resources
            // (e.g. in the case of long polls).
            var self = this;

            var me = Iznik.Session.get('me');
            var myid = me ? me.id : null;

            if (!myid) {
                // Not logged in, try later;
                _.delay(_.bind(self.wait, self), 5000);
            } else if (!self.waiting) {
                // We only want one outstanding poll for this instance.
                self.waiting = true;

                self.nchan = new NchanSubscriber(CHAT_HOST + '/subscribe?id=' + myid, {
                    subscriber: [ 'websocket', 'eventsource', 'longpoll '],
                });

                self.nchan.start();

                console.log("NCHAN started");

                self.nchan.on('message', function(ret, message_metadata) {
                    console.log("NCHAN", ret, message_metadata);

                    if (ret) {
                        ret = JSON.parse(ret);

                        // We will get notified for both MT and FD chats.  But we only want to react to
                        // the one which this client actually is.
                        var mt = ret && ret.hasOwnProperty('modtools') ? ret.modtools : false;

                        if ((MODTOOLS == mt) && ret && ret.hasOwnProperty('text')) {
                            var data = ret.text;
                            log("Received notif", ret);

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
                                        self.trigger('somethinghappened', data.newroom);
                                    });
                                } else if (data.hasOwnProperty('roomid')) {
                                    // Activity on this room.  Fetch it.
                                    //
                                    // Make sure we have this chat in our collection - might not have picked
                                    // it up yet - timing windows.
                                    var chat = self.get(data.roomid);
                                    console.log("Activity on ", data.roomid, chat);
                                    if (!chat) {
                                        // We don't have it - get and add.
                                        chat = new Iznik.Models.Chat.Room({
                                            id: data.roomid
                                        });

                                        chat.fetch({
                                            remove: true
                                        }).then(function() {
                                            self.add(chat, { merge: true });
                                            self.trigger('somethinghappened', data.roomid);
                                        });
                                    } else {
                                        // We already have it.
                                        chat.fetch().then(function() {
                                            self.trigger('somethinghappened', data.roomid);
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            }
        },

        waitError: function () {
            // This can validly happen when we switch pages, because we abort outstanding requests
            // and hence our long poll.
            // TODO Do we get tidied?
            this.waiting = false;

            // Probably a network glitch.  Retry later.
            _.delay(_.bind(this.wait, this), 1000);
        },

        fallback: function () {
            var self = this;

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

        reportPerson: function (groupid, chatid, reason, message) {
            var self = this;

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    type: 'POST',
                    headers: {
                        'X-HTTP-Method-Override': 'PUT'
                    },
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

        approve: function() {
            return($.ajax({
                url: API + 'chatmessages',
                type: 'POST',
                data: {
                    id: this.get('id'),
                    action: 'Approve'
                }
            }));
        },

        approveall: function() {
            return($.ajax({
                url: API + 'chatmessages',
                type: 'POST',
                data: {
                    id: this.get('id'),
                    action: 'ApproveAllFuture'
                }
            }));
        },

        reject: function() {
            return ($.ajax({
                url: API + 'chatmessages',
                type: 'POST',
                data: {
                    id: this.get('id'),
                    action: 'Reject'
                }
            }))
        },

        hold: function() {
            return($.ajax({
                url: API + 'chatmessages',
                type: 'POST',
                data: {
                    id: this.get('id'),
                    action: 'Hold'
                }
            }));
        },

        release: function() {
            return($.ajax({
                url: API + 'chatmessages',
                type: 'POST',
                data: {
                    id: this.get('id'),
                    action: 'Release'
                }
            }));
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
        fetchParams: null,

        url: function() {
            var url = API + 'chat/rooms/' + this.options.roomid + '/messages?';

            if (this.fetchParams) {
                _.each(this.fetchParams, function(value, key) {
                    url += "&" + key + "=" + encodeURIComponent(value);
                });
            }

            return(url);
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
        },

        fetch: function(params) {
            this.fetchParams = params;
            return(Iznik.Collection.prototype.fetch.call(this, params));
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

    // Start the sending process in case there are any left from last time.
    $(document).ready(sendQueue);
});