Iznik.Models.Message = IznikModel.extend({

});

Iznik.Collections.Message = IznikCollection.extend({
    model: Iznik.Models.Message,

    initialize: function (options) {
        this.options = options;

        // Use a comparator to show in most recent first order
        this.comparator = function(a) {
            return((new Date(a.get('arrival'))).getTime());
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
                groups.push(ret.groups[group]);
            });

            message.groups = groups;
        });

        return ret.messages;
    }
});