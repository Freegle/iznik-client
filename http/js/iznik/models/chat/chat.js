define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {

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
        }
    });

    Iznik.Collections.Chat.Rooms = Iznik.Collection.extend({
        url: function() {
            // We might be searching.
            return(API + 'chat/rooms' + (this.options.search ? ("?search=" + encodeURIComponent(this.options.search)) : ''));
        },

        model: Iznik.Models.Chat.Room,

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
            // We always want to cache the return value, even if no cached callback is passed, so that we cache it
            // for later.
            options = !options ? {} : options;
            if (!options.hasOwnProperty('data')) {
                options.data = {};
            }

            // This happens on a timer; maybe we've lost our session. So pass the persistent one.
            try {
                sess = localStorage.getItem('session');
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
                options.cached = nullFn;
            }

            return Iznik.Collection.prototype.fetch.call(this, options);
        },

        parse: function(ret) {
            return(ret.chatrooms);
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