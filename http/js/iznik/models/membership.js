define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    // Terminology:
    // - A user corresponds to a real person, or someone pretending to be; that's in user.js
    // - A member is the user's presence on a specific group; that's in here

    Iznik.Models.Membership = Iznik.Model.extend({
        url: function() {
            var url = API + 'memberships/' + this.get('groupid') + '/' + this.get('userid');
            url = this.get('ban') ? (url + '?ban=true') : url;

            return (url);
        },

        parse: function(ret) {
            // We might be called either when parsing a collection or a single membership.
            return(ret.hasOwnProperty('member') ? ret.member : ret);
        },

        unbounce: function() {
            var self = this;

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    type: 'POST',
                    url: API + 'user',
                    data: {
                        id: self.get('userid'),
                        action: 'Unbounce'
                    }, success: function(ret) {
                        if (ret.ret === 0) {
                            resolve();
                        } else {
                            reject();
                        }
                    }, error: reject
                });
            });

            return(p);
        },

        hold: function() {
            var self = this;

            $.ajax({
                type: 'POST',
                url: API + 'memberships',
                data: {
                    userid: self.get('userid'),
                    groupid: self.get('groupid'),
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
                url: API + 'memberships',
                data: {
                    userid: self.get('userid'),
                    groupid: self.get('groupid'),
                    action: 'Release'
                }, success: function(ret) {
                    self.set('heldby', null);
                }
            })
        },

        approve: function() {
            var self = this;

            $.ajax({
                type: 'POST',
                url: API + 'memberships',
                data: {
                    userid: self.get('userid'),
                    groupid: self.get('groupid'),
                    action: 'Approve'
                }, success: function(ret) {
                    self.trigger('approved');
                }
            });
        },

        reject: function(subject, body, stdmsgid) {
            var self= this;

            $.ajax({
                type: 'POST',
                url: API + 'memberships',
                data: {
                    userid: self.get('userid'),
                    groupid: self.get('groupid'),
                    action: 'Reject',
                    subject: subject,
                    stdmsgid: stdmsgid,
                    body: body
                }, success: function(ret) {
                    self.trigger('rejected');
                }
            });
        },

        reply: function(subject, body, stdmsgid) {
            var self = this;

            $.ajax({
                type: 'POST',
                url: API + 'memberships',
                data: {
                    userid:  self.get('userid'),
                    groupid: self.get('groupid'),
                    action: self.collection.options.collection == 'Approved' ? 'Leave Approved Member' : 'Leave Member',
                    subject: subject,
                    body: body,
                    stdmsgid: stdmsgid,
                    groupid: self.get('groupid')
                }, success: function(ret) {
                    self.trigger('replied');
                }
            });
        },

        delete: function() {
            var self = this;

            $.ajax({
                type: 'POST',
                url: API + 'memberships',
                data: {
                    userid: self.get('userid'),
                    groupid: self.get('groupid'),
                    action: 'Delete'
                }, success: function(ret) {
                    self.trigger('deleted');
                }
            })
        }
    });

    Iznik.Collections.Members = Iznik.Collection.extend({
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
            return (API + 'memberships/' + (this.options.groupid > 0 ? this.options.groupid : '') + '?collection=' + this.options.collection)
        },

        parse: function(ret) {
            // Save off the return in case we need any info from it, e.g. context for searches.
            this.ret = ret;

            // Link in the groups.
            _.each(ret.members, function(member) {
                if (ret.groups[member.groupid]) {
                    member.group = ret.groups[member.groupid];
                }
            })  
            return(ret.members);
        }
    });

    Iznik.Collections.Members.Search = Iznik.Collections.Members.extend({
        url: function() {
            return(API + 'memberships/' + presdef('groupid', this.options, '') + '?search=' + encodeURIComponent(this.options.search) + '&collection=' + this.options.collection);
        }
    });

    Iznik.Collections.Members.Happiness = Iznik.Collection.extend({
        model: Iznik.Model,

        ret: null,

        initialize: function (models, options) {
            this.options = options;

            // Use a comparator to show in most recent first order
            this.comparator = function(a, b) {
                var ret = (new Date(b.get('timestamp'))).getTime() - (new Date(a.get('timestamp'))).getTime();
                return(ret);
            }
        },

        url: function() {
            return (API + 'memberships/' + (this.options.groupid > 0 ? this.options.groupid : '') + '?collection=Happiness')
        },

        parse: function(ret) {
            // Save off the return in case we need any info from it, e.g. context for searches.
            this.ret = ret;

            // Link in the groups.
            _.each(ret.members, function(member) {
                if (ret.groups[member.groupid]) {
                    member.group = ret.groups[member.groupid];
                }
            });

            return(ret.members);
        }
    });

    Iznik.Models.Membership.Story = Iznik.Model.extend({
        urlRoot: API + 'stories',

        dontUseForPublicity: function() {
            // By marking it reviewed and not public, it will not be visible.
            var p = $.ajax({
                url: API + 'stories',
                type: 'PATCH',
                data: {
                    id: this.get('id'),
                    reviewed: 1,
                    public: 0
                }
            });

            return(p);
        },

        useForPublicity: function() {
            // By marking it public (if it was) and reviewed, it becomes visible.
            var p = $.ajax({
                url: API + 'stories',
                type: 'PATCH',
                data: {
                    id: this.get('id'),
                    reviewed: 1,
                    public: this.get('public')
                }
            });

            return(p);
        },

        dontUseForNewsletter: function() {
            var p = $.ajax({
                url: API + 'stories',
                type: 'PATCH',
                data: {
                    id: this.get('id'),
                    newsletterreviewed: 1,
                    newsletter: 0
                }
            });

            return(p);
        },

        useForNewsletter: function() {
            var p = $.ajax({
                url: API + 'stories',
                type: 'PATCH',
                data: {
                    id: this.get('id'),
                    newsletterreviewed: 1,
                    newsletter: 1
                }
            });

            return(p);
        },

        like: function() {
            var p = $.ajax({
                url: API + 'stories',
                type: 'POST',
                data: {
                    id: this.get('id'),
                    action: 'Like'
                }
            });

            return(p);
        },

        unlike: function() {
            var p = $.ajax({
                url: API + 'stories',
                type: 'POST',
                data: {
                    id: this.get('id'),
                    action: 'Unlike'
                }
            });

            return(p);
        },

        parse: function(ret) {
            if (ret.hasOwnProperty('story') && ret.story.hasOwnProperty('id')) {
                return(ret.story);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.Members.Stories = Iznik.Collection.extend({
        model: Iznik.Models.Membership.Story,

        ret: null,

        initialize: function (models, options) {
            this.options = options;

            // Use a comparator to show in most recent first order
            this.comparator = function(a, b) {
                var ret = (new Date(b.get('timestamp'))).getTime() - (new Date(a.get('timestamp'))).getTime();
                return(ret);
            }
        },

        url: function() {
            return (API + 'stories?' + (this.options && this.options.groupid > 0 ? ('groupid=' + this.options.groupid) : ''))
        },

        parse: function(ret) {
            this.ret = ret;
            return(ret.stories);
        }
    });
});