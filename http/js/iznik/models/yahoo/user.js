define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
        Iznik.Models.Yahoo.User = Iznik.Model.extend({
        url: function() {
            // Yahoo's client usually puts e.g. memberType=CONFIRMED in, but by omitting this we conveniently search
            // all members, including pending, which is actually what we want.
            var url = YAHOOAPI + "search/groups/" + this.get('group') +
                    "/members?start=1&count=1&sortBy=name&sortOrder=asc&query=" +
                    this.get('email') + "&chrome=raw";
            return(url);
        },

        parse: function(ret, options) {
            if (ret.hasOwnProperty('ygData') &&
                    ret.ygData.hasOwnProperty('members') &&
                    ret.ygData.members.length == 1) {
                // We want our own attributes (e.g. groupid) combined with Yahoo's.
                var obj = _.extend(this.toJSON2(), ret.ygData.members[0]);
                return(obj);
            }
        },

        remove: function(crumb) {
            var self = this;

            if (self.get('userId')) {
                console.log("Remove member", self.get('userId'), crumb);

                if (self.get('moderatorStatus')) {
                    // Don't remove mod.  Pretend we did to zap the work.
                    console.error("Refused to remove mod/owner", self);
                    self.trigger('removesucceeded');
                } else {
                    var data = [{
                        userId: self.get('userId')
                    }];

                    new majax({
                        type: "DELETE",
                        url: YAHOOAPIv2 + "groups/" + this.get('group') + "/members?gapi_crumb=" + crumb + "&members=" + encodeURIComponent(JSON.stringify(data)),
                        data: data,
                        success: function (ret) {
                            console.log("Delete ret", ret);
                            console.log("Type", typeof ret);
                            console.log("data?", ret.hasOwnProperty('ygData'));
                            console.log("passed?", ret.hasOwnProperty('ygData') &&
                                ret.ygData.hasOwnProperty('numPassed'));
                            if (ret.hasOwnProperty('ygData') &&
                                ret.ygData.hasOwnProperty('numPassed')) {
                                // If the delete worked, numPassed == 1.
                                if (ret.ygData.numPassed == 1) {
                                    console.log("Succeeded");
                                    self.trigger('removesucceeded');
                                } else {
                                    // If we get a status of NOT SUBSCRIBED then the member is no longer on the group - which
                                    // means this remove is complete.
                                    if (ret.ygData.hasOwnProperty('members') &&
                                        ret.ygData.members.length == 1 &&
                                        ret.ygData.members[0].hasOwnProperty('status') &&
                                        ret.ygData.members[0].status == 'NOT_SUBSCRIBED') {
                                        console.log("Succeeded as gone");
                                        self.trigger('removesucceeded');
                                    } else if (ret.ygData.hasOwnProperty('members') &&
                                            ret.ygData.members.length == 1 &&
                                            ret.ygData.members[0].hasOwnProperty('status') &&
                                            ret.ygData.members[0].status == 'UNAUTHORIZED') {
                                        console.log("Unauthorized, trigger");
                                        self.trigger('removeprohibited');
                                    } else {
                                        console.log("Failed to remove");
                                        self.trigger('removefailed');
                                    }
                                }
                            } else {
                                console.log("DELETE failed", ret);
                                self.trigger('removefailed');
                            }
                        }, error: function(a,b,c) {
                            console.log("DELETE error", a, b, c);
                            if (b == 'Forbidden') {
                                self.trigger('removeprohibited');
                            } else {
                                self.trigger('removefailed');
                            }
                        }
                    });
                }
            } else {
                // The model doesn't have a user id, which means we didn't find it on Yahoo.
                self.trigger('removesucceeded');
            }
        },

        ban: function(crumb) {
            var self = this;

            if (self.get('moderatorStatus')) {
                console.error("Refused to ban mod/owner", self);
            } else {
                var members = [
                    {
                        userId: self.get('userId'),
                        subscriptionStatus: 'BANNED'
                    }
                ];

                console.log("Ban", JSON.stringify(members));

                new majax({
                    type: "PUT",
                    url: YAHOOAPI + "groups/" + this.get('group') + "/members?gapi_crumb=" + crumb,
                    data: {
                        members: JSON.stringify(members)
                    },
                    success: function (ret) {
                        console.log("Ban returned", ret);
                        if (ret.hasOwnProperty('ygData') &&
                            ret.ygData.hasOwnProperty('numPassed')) {
                            // If the ban worked, numPassed == 1.  If the user is no longer on the group that works for us
                            // too.
                            if (ret.ygData.numPassed == 1 || ret.ygData.members[0].status == 'INVALID_ADDRESS') {
                                self.trigger('bansucceeded');
                            } else if (ret.ygData.numPassed == 1 || ret.ygData.members[0].status == 'FAILED') {
                                // Probably we don't have Ban rights.
                                console.log("Prohibited?");
                                self.trigger('banprohibited');
                            } else {
                                self.trigger('banfailed');
                            }
                        } else {
                            self.trigger('banfailed');
                        }
                    }, error: function (a, b, c) {
                        console.log("DELETE error", a, b, c);
                        if (b == 'Forbidden') {
                            self.trigger('banprohibited');
                        } else {
                            self.trigger('banfailed');
                        }
                    }
                });
            }
        },

        changeAttr: function(attr, val) {
            var self = this;

            function getCrumb(ret) {
                var match = /GROUPS.YG_CRUMB = "(.*)"/.exec(ret);

                if (match) {
                    // Got a crumb.
                    self.crumb = match[1];

                    var members = [
                        {}
                    ];
                    members[0]["userId"] = self.get('userId');
                    members[0][attr] = val;

                    new majax({
                        type: "PUT",
                        url: YAHOOAPI + 'groups/' + self.get('group') + "/members?gapi_crumb=" + self.crumb,
                        data: {
                            members: JSON.stringify(members)
                        }, success: function (ret) {
                            // Fetch it.  That's an easy way of double-checking whether it worked; if not
                            // then the value will be different, triggering a change event and hence a
                            // a re-render of the view.
                            var worked = ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('members') &&
                                    ret.ygData.members.length == 1 && ret.ygData.members[0].hasOwnProperty('status') &&
                                    ret.ygData.members[0].status == 'SUCCESSFUL';
                            self.fetch().then(function () {
                                self.trigger('completed', worked);
                            });
                        }, error: function (request, status, error) {
                            // Couldn't make the change. Reset to old value.  This will trigger a change event and
                            // hence a re-render of any relevant view.
                            console.log("PUT failed", status, error);
                            self.set(attr, self.previous(attr));
                            self.trigger('completed', false);
                        }
                    });
                } else {
                    var match = /window.location.href = "(.*)"/.exec(ret);

                    if (match) {
                        var url = match[1];
                        $.ajax({
                            type: "GET",
                            url: url,
                            success: getCrumb,
                            error: function (request, status, error) {
                                // Couldn't get a crumb. Reset to old value.  This will trigger a change event and
                                // hence a re-render of any relevant view.
                                console.log("getCrumb failed", status, error);
                                self.set(attr, self.previous(attr));
                                self.trigger('completed', true);
                            }
                        });
                    }
                }
            }

            $.ajax({
                type: "GET",
                url: "https://groups.yahoo.com/neo/groups/" + self.get('group') + "/members/all?" + Math.random(),
                success: getCrumb,
                error: function (request, status, error) {
                    // Couldn't get a crumb. Reset to old value.  This will trigger a change event and
                    // hance a re-render of any relevant view.
                    self.set(attr, self.previous(attr));
                    self.trigger('completed', true);
                }
            });
        },

        // We make Yahoo changes via the server.  They will then come back to us as plugin requests, which we
        // will act on, and update the model.
        //
        // This seems convoluted but
        // a) it allows us to log the change
        // b) it allows us to retain the change even if the user navigates away before we've
        // managed to persuade Yahoo to do it
        // c) it gives common code with other cases where changes are triggered from the server side
        // rather than the client.
        changeDelivery: function(val) {
            $.ajax({
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                url: API + '/user',
                data: {
                    groupid: this.get('groupid'),
                    email: this.get('email'),
                    yahooDeliveryType: val
                }, success: function(ret) {
                    IznikPlugin.checkPluginStatus();
                }
            });
        },

        changePostingStatus: function(val) {
            $.ajax({
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                url: API + '/user',
                data: {
                    groupid: this.get('groupid'),
                    email: this.get('email'),
                    yahooPostingStatus: val,
                    ourPostingStatus: val,
                }, success: function(ret) {
                    IznikPlugin.checkPluginStatus();
                }
            });
        }
    });

    // We maintain a singleton collection of users.  This allows us to use the same model across multiple views, which
    // means that when a change is made to the model, the relevant views can pick it up.
    Iznik.Collections.Yahoo.Users = Iznik.Collection.extend({
        findUser: function(parms) {
            var mod = this.findWhere(parms);

            if (!mod) {
                mod = new Iznik.Models.Yahoo.User(parms);
                this.add(mod);
            }
            return(mod)
        }
    });

    IznikYahooUsers = new Iznik.Collections.Yahoo.Users();
});