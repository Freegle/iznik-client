// Terminology:
// - A user corresponds to a real person, or someone pretending to be; that's in user.js
// - A member is the user's presence on a specific group; that's in here

Iznik.Models.Membership = IznikModel.extend({
    url: function() {
        return (API + 'memberships/' + this.get('groupid') + '/' + this.get('userid'))
    },

    parse: function(ret) {
        return(ret.member);
    }
});

Iznik.Collections.Memberships = IznikCollection.extend({
    model: Iznik.Models.Membership,

    ret: null,

    initialize: function (models, options) {
        this.options = options;

        // Use a comparator to show in most recent first order
        this.comparator = function(a, b) {
            var ret = (new Date(b.get('joined'))).getTime() - (new Date(a.get('joined'))).getTime();
            return(ret);
        }
    },

    url: function() {
        return (API + 'memberships/' + this.options.groupid)
    },

    parse: function(ret) {
        return(ret.members);
    }
});