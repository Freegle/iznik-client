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

        comparator: function(item) {
            return - (new Date(item.get('lastdate')).getTime());
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

        comparator: 'timestamp',

        parse: function(ret) {
            var msgs = ret.chatmessages;

            // Fill in the users - each message has the user object below it for our convenience, even though the server
            // returns them in a separate object for bandwidth reasons.
            //
            // If the user is us, we remove it.
            var myid = Iznik.Session.get('me').id;

            _.each(msgs, function(msg, index, list) {
                if (msg.userid != myid) {
                    msg.user = ret.chatusers[msg.userid];
                }
            });

            return(msgs);
        }
    });
});