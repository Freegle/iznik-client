define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Notification = Iznik.Model.extend({
        urlRoot: API + 'notification',

        haveSeen: false,

        seen: function() {
            var self = this;

            // Only want to mark as seen once.
            if (self.haveSeen) {
                return resolvedPromise(self);
            } else {
                self.haveSeen = true;

                return($.ajax({
                    url: API + '/notification',
                    type: 'POST',
                    data: {
                        id: self.get('id'),
                        action: 'Seen'
                    }
                }));
            }
        },

        parse: function (ret) {
            if (ret.hasOwnProperty('notification')) {
                return (ret.notification);
            } else {
                return (ret);
            }
        }
    });

    Iznik.Collections.Notification = Iznik.Collection.extend({
        model: Iznik.Models.Notification,

        url: API + 'notification',

        comparator: function(a) {
            return(-(new Date(a.get('timestamp'))).getTime());
        },

        parse: function(ret) {
            var self = this;

            if (ret.hasOwnProperty('notifications')) {
                return ret.notifications;
            } else {
                return(null);
            }
        }
    });
});