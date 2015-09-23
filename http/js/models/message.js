Iznik.Models.Message = IznikModel.extend({

});

Iznik.Collections.Message = IznikCollection.extend({
    model: Iznik.Models.Message,

    initialize: function (options) {
        this.options = options;

        // Use a comparator to show in most recent first order
        this.comparator = function(a) {
            console.log("Compare", a);
            return((new Date(a.get('arrival'))).getTime());
        }
    },

    url: API + 'messages',

    parse: function(ret) {
        var self = this;
        var groups = ret.groups;

        // Fill in the groups
        _.each(ret.messages, function(message, index, list) {
            message.group = groups[message.groupid];
        });

        return ret.messages;
    }
});