// Terminology:
// - A user corresponds to a real person, or someone pretending to be
// - A member is the user's presence on a specific group.

Iznik.Models.ModTools.User = IznikModel.extend({
    urlRoot: API + '/user',

    parse: function(ret) {
        // We might either be called from a collection, where the user is at the top level, or
        // from getting an individual user, where it's not.
        if (ret.hasOwnProperty('user')) {
            return(ret.user);
        } else {
            return(ret);
        }
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

Iznik.Collections.Members = IznikCollection.extend({
    url: function() {
        return(API + 'group/' + this.options.groupid + '?members=TRUE');
    },

    model: Iznik.Models.ModTools.User,

    initialize: function (models, options) {
        this.options = options;

        // Use a comparator to show in most recent first order
        this.comparator = 'email'
    },

    parse: function(ret) {
        // Save off the return in case we need any info from it, e.g. context for searches.
        this.ret = ret;

        return(ret.group.members);
    }
});