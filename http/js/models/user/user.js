Iznik.Models.ModTools.User = IznikModel.extend({
    urlRoot: API + '/user',

    parse: function(ret) {
        return(ret.user);
    }
});

Iznik.Models.ModTools.User.MessageHistoryEntry = IznikModel.extend({});

Iznik.Collections.ModTools.MessageHistory = IznikCollection.extend({
    model: Iznik.Models.ModTools.User.MessageHistoryEntry,

    initialize: function (options) {
        this.options = options;

        // Use a comparator to show in most recent first order
        this.comparator = function(a) {
            return(-(new Date(a.get('arrival'))).getTime());
        }
    }
});