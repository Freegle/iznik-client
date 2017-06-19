define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Newsfeed= Iznik.Model.extend({
        urlRoot: API + 'newsfeed',

        love: function() {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'Love'
                }
            }));
        },

        unlove: function() {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'Unlove'
                }
            }));
        },

        parse: function (ret) {
            if (ret.hasOwnProperty('newsfeed')) {
                return (ret.newsfeed);
            } else {
                return (ret);
            }
        }
    });

    Iznik.Collections.Newsfeed = Iznik.Collection.extend({
        model: Iznik.Models.Newsfeed,

        url: API + 'newsfeed',

        comparator: function(a, b) {
            // Use a comparator to show in most recent first order
            var ret = b.get('id') - a.get('id');
            return(ret);
        },

        parse: function(ret) {
            var self = this;

            if (ret.hasOwnProperty('newsfeed')) {
                // Fill in the users - each item has the user object below it for our convenience, even though the server
                // returns them in a separate object for bandwidth reasons.
                _.each(ret.newsfeed, function(item, index, list) {
                    if (item.userid) {
                        item.user = ret.users[item.userid];
                    }

                    if (item.replies) {
                        _.each(item.replies, function(reply, index, list) {
                            if (reply.userid) {
                                reply.user = ret.users[item.userid];
                            }
                        });
                    }
                });

                return ret.newsfeed;
            } else {
                return(null);
            }
        }
    });

    Iznik.Collections.Replies = Iznik.Collections.Newsfeed.extend({
        comparator: 'id'
    });
});