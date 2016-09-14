define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {

    Iznik.Models.Chat.Room = Iznik.Model.extend({
        urlRoot: API + 'chat/rooms',

        send: function(message) {
            var self = this;

            var msg = new Iznik.Models.Chat.Message({
                message: message,
                roomid: this.get('id')
            });
            msg.save().then(function() {
                self.trigger('sent', msg.get('id'));
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
        url: API + 'chat/rooms',

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

        fetch: function() {
            // Which chat types we fetch depends on whether we're in ModTools or the User i/f.
            // console.log("Fetch chats"); console.trace();
            return Iznik.Collection.prototype.fetch.call(this, {
                data: {
                    chattypes: this.options.modtools ? [ 'Mod2Mod', 'User2Mod' ] : [ 'User2User', 'User2Mod' ]
                }
            });
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