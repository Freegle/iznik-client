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

    hold: function() {
        var self = this;

        $.ajax({
            type: 'POST',
            url: API + 'user/' + self.get('id'),
            data: {
                id: self.get('id'),
                action: 'Hold'
            }, success: function(ret) {
                self.set('heldby', Iznik.Session.get('me'));
            }
        })
    },

    release: function() {
        var self = this;

        $.ajax({
            type: 'POST',
            url: API + 'user/' + self.get('id'),
            data: {
                id: self.get('id'),
                action: 'Release'
            }, success: function(ret) {
                self.set('heldby', null);
            }
        })
    },

    approve: function() {
        var self = this;
        // We approve the message on all groups.  Future enhancement?
        _.each(self.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'user/' + self.get('id'),
                data: {
                    id: self.get('id'),
                    groupid: group.id,
                    action: 'Approve'
                }, success: function(ret) {
                    self.trigger('approved');
                }
            })
        });
    },

    reject: function(subject, body, stdmsgid) {
        // We reject the message on all groups.  Future enhancement?
        var self= this;
        _.each(self.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'user/' + self.get('id'),
                data: {
                    id: self.get('id'),
                    groupid: group.id,
                    action: 'Reject',
                    subject: subject,
                    stdmsgid: stdmsgid,
                    body: body
                }, success: function(ret) {
                    self.trigger('rejected');
                }
            })
        });
    },

    reply: function(subject, body, stdmsgid) {
        var self = this;

        $.ajax({
            type: 'POST',
            url: API + 'user/' + self.get('id'),
            data: {
                action: 'Reply',
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