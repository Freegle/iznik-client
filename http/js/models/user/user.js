// Terminology:
// - A user corresponds to a real person, or someone pretending to be; that's in here
// - A member is the user's presence on a specific group; that's in membership.js

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
    },

    reply: function(subject, body, stdmsgid) {
        var self = this;

        $.ajax({
            type: 'POST',
            url: API + 'user/' + self.get('id'),
            data: {
                subject: subject,
                body: body,
                stdmsgid: stdmsgid,
                groupid: self.get('groupid')
            }, success: function(ret) {
                self.trigger('replied');
            }
        });
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
