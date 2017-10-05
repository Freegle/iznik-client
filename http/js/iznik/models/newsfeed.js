define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Newsfeed = Iznik.Model.extend({
        urlRoot: API + 'newsfeed',

        follow: function() {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'Follow'
                }
            }));
        },

        unfollow: function() {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'Unfollow'
                }
            }));
        },

        referToWanted: function() {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'ReferToWanted'
                }
            }));
        },

        referToOffer: function() {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'ReferToOffer'
                }
            }));
        },

        referToTaken: function() {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'ReferToTaken'
                }
            }));
        },

        attachToThread: function(attachto) {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'AttachToThread',
                    attachto: attachto
                }
            }));
        },

        referToReceived: function() {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'ReferToReceived'
                }
            }));
        },

        seen: function() {
            var self = this;

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: API + '/newsfeed/' + self.get('id'),
                    type: 'POST',
                    data: {
                        action: 'Seen'
                    }, success: function(ret) {
                        if (ret.ret === 0) {
                            self.set('seen', true);
                            resolve();
                        }
                    }
                })
            });

            return(p);
        },

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

        report: function(reason) {
            var self = this;

            return($.ajax({
                url: API + '/newsfeed/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'Report',
                    reason: reason
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

        parse: function(ret) {
            var self = this;

            if (ret.hasOwnProperty('newsfeed')) {
                // Save return used by infinite scroll.
                self.ret = ret;

                // Fill in the users - each item has the user object below it for our convenience, even though the server
                // returns them in a separate object for bandwidth reasons.
                _.each(ret.newsfeed, function(item, index, list) {
                    if (item.userid) {
                        item.user = ret.users[item.userid];
                    }

                    if (item.replies) {
                        _.each(item.replies, function(reply, index, list) {
                            if (reply.userid) {
                                reply.user = ret.users[reply.userid];
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