define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    // Terminology:
    // - A user corresponds to a real person, or someone pretending to be; that's in here
    // - A member is the user's presence on a specific group; that's in membership.js

    Iznik.Models.Chat = Iznik.Model.extend({
        urlRoot: API + '/chat',

        parse: function (ret) {
            // We might either be called from a collection, where the chat is at the top level, or
            // from getting an individual chat, where it's not.
            if (ret.hasOwnProperty('chat')) {
                return (ret.chat);
            } else {
                return (ret);
            }
        }
    });

    Iznik.Collections.Chat = Iznik.Collection.extend({
        model: Iznik.Models.Chat
    });

    Iznik.Models.Chat.Message = Iznik.Model.extend({
        urlRoot: function() {
            return(API + '/chat' + this.options.chatid + '/message')
        },

        parse: function (ret) {
            // We might either be called from a collection, where the message is at the top level, or
            // from getting an individual message, where it's not.
            if (ret.hasOwnProperty('message')) {
                return (ret.message);
            } else {
                return (ret);
            }
        }
    });

    Iznik.Collections.Chat.Message = Iznik.Collection.extend({
        model: Iznik.Models.Chat.Message,
        comparator: 'timestamp'
    });
});