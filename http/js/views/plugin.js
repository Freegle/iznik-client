Iznik.Views.Plugin.Main = IznikView.extend({
    connected: false,

    work: [],
    currentItem: null,

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
        // Start pending syncs first because if they're wrong, that's normally more annoying.
        Iznik.Session.get('groups').each(function (group) {
            if (group.get('onyahoo') &&
                (group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                (new Iznik.Views.Plugin.Yahoo.SyncMessages.Pending({model: group})).render();
            }
        });

        Iznik.Session.get('groups').each(function (group) {
            if (group.get('onyahoo') &&
                (group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                (new Iznik.Views.Plugin.Yahoo.SyncMessages.Approved({model: group})).render();
            }
        });

        // Sync every ten minutes.  Most changes will be picked up by the session poll, but it's possible
        // that someone will delete messages directly on Yahoo which we need to notice have gone.
        _.delay(this.startSyncs, 600000);
    },

    checkWork: function() {
        var self = this;
        this.updatePluginCount();

        var groups = Iznik.Session.get('groups');
        var groupname = groups && groups.length > 0 ? groups.at(0).get('nameshort') : null;

        if (groupname && !this.currentItem) {
            // Get any first item of work to do.
            var first = this.work.shift();

            if (first) {
                self.currentItem = first;

                // Get a crumb from Yahoo to do the work.  It doesn't matter which of our groups we do this for -
                // Yahoo returns the same crumb.
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

                $.ajaxq('plugin', {
                    type: "GET",
                    url: "https://groups.yahoo.com/neo/groups/" + groupname + "/management/pendingmessages",
                    success: getCrumb,
                    error: function (request, status, error) {
                        self.retryWork(self.currentItem);
                    }
                });
            }
        }
    },

    addWork: function(work) {
        this.work.push(work);
        this.updatePluginCount();
        this.checkWork();
    },

    updatePluginCount: function() {
        var count = this.work.length + (this.currentItem !== null ? 1 : 0 );

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
        // Put at the back so as not to block other work.
        this.currentItem = null;
        this.work.push(work);
        this.checkWork();
    },

    checkPluginStatus: function() {
        var self = this;

        function checkResponse(self) {
            return(function(ret) {
                if (ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('allMyGroups')) {
                    if (!self.connected) {
                        self.resume();
                    }

                    $('#js-plugindisconnected').fadeOut('slow', function() {
                        $('#js-pluginconnected').fadeIn('slow');
                    })
                } else {
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
                        }
                    });

                    self.checkWork();
                }
            }
        })
    }
});

Iznik.Views.Plugin.Info = IznikView.extend({
    className: "panel panel-default js-plugin",

    template: "layout_plugin"
});

Iznik.Views.Plugin.Work = IznikView.extend({
    startBusy: function() {
        // Change icon
        this.$('.glyphicon-time, glyphicon-warning-sign').removeClass('glyphicon-time, glyphicon-warning-sign').addClass('glyphicon-refresh rotate');
    },

    fail: function() {
        // Failed - put to the back of the queue.
        IznikPlugin.retryWork(this);
        IznikPlugin.completedWork();

        // Move to the end of the list.
        this.$('.glyphicon-refresh').removeClass('glyphicon-refresh rotate').addClass('glyphicon-warning-sign');
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
        }, this), 500);
    },

    render: function() {
        // Render our template and add it to the visible work queue.
        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        $('#js-work').append(this.$el).fadeIn('slow');

        // Queue this item of work.
        IznikPlugin.addWork(this);

        return(this);
    }
});

Iznik.Views.Plugin.Yahoo.SyncMessages = Iznik.Views.Plugin.Work.extend({
    offset: 1,

    chunkSize: 100,

    ageLimit: 31,

    start: function() {
        var self = this;
        this.startBusy();

        // Need to create this here rather than as a property, otherwise the same array is shared between instances
        // of this object.
        this.messages = [];

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

                        if (ret.ret == 0) {
                            // If there are messages which we don't have but the server does, then the server
                            // is wrong and we need to delete them.
                            self.promises = [];
                            _.each(ret.missingonclient, function(missing, index, list) {
                                self.promises.push($.ajaxq('plugin', {
                                    type: "DELETE",
                                    url: API + 'message',
                                    context: self,
                                    data: {
                                        id: missing.id,
                                        collection:  missing.collection,
                                        reason: 'Not present on Yahoo pending'
                                    }
                                }));
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
                                                success: function(ret) {
                                                    missing.deferred.resolve();
                                                }
                                            });
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

    numField: 'numResults',
    idField: 'yahoopendingid',
    dateField: 'postDate',

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

    numField: 'numRecords',
    idField: 'yahooapprovedid',
    dateField: 'date',

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

    chunkSize: 100,

    start: function() {
        var self = this;
        this.startBusy();

        // Need to create this here rather than as a property, otherwise the same array is shared between instances
        // of this object.
        this.members = [];

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
            var members = ret.ygData[this.memberLocation];
            var maxage = null;

            for (var i = 0; i < total; i++) {
                var member = members[i];
                var percent = Math.round((this.offset / total) * 100);
                self.$('.progress-bar:first').css('width',  percent + '%').attr('aria-valuenow', percent);

                var mom = new moment(member['date'] * 1000);
                var thisone = {
                    email: member['email'],
                    yahooUserId: member['userId'],
                    yahooPostingStatus: member['postingStatus'],
                    yahooDeliveryType: member['deliveryType'],
                    yahooModeratorStatus: member.hasOwnProperty('moderatorStatus') ? member['moderatorStatus'] : 'MEMBER',
                    name: member['yid'],
                    date: mom.format()
                };

                this.members.push(thisone);
            }

            if (total == 0 || total < this.chunkSize) {
                // Finished.  Now pass these members to the server.
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

                        if (ret.ret == 0) {
                            // If there are messages which we don't have but the server does, then the server
                            // is wrong and we need to delete them.
                            self.promises = [];
                            _.each(ret.missingonclient, function(missing, index, list) {
                                self.promises.push($.ajaxq('plugin', {
                                    type: "DELETE",
                                    url: API + 'message',
                                    context: self,
                                    data: {
                                        id: missing.id,
                                        collection:  missing.collection,
                                        reason: 'Not present on Yahoo pending'
                                    }
                                }));
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
                                                success: function(ret) {
                                                    missing.deferred.resolve();
                                                }
                                            });
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
                    },
                    error: self.failChunk
                });
            } else {
                this.queue();
            }
        }
    }
});

Iznik.Views.Plugin.Yahoo.ApprovePendingMessage = Iznik.Views.Plugin.Work.extend({
    template: 'plugin_pending_approve',

    server: true,

    start: function() {
        var self = this;
        this.startBusy();

        $.ajaxq('plugin', {
            type: "POST",
            url: YAHOOAPI + 'groups/' + this.model.get('group').nameshort + "/pending/messages",
            data: {
                A: this.model.get('yahoopendingid'),
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
                }
            }, error: function(a,b,c) {
                self.fail();
            }
        });
    }
});

Iznik.Views.Plugin.Yahoo.RejectPendingMessage = Iznik.Views.Plugin.Work.extend({
    template: 'plugin_pending_reject',

    server: true,

    start: function() {
        var self = this;
        this.startBusy();

        $.ajaxq('plugin', {
            type: "POST",
            url: YAHOOAPI + 'groups/' + this.model.get('group').nameshort + "/pending/messages",
            data: {
                A: this.model.get('yahoopendingid'),
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
                }
            }, error: function() {
                self.fail();
            }
        });
    }
});

Iznik.Views.Plugin.Yahoo.ChangeAttribute  = Iznik.Views.Plugin.Work.extend({
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

Iznik.Views.Plugin.Yahoo.DeliveryType  = Iznik.Views.Plugin.Yahoo.ChangeAttribute.extend({
    template: 'plugin_yahoo_delivery',
    attr: 'deliveryType'
});

Iznik.Views.Plugin.Yahoo.PostingStatus = Iznik.Views.Plugin.Yahoo.ChangeAttribute.extend({
    template: 'plugin_yahoo_posting',
    attr: 'postingStatus'
});

var IznikPlugin = new Iznik.Views.Plugin.Main();

