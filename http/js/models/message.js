Iznik.Models.Message = IznikModel.extend({
    url: function() {
        return (API + 'message/' + this.get('id'));
    },

    parse: function(ret) {
        // We might either be called from a collection, where the message is at the top level, or
        // from getting an individual message, where it's not.
        if (ret.hasOwnProperty('message')) {
            return(ret.message);
        } else {
            return(ret);
        }
    }
});

Iznik.Collections.Message = IznikCollection.extend({
    model: Iznik.Models.Message,

    initialize: function (options) {
        this.options = options;

        // Use a comparator to show in most recent first order
        this.comparator = function(a, b) {
            var ret = (new Date(b.get('date'))).getTime() - (new Date(a.get('date'))).getTime();
            return(ret);
        }
    },

    url: API + 'messages',

    parse: function(ret) {
        var self = this;

        // Fill in the groups - each message has the group object below it for our convenience, even though the server
        // returns them in a separate object for bandwidth reasons.
        _.each(ret.messages, function(message, index, list) {
            var groups = [];
            _.each(message.groups, function(group, index2, list2) {
                var groupdata = ret.groups[group.groupid];
                groups.push(_.extend([], groupdata, group));
            });

            message.groups = groups;
        });

        return ret.messages;
    }
});