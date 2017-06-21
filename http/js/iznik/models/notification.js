define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Notification = Iznik.Model.extend({
        urlRoot: API + 'notification',

        seen: function() {
            var self = this;

            return($.ajax({
                url: API + '/notification/' + self.get('id'),
                type: 'POST',
                data: {
                    action: 'Seen'
                }
            }));
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