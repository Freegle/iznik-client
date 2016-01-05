Iznik.Views.Plugin.Main = IznikView.extend({
    connected: false,
    everConnected: false,

    work: [],
    retrying: [],
    currentItem: null,
    yahooGroups: [],

    render: function() {
        window.setTimeout(_.bind(this.checkPluginStatus, this), 3000);
    },

    resume: function() {
        this.connected = true;
        this.checkWork();
    },

    pause: function() {
        var self = this;
        self.connected = false;
    },

    startSyncs: function() {
        var now = moment();

        // Start pending syncs first because if they're wrong, that's normally more annoying.
        Iznik.Session.get('groups').each(function (group) {
            if (group.get('onyahoo') &&
                (group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                (new Iznik.Views.Plugin.Yahoo.SyncMessages.Pending({model: group})).render();
            }
        });

        Iznik.Session.get('groups').each(function (group) {
            var last = moment(group.get('lastyahoomessagesync'));
            var hoursago = moment.duration(now.diff(last)).asHours();

            if (group.get('onyahoo') &&
                (!group.get('lastyahoomessagesync') || hoursago > 23) &&
                (group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                (new Iznik.Views.Plugin.Yahoo.SyncMessages.Approved({model: group})).render();
            }
        });

        Iznik.Session.get('groups').each(function (group) {
            var last = moment(group.get('lastyahoomembersync'));
            var hoursago = moment.duration(now.diff(last)).asHours();

            if (group.get('onyahoo') &&
                (!group.get('lastyahoomembersync') || hoursago > 23) &&
                (group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                (new Iznik.Views.Plugin.Yahoo.SyncMembers.Approved({model: group})).render();
            }
        });

        // Sync every twenty minutes.  Most changes will be picked up by the session poll, but it's possible
        // that someone will delete messages directly on Yahoo which we need to notice have gone.
        //
        // Delay doesn't set the right context by default.
        // TODO don't do this until we stop them starting while the last lot are still running.
        //_.delay(_.bind(this.startSyncs, this), 1200000);
    },

    checkWork: function() {
        var self = this;
        this.updatePluginCount();

        if (!this.currentItem) {
            // Get any first item of work to do.
            var first = this.work.shift();

            if (first) {
                self.currentItem = first;
                //console.log("First item", first);

                var groupname;

                if (first.model.get('nameshort')) {
                    // Get a crumb from the relevant group
                    groupname = first.model.get('nameshort');
                } else {
                    // We're not acting on a specific group.  Get a crumb from one of ours.
                    var groups = Iznik.Session.get('groups');
                    groupname = groups && groups.length > 0 ? groups.at(0).get('nameshort') : null;
                }

                function getCrumb(ret) {
                    var match = /GROUPS.YG_CRUMB = "(.*)"/.exec(ret);

                    if (match) {
                        // All work has a start method which triggers action.
                        first.crumb = match[1];
                        first.start();
                    } else {
                        var match = /window.location.href = "(.*)"/.exec(ret);

                        if (match) {
                            var url = match[1];
                            $.ajaxq('plugin', {
                                type: "GET",
                                url: url,
                                success: getCrumb,
                                error: function (request, status, error) {
                                    self.retryWork(self.currentItem);
                                }
                            });
                        }
                    }
                }

                _.delay(function() {
                    $.ajaxq('plugin', {
                        type: "GET",
                        url: "https://groups.yahoo.com/neo/groups/" + groupname + first.crumbLocation + "?" + Math.random(),
                        success: getCrumb,
                        error: function (request, status, error) {
                            self.retryWork(self.currentItem);
                        }
                    }, 500);
                });
            }
        }
    },

    addWork: function(work) {
        var id = work.model ? work.model.get('workid') : null;
        var add = true;

        if (id) {
            _.each(_.union([this.currentItem], this.work, this.retrying), function (item) {
                if (item) {
                    var itemid = item.model ? item.model.get('workid') : null;

                    if (id == itemid) {
                        // We already have this item of work - no need to add it.
                        add = false;
                        work.destroyIt();
                    }
                }
            });
        }

        if (add) {
            this.work.push(work);
            this.updatePluginCount();
            this.checkWork();
        }
    },

    updatePluginCount: function() {
        var count = this.work.length + (this.currentItem !== null ? 1 : 0 ) + this.retrying.length;

        if (count > 0) {
            $('.js-plugincount').html(count).show();
            $('#js-nowork').hide();
        } else {
            $('.js-plugincount').empty().hide();
            $('#js-nowork').fadeIn('slow');
        }
    },

    completedWork: function() {
        this.currentItem = null;
        this.checkWork();
    },

    requeueWork: function(work) {
        // This is ongoing - so put to the front.
        this.currentItem = null;
        this.work.unshift(work);
        this.checkWork();
    },

    retryWork: function(work) {
        var self = this;

        self.currentItem = null;
        self.retrying.push(work);

        // We don't want to add the work back into the queue immediately, as this could mean that we hammer away
        // retrying, which increases the chance of Yahoo 999s.
        _.delay(function() {
            self.retrying = _.without(self.retrying, work);

            // Put at the back so as not to block other work.
            self.work.push(work);
            self.checkWork();
        }, 60000);
    },

    checkPluginStatus: function() {
        var self = this;

        function checkResponse(self) {
            return(function(ret) {
                if (ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('allMyGroups')) {
                    $('.pluginonly').show();

                    if (!self.connected) {
                        self.resume();

                        if (!self.everConnected) {
                            // The plugin state might flipflop between connected and disconnected.  We don't want
                            // to trigger invitations each time.
                            self.listYahooGroups();
                        }

                        self.everConnected = true;
                    }

                    $('#js-plugindisconnected').fadeOut('slow', function() {
                        $('#js-pluginconnected').fadeIn('slow');
                    })
                } else {
                    $('.pluginonly').hide();

                    if (self.connected) {
                        self.pause();
                    }

                    $('#js-pluginconnected').fadeOut('slow', function() {
                        $('#js-plugindisconnected').fadeIn('slow');
                    })
                }
            });
        }

        // Check if we are connected to Yahoo by issuing an API call.
        new majax({
            type: 'GET',
            url: 'https://groups.yahoo.com/api/v1/user/groups/all',
            success: checkResponse(self),
            error: function() {
                $('.pluginonly').hide();

                // If we got an error, we're not connected.
                if (self.connected) {
                    self.pause();
                }

                $('#js-pluginconnected').fadeOut('slow', function() {
                    $('#js-plugindisconnected').fadeIn('slow');
                })
            }, complete: function() {
                window.setTimeout(_.bind(self.checkPluginStatus, self), 10000);
            }
        });

        // Get our session, both to keep it alive and update any counts.
        Iznik.Session.testLoggedIn();

        // Check if we have any plugin work to do from the server.
        $.ajaxq('plugin', {
            type: 'GET',
            url: API + 'plugin',
            success: function(ret) {
                if (ret.ret == 0 && ret.plugin.length > 0) {
                    _.each(ret.plugin, function(work, index, list) {
                        work.workid = work.id;
                        work = _.extend(work, jQuery.parseJSON(work.data));

                        // This is work from the server, which we may already have
                        var got = (self.currentItem && work.id == self.currentItem.model.get('id'));

                        _.each(self.work, function(item, index, list) {
                            if (item.model.get('id') == work.id) {
                                got = true;
                            }
                        });

                        if (got) {
                            return;
                        }

                        // Create a piece of work for us to do.  If we already have this one it'll be filtered
                        // out when we add it.
                        if (work.hasOwnProperty('groupid')) {
                            // Find our group and add it in.
                            work.group = Iznik.Session.get('groups').findWhere({
                                id: work.groupid
                            });
                            work.group = work.group.toJSON2();
                        }

                        switch (work.type) {
                            case 'ApprovePendingMessage': {
                                (new Iznik.Views.Plugin.Yahoo.ApprovePendingMessage({
                                    model: new IznikModel(work)
                                }).render());
                                break;
                            }

                            case 'RejectPendingMessage': {
                                (new Iznik.Views.Plugin.Yahoo.RejectPendingMessage({
                                    model: new IznikModel(work)
                                }).render());
                                break;
                            }

                            case 'DeliveryType': {
                                (new Iznik.Views.Plugin.Yahoo.DeliveryType({
                                    model: new IznikModel(work)
                                }).render());
                                break;
                            }

                            case 'PostingStatus': {
                                (new Iznik.Views.Plugin.Yahoo.PostingStatus({
                                    model: new IznikModel(work)
                                }).render());
                                break;
                            }

                            case 'RemoveApprovedMember': {
                                (new Iznik.Views.Plugin.Yahoo.RemoveApprovedMember({
                                    model: new IznikModel(work)
                                }).render());
                                break;
                            }
                        }
                    });

                    self.checkWork();
                }
            }
        })
    },

    listYahooGroups: function() {
        // We get a list of all the groups on Yahoo so that we can see whether there are groups on the server
        // for which we need to update our mod status.
        this.yahooGroupStart = 1;
        this.getYahooGroupChunk();
    },

    getYahooGroupChunk: function() {
        // If this fails, we just won't finish checking, and therefore won't make any changes, which is probably
        // the best option.
        $.ajaxq('plugin', {
            type: "GET",
            context: this,
            url: YAHOOAPI + 'user/groups/all?start=' + this.yahooGroupStart + "&count=20&sortOrder=asc&orderBy=name&chrome=raw",
            success: this.processYahooGroupChunk
        });
    },

    processYahooGroupChunk: function(ret) {
        var self = this;

        if (ret.hasOwnProperty('ygData')) {
            if (ret.ygData.hasOwnProperty('allMyGroups')) {
                _.each(ret.ygData.allMyGroups, function(group) {
                    if (group.membership == "MOD" || group.membership == "OWN") {
                        self.yahooGroups.push(group.groupName.toLocaleLowerCase());
                    }
                });

                if (ret.ygData.allMyGroups.length > 0) {
                    this.yahooGroupStart += 20;
                    this.getYahooGroupChunk();
                }
            } else {
                // We've got all the groups we're an owner/mod on.
                var serverGroups = [];
                var nameToId = [];
                Iznik.Session.get('groups').each(function (group) {
                    var role = group.get('role');
                    if (role == 'Moderator' || role == 'Owner') {
                        var lname = group.get('nameshort').toLowerCase();
                        serverGroups.push(lname);
                        nameToId[lname] = group.get('id');
                    }
                });

                var serverMissing = _.difference(self.yahooGroups, serverGroups);
                var yahooMissing = _.difference(serverGroups, self.yahooGroups);
                //console.log("Yahoo groups", self.yahooGroups);
                //console.log("Server groups", serverGroups);
                //console.log("Mod on Yahoo but not server", serverMissing);
                //console.log("Mod on server but but not Yahoo", yahooMissing);
                //console.log("NameToId", nameToId);
                //console.log("Session", Iznik.Session);

                // If we're a mod on the server but not on Yahoo, then we need to demote ourselves.
                _.each(yahooMissing, function(demote) {
                    $.ajax({
                        url: API + 'memberships',
                        type: 'PATCH',
                        data: {
                            userid: Iznik.Session.get('me').id,
                            groupid: nameToId[demote],
                            role: 'Member'
                        }
                    })
                });

                // If we're a mod on Yahoo but not on the server, and it's a group the server knows about,
                // then we need to prove to the server that we're a mod so that we can auto-add it to
                // our list of groups.  We do this by triggering an invitation, which is something only mods
                // can do.
                _.each(serverMissing, function(group) {
                    var g = new Iznik.Models.Group({ id: group});
                    g.fetch().then(function() {
                        // The group is hosted by the server; trigger a confirm.  First we need a confirm key.
                        $.ajax({
                            url: API + 'group',
                            type: 'POST',
                            data: {
                                id: g.get('id'),
                                action: 'ConfirmKey'
                            },
                            success: function(ret) {
                                if (ret.ret == 0) {
                                    var email = 'modconfirm-' + g.get('id') + '-' +
                                        Iznik.Session.get('me').id + '-' + ret.key + '@' + location.host;

                                    (new Iznik.Views.Plugin.Yahoo.ConfirmMod({
                                        model: new IznikModel({
                                            nameshort: group,
                                            email: email
                                        })
                                    }).render());
                                }
                            }
                        })
                    });
                });
            }
        }
    }
});

Iznik.Views.Plugin.Info = IznikView.extend({
    className: "panel panel-default js-plugin",

    template: "layout_plugin",

    render: function() {
        this.$el.html(window.template(this.template)());

        var v = new Iznik.Views.Help.Box();
        v.template = 'modtools_layout_background';
        this.$('.js-background').html(v.render().el);

        return(this);
    }
});

Iznik.Views.Plugin.Work = IznikView.extend({
    startBusy: function() {
        // Change icon
        this.$('.glyphicon-time, glyphicon-warning-sign').removeClass('glyphicon-time, glyphicon-warning-sign').addClass('glyphicon-refresh rotate');
    },

    drop: function() {
        var self = this;

        // Don't even retry
        IznikPlugin.completedWork();
        this.$el.fadeOut('slow', function() {
            self.$el.remove();
        });
    },

    fail: function() {
        this.$('.glyphicon-refresh').removeClass('glyphicon-refresh rotate').addClass('glyphicon-warning-sign');

        // Failed - put to the back of the queue.
        IznikPlugin.retryWork(this);
        IznikPlugin.completedWork();

        // Move to the end of the list.
        this.$el.detach();
        $('#js-work').append(this.$el);
    },

    succeed: function() {
        var self = this;

        function finished() {
            self.$el.fadeOut(2000, function () {
                self.remove();
                IznikPlugin.completedWork();
            });

            // Refresh any counts on our menu, which may have changed.
            Iznik.Session.updateCounts();
        }

        if (self.server) {
            // This work came from the server - record the success there.
            //
            // Even if this fails, continue.
            $.ajaxq('plugin', {
                type: "DELETE",
                url: API + 'plugin',
                data: {
                    id: self.model.get('workid')
                }, complete: function () {
                    finished();
                }
            });
        } else {
            finished();
        }
    },

    queue: function() {
        window.setTimeout(_.bind(function() {
            // This is ongoing - so add it to the front of the queue.
            IznikPlugin.requeueWork(this);
        }, this), 1);
    },

    rendered: false,
    render: function() {
        if (!this.rendered) {
            // Render our template and add it to the visible work queue.
            //
            // Only render once otherwise we get duplicates in the fail case.
            this.$el.html(window.template(this.template)(this.model ? this.model.toJSON2() : null));
            $('#js-work').append(this.$el).fadeIn('slow');
            this.rendered = true;
        }

        // Queue this item of work.
        IznikPlugin.addWork(this);

        return(this);
    }
});

Iznik.Views.Plugin.Yahoo.SyncMessages = Iznik.Views.Plugin.Work.extend({
    offset: 1,

    chunkSize: 100,

    ageLimit: 31,

    earlist: null,

    crumbLocation: "/management/pendingmessages",

    start: function() {
        var self = this;
        this.startBusy();

        // Need to create this here rather than as a property, otherwise the same array is shared between instances
        // of this object.
        if (!this.hasOwnProperty('messages')) {
            this.messages = [];
        }

        $.ajax({
            type: "GET",
            url: self.url(),
            context: self,
            success: self.processChunk,
            error: self.failChunk
        });
    },

    failChunk:  function (request, status, error) {
        this.fail();
    },

    ourSyncProgressBar: function() {
        var percent = Math.round((this.promisesCount / this.promisesLen) * 100);
        this.$('.progress-bar:last').css('width',  percent + '%').attr('aria-valuenow', percent);
    },

    processChunk: function(ret) {
        var self = this;
        var now = moment();

        if (ret.ygData) {
            var total = ret.ygData[this.numField];
            this.offset += total;
            var messages = ret.ygData[this.messageLocation];
            var maxage = null;

            for (var i = 0; i < total; i++) {
                var message = messages[i];

                // Keep track of the earliest message we're going to pass - we may use that later to decide whether
                // to delete.
                self.earliest = (self.earliest == null || message[self.dateField] < self.earliest) ?
                    message[self.dateField] : self.earliest;

                var d = moment(message[this.dateField] * 1000);
                var age = now.diff(d) / 1000 / 60 / 60 / 24;
                maxage = age > maxage ? age : maxage;
                var percent = Math.round((maxage / self.ageLimit) * 100);
                self.$('.progress-bar:first').css('width',  percent + '%').attr('aria-valuenow', percent);

                var thisone = {
                    email: message['email'],
                    subject: message['subject'],
                    date: d.format()
                };

                if (message.hasOwnProperty('msgId')) {
                    thisone.yahoopendingid = message['msgId'];
                }

                if (message.hasOwnProperty('messageId')) {
                    thisone.yahooapprovedid = message['messageId'];
                }

                this.messages.push(thisone);
            }

            if (total == 0 || total < this.chunkSize || maxage >= self.ageLimit) {
                // Finished.  Now check with the server whether we have any messages which it doesn't.
                $.ajaxq('plugin', {
                    type: "POST",
                    url: API + 'messages',
                    context: self,
                    data: {
                        'groupid': this.model.get('id'),
                        'collections': this.collections,
                        'messages': this.messages
                    },
                    success: function(ret) {
                        var self = this;

                        // Defer as ajaxq plays up when you queue items from within a callback.
                        _.defer(function() {
                            if (ret.ret == 0) {
                                // If there are messages which we don't have but the server does, then the server
                                // is wrong and we need to delete them.
                                //
                                // We might be deleting all such messages (for Pending, where we do a sync of all
                                // of them) or only ones which are later than the earlier message we passed and where
                                // we therefore know they must have been deleted from Yahoo (Approved).
                                self.promises = [];
                                _.each(ret.missingonclient, function(missing, index, list) {
                                    if (self.deleteAllMissing || missing[self.dateField] > self.earliest) {
                                        self.promises.push($.ajaxq('plugin', {
                                            type: "DELETE",
                                            url: API + 'message',
                                            context: self,
                                            data: {
                                                id: missing.id,
                                                groupid: self.model.get('id'),
                                                collection: missing.collection,
                                                reason: 'Not present on Yahoo'
                                            }
                                        }));
                                    }
                                });

                                // If there are messages which we have but the server doesn't, then the server is
                                // wrong and we need to add them.
                                _.each(ret.missingonserver, function(missing, index, list) {
                                    missing.deferred = new $.Deferred();
                                    self.promises.push(missing.deferred.promise());

                                    $.ajaxq('plugin', {
                                        type: "GET",
                                        url: self.sourceurl(missing[self.idField]),
                                        context: self,
                                        success: function(ret) {
                                            if (ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('rawEmail')) {
                                                var source = decodeEntities(ret.ygData.rawEmail);

                                                if (source.indexOf('X-eGroups-Edited-By:') == -1) {
                                                    var data = {
                                                        groupid: self.model.get('id'),
                                                        from: ret.ygData.email,
                                                        message: source,
                                                        source: self.source
                                                    };

                                                    data[self.idField] = missing[self.idField];

                                                    $.ajaxq('plugin', {
                                                        type: "PUT",
                                                        url: API + 'messages',
                                                        data: data,
                                                        context: self,
                                                        success: function (ret) {
                                                            missing.deferred.resolve();
                                                        }
                                                    });
                                                } else {
                                                    // This is an edited message, which is all messed up and difficult
                                                    // to sync.  Ignore it.
                                                    missing.deferred.resolve();
                                                }
                                            } else {
                                                // Couldn't fetch.  Not much we can do - Yahoo has some messages
                                                // which are not accessible.
                                                missing.deferred.resolve();
                                            }
                                        }, error: function(req, status, error) {
                                            // Couldn't fetch.  Not much we can do - Yahoo has some messages
                                            // which are not accessible.
                                            missing.deferred.resolve();
                                        }
                                    });
                                });

                                // Record how many there are and update progress bar
                                self.promisesLen = self.promises.length;
                                self.promisesCount = 0;
                                _.each(self.promises, function(promise) {
                                    promise.done(function() {
                                        self.promisesCount++;
                                        self.ourSyncProgressBar.apply(self);

                                        if (self.promisesCount >= self.promisesLen) {
                                            // Once they're all done, we have succeeded.
                                            self.succeed();
                                        }
                                    });
                                });

                                if (self.promisesLen == 0) {
                                    self.succeed();
                                }
                            } else {
                                self.failChunk();
                            }
                        });
                    },
                    error: self.failChunk
                });
            } else {
                this.queue();
            }
        }
    }
});

Iznik.Views.Plugin.Yahoo.SyncMessages.Pending = Iznik.Views.Plugin.Yahoo.SyncMessages.extend({
    template: 'plugin_sync_pending',

    messageLocation: 'pendingMessages',
    crumbLocation: "/management/pendingmessages",

    numField: 'numResults',
    idField: 'yahoopendingid',
    dateField: 'postDate',

    deleteAllMissing: true,

    collections: [
        'Pending',
        'Spam'
    ],

    source: 'Yahoo Pending',

    url: function() {
        return YAHOOAPI + 'groups/' + this.model.get('nameshort') + "/pending/messages/" + this.offset +
            "/parts?start=1&count=" + this.chunkSize + "&chrome=raw"
    },

    sourceurl: function(id) {
        return YAHOOAPI + 'groups/' + this.model.get('nameshort') + '/pending/messages/' + id + '/raw'
    }
});

Iznik.Views.Plugin.Yahoo.SyncMessages.Approved = Iznik.Views.Plugin.Yahoo.SyncMessages.extend({
    // Setting offset to 0 omits start from first one
    offset: 0,

    template: 'plugin_sync_approved',

    messageLocation: 'messages',
    crumbLocation: "/management/pendingmessages",

    numField: 'numRecords',
    idField: 'yahooapprovedid',
    dateField: 'date',

    deleteAllMissing: false,

    collections: [
        'Approved',
        'Spam'
    ],

    source: 'Yahoo Approved',

    url: function() {
        var url = YAHOOAPI + 'groups/' + this.model.get('nameshort') + "/messages?count=" + this.chunkSize + "&chrome=raw"

        if (this.offset) {
            url += "&start=" + this.offset;
        }

        return(url);
    },

    sourceurl: function(id) {
        return YAHOOAPI + 'groups/' + this.model.get('nameshort') + '/messages/' + id + '/raw'
    }
});

Iznik.Views.Plugin.Yahoo.SyncMembers = Iznik.Views.Plugin.Work.extend({
    offset: 1,

    crumbLocation: "/members/all",

    chunkSize: 100,
    promisesCount: 0,

    start: function() {
        var self = this;
        this.startBusy();

        // Need to create this here rather than as a property, otherwise the same array is shared between instances
        // of this object.
        if (!this.hasOwnProperty('members')) {
            this.members = [];
        }

        $.ajax({
            type: "GET",
            url: self.url(),
            context: self,
            success: self.processChunk,
            error: self.failChunk
        });
    },

    failChunk:  function (request, status, error) {
        this.fail();
    },

    progressBar: function() {
        var percent = Math.round((this.offset/ this.totalMembers) * 100);
        this.$('.progress-bar').css('width',  percent + '%').attr('aria-valuenow', percent);
    },

    processChunk: function(ret) {
        var self = this;
        var now = moment();

        if (ret.ygData) {
            var total = ret.ygData[this.numField];
            var members = ret.ygData[this.memberLocation];
            this.offset += members.length;

            self.totalMembers = total;
            self.progressBar.apply(self);

            _.each(members, function(member) {
                var mom = new moment(member[self.dateField] * 1000);
                var thisone = {
                    email: member['email'],
                    yahooUserId: member['userId'],
                    yahooPostingStatus: member['postingStatus'],
                    yahooDeliveryType: member['deliveryType'],
                    yahooModeratorStatus: member.hasOwnProperty('moderatorStatus') ? member['moderatorStatus'] : 'MEMBER',
                    name: member['yid'],
                    date: mom.format()
                };

                self.members.push(thisone);
            });

            if (total == 0 || members.length < this.chunkSize) {
                // Finished.  Now pass these members to the server.
                $.ajaxq('plugin', {
                    type: 'PATCH',
                    url: API + 'memberships',
                    context: self,
                    data: {
                        'groupid': this.model.get('id'),
                        'members': this.members
                    },
                    success: function(ret) {
                        var self = this;

                        if (ret.ret == 0) {
                            self.succeed();
                        } else {
                            self.failChunk();
                        }
                    },
                    error: self.failChunk
                });
            } else {
                this.queue();
            }
        }
    }
});

Iznik.Views.Plugin.Yahoo.SyncMembers.Approved = Iznik.Views.Plugin.Yahoo.SyncMembers.extend({
    // Setting offset to 0 omits start from first one
    offset: 0,

    template: 'plugin_sync_approved_members',

    crumbLocation: "/members/all",
    memberLocation: 'members',

    numField: 'total',
    dateField: 'date',

    collections: [
        'Approved',
        'Pending'
    ],

    url: function() {
        var url = YAHOOAPI + 'groups/' + this.model.get('nameshort') + "/members/confirmed?count=" + this.chunkSize + "&chrome=raw"

        if (this.offset) {
            url += "&start=" + this.offset;
        }

        return(url);
    }
});

Iznik.Views.Plugin.Yahoo.ApprovePendingMessage = Iznik.Views.Plugin.Work.extend({
    template: 'plugin_pending_approve',
    crumbLocation: "/management/pendingmessages",

    server: true,

    start: function() {
        var self = this;
        this.startBusy();

        $.ajaxq('plugin', {
            type: "POST",
            url: YAHOOAPI + 'groups/' + this.model.get('group').nameshort + "/pending/messages",
            data: {
                A: this.model.get('id'),
                gapi_crumb: this.crumb
            }, success: function (ret) {
                if (ret.hasOwnProperty('ygData') &&
                    ret.ygData.hasOwnProperty('numAccepted') &&
                    ret.ygData.hasOwnProperty('numRejected')) {
                    // If the approval worked, then numAccepted = 1.
                    // If the approval is no longer relevant because the pending message has gone, both are 0.
                    if (ret.ygData.numAccepted == 1 ||
                        (ret.ygData.numAccepted == 0 && ret.ygData.numRejected == 0)) {
                        self.succeed();
                    } else {
                        self.fail();
                    }
                } else {
                    self.fail();
                }
            }, error: function(a,b,c) {
                self.fail();
            }
        });
    }
});

Iznik.Views.Plugin.Yahoo.RejectPendingMessage = Iznik.Views.Plugin.Work.extend({
    template: 'plugin_pending_reject',
    crumbLocation: "/management/pendingmessages",

    server: true,

    start: function() {
        var self = this;
        this.startBusy();

        $.ajaxq('plugin', {
            type: "POST",
            url: YAHOOAPI + 'groups/' + this.model.get('group').nameshort + "/pending/messages",
            data: {
                R: this.model.get('id'),
                gapi_crumb: this.crumb
            }, success: function (ret) {
                if (ret.hasOwnProperty('ygData') &&
                    ret.ygData.hasOwnProperty('numAccepted') &&
                    ret.ygData.hasOwnProperty('numRejected')) {
                    // If the rection worked, then numRejected = 1.
                    // If the rejection is no longer relevant because the pending message has gone, both are 0.
                    if (ret.ygData.numRejected== 1 ||
                        (ret.ygData.numAccepted == 0 && ret.ygData.numRejected == 0)) {
                        self.succeed();
                    } else {
                        self.fail();
                    }
                } else {
                    self.fail();
                }
            }, error: function() {
                self.fail();
            }
        });
    }
});

Iznik.Views.Plugin.Yahoo.ChangeAttribute = Iznik.Views.Plugin.Work.extend({
    crumbLocation: "/members/all",

    server: true,

    start: function() {
        var self = this;
        this.startBusy();

        var mod = IznikYahooUsers.findUser({
            email: this.model.get('email'),
            group: this.model.get('group').nameshort
        });

        mod.fetch().then(function() {
            // Make the change.  This will result in change events to the model and thereby refresh any
            // views.
            if (!mod.get('userId')) {
                // We couldn't fetch the user on Yahoo, which means they are no longer on the group.  This
                // is effectively a success for this change.
                self.succeed();
            } else {
                self.listenToOnce(mod, 'completed', function(worked) {
                    if (worked) {
                        self.succeed();
                    } else {
                        self.fail();
                    }
                });

                mod.changeAttr(self.attr, self.model.get(self.attr));
            }
        });
    }
});

Iznik.Views.Plugin.FakeFail = Iznik.Views.Plugin.Work.extend({
    template: 'plugin_fakefail',

    start: function() {
        var self = this;
        this.startBusy();
        _.delay(function() {
            self.fail();
        }, 5000);
    }
});

Iznik.Views.Plugin.Yahoo.DeliveryType  = Iznik.Views.Plugin.Yahoo.ChangeAttribute.extend({
    crumbLocation: "/members/all",
    template: 'plugin_yahoo_delivery',
    attr: 'deliveryType'
});

Iznik.Views.Plugin.Yahoo.PostingStatus = Iznik.Views.Plugin.Yahoo.ChangeAttribute.extend({
    crumbLocation: "/members/all",
    template: 'plugin_yahoo_posting',
    attr: 'postingStatus'
});

Iznik.Views.Plugin.Yahoo.Invite = Iznik.Views.Plugin.Work.extend({
    crumbLocation: "/members/all",
    template: 'plugin_invite',

    start: function() {
        var self = this;
        this.startBusy();

        $.ajax({
            type: "POST",
            url: YAHOOAPI + "groups/" + self.model.get('nameshort') +
                "/members?actionType=MAILINGLIST_INVITE&gapi_crumb=" + self.crumb,
            data: 'members=[{"email":"' + self.model.get('email') + '"}]',
            success: function (ret) {
                console.log("Invite returned", ret);

                if (ret.hasOwnProperty('ygData') &&
                    ret.ygData.hasOwnProperty('numSuccessfulInvites')) {
                    // If the invite worked, numSuccessfulInvites == 1.
                    if (ret.ygData.numSuccessfulInvites == 1) {
                        self.succeed();
                    } else {
                        self.fail();
                    }
                } else {
                    self.fail();
                }
            }, error: function() {
                self.fail();
            }
        });
    }
});

Iznik.Views.Plugin.Yahoo.ConfirmMod = Iznik.Views.Plugin.Yahoo.Invite.extend({
    template: 'plugin_confirmmod',

    start: function() {
        // For this we drop if we fail - because we might not have those mod permissions on Yahoo.
        var self = this;
        this.startBusy();

        $.ajax({
            type: "POST",
            url: YAHOOAPI + "groups/" + self.model.get('nameshort') +
            "/members?actionType=MAILINGLIST_INVITE&gapi_crumb=" + self.crumb,
            data: 'members=[{"email":"' + self.model.get('email') + '"}]',
            success: function (ret) {
                console.log("Invite returned", ret);

                if (ret.hasOwnProperty('ygData') &&
                    ret.ygData.hasOwnProperty('numSuccessfulInvites')) {
                    // If the invite worked, numSuccessfulInvites == 1.
                    if (ret.ygData.numSuccessfulInvites == 1) {
                        self.succeed();
                    } else {
                        self.drop();
                    }
                } else {
                    self.drop();
                }
            }, error: function() {
                self.drop();
            }
        });
    }
});

Iznik.Views.Plugin.Yahoo.RemoveApprovedMember = Iznik.Views.Plugin.Work.extend({
    template: 'plugin_member_approved_remove',

    crumbLocation: "/members/all",

    server: true,

    start: function() {
        var self = this;

        var data = [{
            userId: this.model.get('id')
        }];

        new majax({
            type: "DELETE",
            url: YAHOOAPIv2 + "groups/" + this.model.get('group').nameshort + "/members?gapi_crumb=" + self.crumb + "&members=" + encodeURIComponent(JSON.stringify(data)),
            data: data,
            success: function (ret) {
                if (ret.hasOwnProperty('ygData') &&
                    ret.ygData.hasOwnProperty('numPassed')) {
                    // If the delete worked, numPassed == 1.
                    if (ret.ygData.numPassed == 1) {
                        self.succeed();
                    } else {
                        // If we get a status of NOT SUBSCRIBED then the member is no longer on the group - which
                        // means this remove is complete.
                        if (ret.ygData.hasOwnProperty('members') &&
                            ret.ygData.members.length == 1 &&
                            ret.ygData.members[0].hasOwnProperty('status') &&
                            ret.ygData.members[0].status == 'NOT_SUBSCRIBED') {
                            self.succeed();
                        } else {
                            self.fail();
                        }
                    }
                } else {
                    self.fail();
                }
            }, error: function() {
                self.fail();
            }
        });

        this.startBusy();
    }
});

var IznikPlugin = new Iznik.Views.Plugin.Main();

//_.delay(function() {
//    var v = new Iznik.Views.Plugin.FakeFail({
//        model: new IznikModel()
//    });
//    v.render();
//}, 10000);
