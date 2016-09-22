define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Models.Message = Iznik.Model.extend({
        url: function() {
            return (API + 'message/' + this.get('id'));
        },

        hold: function() {
            var self = this;

            $.ajax({
                type: 'POST',
                url: API + 'message',
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
                url: API + 'message',
                data: {
                    id: self.get('id'),
                    action: 'Release'
                }, success: function(ret) {
                    self.set('heldby', null);
                }
            })
        },

        approve: function(subject, body, stdmsgid) {
            var self = this;
            // We approve the message on all groups.  Future enhancement?
            _.each(self.get('groups'), function(group, index, list) {
                $.ajax({
                    type: 'POST',
                    url: API + 'message',
                    data: {
                        id: self.get('id'),
                        groupid: group.id,
                        action: 'Approve',
                        subject: subject,
                        stdmsgid: stdmsgid,
                        body: body
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
                    url: API + 'message',
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
            // We mail on only one group, otherwise the user will get multiple copies.
            var self = this;
            var group = _.first(self.get('groups'));

            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: self.get('id'),
                    groupid: group.id,
                    action: 'Reply',
                    subject: subject,
                    body: body,
                    stdmsgid: stdmsgid
                }, success: function(ret) {
                    console.log("trigger replied", self);
                    self.trigger('replied');
                }
            });
        },

        delete: function(subject, body, stdmsgid) {
            var self = this;

            // We delete the message on all groups.  Future enhancement?
            _.each(self.get('groups'), function(group, index, list) {
                $.ajax({
                    type: 'POST',
                    url: API + 'message',
                    data: {
                        id: self.get('id'),
                        groupid: group.id,
                        action: 'Delete',
                        subject: subject,
                        body: body,
                        stdmsgid: stdmsgid
                    }, success: function(ret) {
                        self.trigger('deleted');
                    }
                })
            });
        },

        edit: function(subject, textbody, htmlbody) {
            // We need a closure to guard the parameters.

            function closure(self, subject, textbody, htmlbody) {
                return(function() {
                    // Editing is complex.
                    // - We need a crumb from Yahoo to allow us to do it.
                    // - We have to construct the bodyparts for the full message.  We have to use the same msgPartId as
                    //   currently exists in the message (otherwise the edit fails), so we need to find what that is.
                    // - We don't know an API call to get the message parts for a specific message, so we need to fetch the
                    //   pending messages, and find the one we're interested in first.
                    // - Once we've constructed the edit, we post it off to Yahoo and hope for the best
                    // - We don't get a return code, exactly, but if it worked we get the message back again
                    // - We also need to update the copy on our server.
                    _.each(self.get('groups'), function(group, index, list) {
                        var groupname = group.nameshort;

                        $.ajax({
                            type: 'GET',
                            url: YAHOOAPI + 'groups/' + group.nameshort + "/pending/messages/1/parts?start=1&count=100&chrome=raw",
                            async: false,
                            context: self,
                            success: function(ret) {
                                var found = false;
                                var self = this;
                                if (ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('pendingMessages')) {
                                    _.each(ret.ygData.pendingMessages, function (msg) {
                                        if (msg.msgId == group.yahoopendingid) {
                                            found = true;

                                            self.parts = [];

                                            // We might be passed both an HTML and a text bodypart.  In this case we drop the
                                            // text one when editing on Yahoo.  This is because Yahoo only seems to handle having
                                            // one of them, and tends to convert text/plain messages to text/html when you edit -
                                            // so we follow suit.
                                            if (htmlbody) {
                                                self.parts.push({
                                                    msgPartId: msg.messageParts[0].msgPartId,
                                                    contentType: 'text/html',
                                                    textContent: htmlbody
                                                });
                                            } else if (textbody) {
                                                // Convert to HTML
                                                self.parts.push({
                                                    msgPartId: msg.messageParts[0].msgPartId,
                                                    contentType: 'text/html',
                                                    textContent: '<p>' + textbody + '</p>'
                                                });
                                            }

                                            self.data = {
                                                subject: subject,
                                                messageParts: self.parts
                                            }

                                            // Get a crumb from Yahoo to do the work.
                                            function getCrumb(self) {
                                                return(function(ret) {
                                                    var match = /GROUPS.YG_CRUMB = "(.*)"/.exec(ret);

                                                    if (match) {
                                                        self.crumb = match[1];
                                                        new majax({
                                                            type: "POST",
                                                            url: YAHOOAPI + 'groups/' + groupname + "/pending/messages/" + group.yahoopendingid + "?gapi_crumb=" + self.crumb,
                                                            data: {
                                                                messageParts: JSON.stringify(self.data),
                                                                action: 'SAVE'
                                                            },
                                                            success: function (ret) {
                                                                if (ret.hasOwnProperty('ygData') &&
                                                                    ret.ygData.hasOwnProperty('msgId') &&
                                                                    ret.ygData.msgId == group.yahoopendingid) {
                                                                    // The edit on Yahoo worked.  Miracles never cease.  Now update the copy on our server.
                                                                    //
                                                                    // We also drop the text part here too, because the server will (in its absence)
                                                                    // convert the HTML variant to text - and do a better job than we may have done on the client.
                                                                    self.data2 = {
                                                                        id: self.get('id'),
                                                                        subject: subject
                                                                    };

                                                                    if (htmlbody) {
                                                                        self.data2.htmlbody = htmlbody;
                                                                    } else {
                                                                        self.data2.textbody = textbody;
                                                                    }

                                                                    $.ajax({
                                                                        type: 'POST',
                                                                        headers: {
                                                                            'X-HTTP-Method-Override': 'PUT',
                                                                        },
                                                                        url: API + 'message',
                                                                        data: self.data2,
                                                                        success: function (ret) {
                                                                            console.log("Server edit returned", ret);
                                                                            if (ret.ret == 0) {
                                                                                // Make sure we're up to date.
                                                                                console.log("Fetch");
                                                                                self.fetch({
                                                                                    data: {
                                                                                        messagehistory: true
                                                                                    }
                                                                                }).then(function () {
                                                                                    console.log("Fetched", self);
                                                                                    self.trigger('editsucceeded');
                                                                                });
                                                                            } else {
                                                                                self.trigger('editfailed');
                                                                            }
                                                                        }, error: function (request, status, error) {
                                                                            console.log("Server edit failed", request, status, error)
                                                                            self.trigger('editfailed');
                                                                        }
                                                                    })
                                                                } else {
                                                                    self.trigger('editfailed');
                                                                }
                                                            }, error: function (request, status, error) {
                                                                console.log("Edit failed", request, status, error)
                                                                self.trigger('editfailed');
                                                            }
                                                        });
                                                    } else {
                                                        var match = /window.location.href = "(.*)"/.exec(ret);

                                                        if (match) {
                                                            var url = match[1];
                                                            $.ajax({
                                                                type: "GET",
                                                                url: url,
                                                                success: getCrumb(self),
                                                                error: function (request, status, error) {
                                                                    console.log("Get crumb failed");
                                                                    self.trigger('editfailed');
                                                                }
                                                            });
                                                        }
                                                    }
                                                });
                                            }

                                            // Do this synchronously to increase the chance that the crumb will still be valid
                                            // when we use it.  If we have background work happening the crumb might be invalidated
                                            // under our feet.
                                            $.ajax({
                                                type: "GET",
                                                url: "https://groups.yahoo.com/neo/groups/" + groupname + "/management/pendingmessages?" + Math.random(),
                                                success: getCrumb(self),
                                                async: false,
                                                error: function (request, status, error) {
                                                    console.log("Get crumb failed");
                                                    self.trigger('editfailed');
                                                }
                                            });
                                        }
                                    });
                                }

                                if (!found) {
                                    console.log("Pending message not found");
                                    self.trigger('editfailed');
                                }
                            }, error: function (request, status, error) {
                                console.log("Get pending failed", request, status, error)
                                self.trigger('editfailed');
                            }
                        })
                    });
                });
            }

            closure(this, subject, textbody, htmlbody)();
        },

        parse: function(ret) {
            // We might either be called from a collection, where the message is at the top level, or
            // from getting an individual message, where it's not.  In the latter case we need to fill in
            // the groups; in the former, it's done in the collection code below.
            var message;

            if (ret.hasOwnProperty('message')) {
                message = ret.message;

                // Fill in the groups - each message has the group object below it for our convenience, even though the server
                // returns them in a separate object for bandwidth reasons.
                var groups = [];
                _.each(message.groups, function(group) {
                    var groupdata = ret.groups[group.groupid];
                    groups.push(_.extend({}, groupdata, group));
                });

                message.groups = groups;
            } else {
                message = ret;
            }

            return(message);
        },

        stripGumf: function(property) {
            // We have the same function in PHP in Message.php; keep them in sync.
            var text = this.get(property);

            if (text) {
                // console.log("Strip photo", text);
                // Strip photo links - we should have those as attachments.
                text = text.replace(/You can see a photo[\s\S]*?jpg/, '');
                text = text.replace(/Check out the pictures[\s\S]*?https:\/\/trashnothing[\s\S]*?pics\/\d*/, '');
                text = text.replace(/You can see photos here[\s\S]*jpg/m, '');
                text = text.replace(/https:\/\/direct.*jpg/m, '');

                // FOPs
                text = text.replace(/Fair Offer Policy applies \(see https:\/\/[\s\S]*\)/, '');
                text = text.replace(/Fair Offer Policy:[\s\S]*?reply./, '');

                // App footer
                text = text.replace(/Freegle app.*[0-9]$/m, '');

                // Footers
                text = text.replace(/--[\s\S]*Get Freegling[\s\S]*book/m, '');
                text = text.replace(/--[\s\S]*Get Freegling[\s\S]*org[\s\S]*?<\/a>/m, '');
                text = text.replace(/This message was sent via Freegle Direct[\s\S]*/m, '');
                text = text.replace(/\[Non-text portions of this message have been removed\]/m, '');
                text = text.replace(/^--$[\s\S]*/m, '');

                // Redundant line breaks
                text = text.replace(/(?:(?:\r\n|\r|\n)\s*){2}/m, "\n\n");

                // Duff text added by Yahoo Mail app
                text = text.replace('blockquote, div.yahoo_quoted { margin-left: 0 !important; border-left:1px #715FFA solid !important; padding-left:1ex !important; background-color:white !important; }', '');

                text = text.trim();
                // console.log("Stripped photo", text);
            } else {
                text = '';
            }

            this.set(property, text);
        }
    });

    Iznik.Collections.Message = Iznik.Collection.extend({
        model: Iznik.Models.Message,
        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        comparator: function(a, b) {
            // Use a comparator to show in most recent first order
            var ret = (new Date(b.get('arrival'))).getTime() - (new Date(a.get('arrival'))).getTime();
            return(ret);
        },

        url: function() {
            // The URL changes based on whether we're wanting a specific group, collection, mod groups only, or
            // group type (e.g. just Freegle).
            //
            // If we are in the user interface we only ever want OFFERs/WANTEDs.  The TAKEN/RECEIVED messages
            // are returned attached to those, so we don't need to see them separately.
            var url = API + 'messages?' +
                (this.options.groupid > 0 ? ("groupid=" + this.options.groupid + "&") : '') +
                'collection=' + this.options.collection +
                '&modtools=' + this.options.modtools +
                (this.options.modtools ? '' : '&types[]=Offer&types[]=Wanted') +
                (this.options.type ? ('&grouptype=' + this.options.type) : '');
            // console.log("Collection url", url);
            return (url);
        },

        parse: function(ret) {
            var self = this;

            // Save off the return in case we need any info from it, e.g. context for searches.
            self.ret = ret;

            if (ret.hasOwnProperty('messages')) {
                // Fill in the groups - each message has the group object below it for our convenience, even though the server
                // returns them in a separate object for bandwidth reasons.
                _.each(ret.messages, function(message, index, list) {
                    var groups = [];
                    var arrival = 0;

                    _.each(message.groups, function(group, index2, list2) {
                        var groupdata = ret.groups[group.groupid];

                        // Arrival at the message level shows when it first hit the platform.  But in this context
                        // we are interested in showing the latest time it was posted on any of the groups which
                        // we are looking at.
                        var arrivalepoch = (new Date(group.arrival)).getTime();
                        arrival = Math.max(arrivalepoch, arrival);

                        // Need to know whether it's our message when rendering the group info.
                        group.mine = message.mine;

                        if (message.type == 'Offer' || message.type == 'Wanted') {
                            // Work out when we can repost.
                            group.autorepostallowed = false;
                            var settings = groupdata.settings;

                            if (settings.hasOwnProperty('reposts')) {
                                var interval = message.type == 'Offer' ? settings.reposts.offer : settings.reposts.wanted;
                                var max = settings.reposts.max;

                                if (group.autoreposts < max) {
                                    group.autorepostallowed = true;
                                    var m = new moment(group.arrival);
                                    m = m.add(interval, 'days');
                                    group.autorepostat = m.format();
                                }
                            }
                        }

                        groups.push(_.extend({}, groupdata, group));
                    });

                    message.arrival = (new Date(arrival)).toISOString();
                    message.groups = groups;
                });

                return ret.messages;
            } else {
                return(null);
            }
        }
    });

    Iznik.Collections.Messages.Search = Iznik.Collections.Message.extend({
        url: function() {
            var url;
            if (this.options.searchmess) {
                url = API + 'messages/searchmess/' + encodeURIComponent(this.options.searchmess);
            } else {
                url = API + 'messages/searchmemb/' + encodeURIComponent(this.options.searchmemb);
            }

            return(url);
        }
    });

    Iznik.Collections.Messages.SearchAll = Iznik.Collections.Message.extend({
        url: function() {
            url = API + 'messages/searchall/' + encodeURIComponent(this.options.searchmess);
            return(url);
        }
    });

    // Search sorted by closeness.
    Iznik.Collections.Messages.GeoSearch = Iznik.Collections.Messages.Search.extend({
        comparator: function(a, b) {
            if (this.options.nearlocation) {
                var mylat = this.options.nearlocation.lat;
                var mylng = this.options.nearlocation.lng;

                // Messages might have an area, or (if we have rights) a location.
                var aloc = a.get('location') ? a.get('location') : a.get('area');
                var bloc = b.get('location') ? b.get('location') : b.get('area');

                // Some messages don't have locations.  Assume they're far away.
                if (!aloc) {
                    return(1)
                } else if (!bloc) {
                    return(-1);
                }

                var adist = haversineDistance([mylat, mylng], [aloc.lat, aloc.lng], true);
                var bdist = haversineDistance([mylat, mylng], [bloc.lat, bloc.lng], true);
                a.set('distance', Math.round(adist, 1));
                b.set('distance', Math.round(bdist, 1));

                return(adist - bdist);
            }
        }
    });
});