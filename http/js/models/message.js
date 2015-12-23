Iznik.Models.Message = IznikModel.extend({
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

    approve: function() {
        var self = this;
        // We approve the message on all groups.  Future enhancement?
        _.each(self.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
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

    reject: function(subject, body) {
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
                    body: body
                }, success: function(ret) {
                    self.trigger('rejected');
                }
            })
        });
    },

    reply: function(subject, body) {
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
                body: body
            }, success: function(ret) {
                self.trigger('replied');
            }
        });
    },

    delete: function(subject, body) {
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
                    body: body
                }, success: function(ret) {
                    self.trigger('deleted');
                }
            })
        });
    },

    edit: function(subject, textbody, htmlbody) {
        var self = this;
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
            console.log("Edit on", group, self);
            var groupname = group.nameshort;

            $.ajax({
                type: 'GET',
                url: YAHOOAPI + 'groups/' + group.nameshort + "/pending/messages/1/parts?start=1&count=100&chrome=raw",
                success: function(ret) {
                    console.log("Got pending", ret);
                    var found = false;
                    if (ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('pendingMessages')) {
                        _.each(ret.ygData.pendingMessages, function (msg) {
                            console.log("Check message", msg);
                            if (msg.msgId == group.yahoopendingid) {
                                console.log("Found message");
                                found = true;

                                var parts = [];

                                // We might be passed both an HTML and a text bodypart.  In this case we drop the
                                // text one when editing on Yahoo.  This is because Yahoo only seems to handle having
                                // one of them, and tends to convert text/plain messages to text/html when you edit -
                                // so we follow suit.
                                if (htmlbody) {
                                    parts.push({
                                        msgPartId: msg.messageParts[0].msgPartId,
                                        contentType: 'text/html',
                                        textContent: htmlbody
                                    });
                                } else if (textbody) {
                                    parts.push({
                                        msgPartId: msg.messageParts[0].msgPartId,
                                        contentType: 'text/plain',
                                        textContent: textbody
                                    });
                                }

                                var data = {
                                    subject: subject,
                                    messageParts: parts
                                }

                                console.log("Edit with", data);

                                // Get a crumb from Yahoo to do the work.
                                function getCrumb(ret) {
                                    var match = /GROUPS.YG_CRUMB = "(.*)"/.exec(ret);

                                    if (match) {
                                        self.crumb = match[1];
                                        new majax({
                                            type: "POST",
                                            url: YAHOOAPI + 'groups/' + groupname + "/pending/messages/" + group.yahoopendingid + "?gapi_crumb=" + self.crumb,
                                            data: {
                                                messageParts: JSON.stringify(data),
                                                action: 'SAVE'
                                            },
                                            success: function (ret) {
                                                console.log("Edit returned", ret);

                                                if (ret.hasOwnProperty('ygData') &&
                                                    ret.ygData.hasOwnProperty('msgId') &&
                                                    ret.ygData.msgId == group.yahoopendingid) {
                                                    // The edit on Yahoo worked.  Miracles never cease.  Now update the copy on our server.
                                                    $.ajax({
                                                        type: 'PUT',
                                                        url: API + 'message',
                                                        data: {
                                                            id: self.get('id'),
                                                            subject: subject,
                                                            textbody: textbody,
                                                            htmlbody: htmlbody
                                                        }, success: function (ret) {
                                                            console.log("Server edit returned", ret);
                                                            if (ret.ret == 0) {
                                                                // Make sure we're up to date.
                                                                self.fetch().then(function () {
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
                                            $.ajaxq('plugin', {
                                                type: "GET",
                                                url: url,
                                                success: getCrumb,
                                                error: function (request, status, error) {
                                                    console.log("Get crumb failed");
                                                    self.trigger('editfailed');
                                                }
                                            });
                                        }
                                    }
                                }

                                // Do this synchronously to increase the chance that the crumb will still be valid
                                // when we use it.  If we have background work happening the crumb might be invalidated
                                // under our feet.
                                $.ajaxq('plugin', {
                                    type: "GET",
                                    url: "https://groups.yahoo.com/neo/groups/" + self.get('') + "/management/pendingmessages?" + Math.random(),
                                    success: getCrumb,
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
                groups.push(_.extend([], groupdata, group));
            });

            message.groups = groups;
        } else {
            message = ret;
        }

        return(message);
    }
});

Iznik.Collections.Message = IznikCollection.extend({
    model: Iznik.Models.Message,

    initialize: function (options) {
        this.options = options;

        // Use a comparator to show in most recent first order
        this.comparator = function(a, b) {
            var ret = (new Date(b.get('date'))).getTime() - (new Date(a.get('date'))).getTime();
            return(ret);
        }
    },

    url: API + 'messages',

    parse: function(ret) {
        var self = this;

        if (ret.hasOwnProperty('messages')) {
            // Fill in the groups - each message has the group object below it for our convenience, even though the server
            // returns them in a separate object for bandwidth reasons.
            _.each(ret.messages, function(message, index, list) {
                var groups = [];
                _.each(message.groups, function(group, index2, list2) {
                    var groupdata = ret.groups[group.groupid];
                    groups.push(_.extend([], groupdata, group));
                });

                message.groups = groups;
            });

            return ret.messages;
        } else {
            console.log("No messages to return", ret);
            return(null);
        }
    }
});