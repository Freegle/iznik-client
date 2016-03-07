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

Iznik.Models.ModTools.User.Comment = IznikModel.extend({
    urlRoot: function() {
        return(API + 'comment');
    },

    parse: function(ret) {
        // We might either be called from a collection, where the comment is at the top level, or
        // from getting an individual comment, where it's not.
        if (ret.hasOwnProperty('comment')) {
            return(ret.comment);
        } else {
            return(ret);
        }
    },

    edit: function(user1, user2, user3, user4, user5, user6, user7, user8, user9, user10, user11) {
        var self = this;

        $.ajax({
            type: 'POST',
            url: API + 'comment/' + self.get('id'),
            data: {
                id: self.get('id'),
                user1: user1,
                user2: user2,
                user3: user3,
                user4: user4,
                user5: user5,
                user6: user6,
                user7: user7,
                user8: user8,
                user9: user9,
                user10: user10,
                user11: user11
            }, success: function(ret) {
                self.trigger('edited');
            }
        });
    }
});