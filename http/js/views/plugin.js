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

    checkWork: function() {
        if (!this.currentItem) {
            // Get any first item of work to do.
            var first = this.work.pop();

            if (first) {
                $('#js-nowork').hide();
                // All work has a start method which triggers action.
                this.currentItem = first;
                first.start();
            } else {
                $('#js-nowork').fadeIn('slow');
            }
        }
    },

    addWork: function(work) {
        this.work.push(work);
    },

    completedWork: function() {
        this.currentItem = null;
        this.checkWork();
    },

    requeueWork: function(work) {
        // This is ongoing - so put to the front.
        this.work.unshift(work);
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
            complete: function() {
                window.setTimeout(_.bind(self.checkPluginStatus, self), 30000);
            }
        })
    }
});

Iznik.Views.Plugin.Info = IznikView.extend({
    className: "panel panel-default js-plugin",

    template: "layout_plugin"
});

Iznik.Views.Plugin.Work = IznikView.extend({
    fail: function() {
        // TODO Should we show failures in some way?
        var self = this;
        this.$el.fadeOut(2000, function() {
            self.remove();
            IznikPlugin.completedWork();
        });
    },

    succeed: function() {
        var self = this;
        this.$el.fadeOut(2000, function() {
            self.remove();
            IznikPlugin.completedWork();
        });
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
        $('#js-work').prepend(this.$el).fadeIn('slow');

        // Queue this item of work.
        IznikPlugin.addWork(this);

        return(this);
    }
});

Iznik.Views.Plugin.Yahoo.Sync = Iznik.Views.Plugin.Work.extend({
    offset: 1,

    chunkSize: 100,

    start: function() {
        var self = this;

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

    processChunk: function(ret) {
        var self = this;

        if (ret.ygData) {
            var total = ret.ygData.numResults;
            this.offset += total;
            var messages = ret.ygData[this.messageLocation];

            for (var i = 0; i < total; i++) {
                var message = messages[i];
                var d = new Date();
                d.setUTCSeconds(message['postDate']);

                this.messages.push({
                    email: message['email'],
                    subject: message['subject'],
                    date: d.toISOString(),
                    yahoopendingid: message['msgId']
                });
            }

            if (total == 0 || total < this.chunkSize) {
                // Finished.  Now check with the server whether we have any messages which it doesn't.
                $.ajax({
                    type: "POST",
                    url: API + 'correlate',
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
                            var promises = [];
                            _.each(ret.missingonclient, function(missing, index, list) {
                                promises.push($.ajax({
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

                                $.ajax({
                                    type: "GET",
                                    url: self.sourceurl(missing['yahoopendingid']),
                                    context: self,
                                    success: function(ret) {
                                        console.log("Get source", ret);
                                        if (ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('rawEmail')) {
                                            console.log("Got email ", ret.ygData.rawEmail);
                                        }
                                    }
                                });
                            });

                            $.when(promises).then(function() {
                                // All the deletes have completed.
                            });
                        } else {
                            self.failChunk();
                        }
                    },
                    error: self.failChunk
                });

                this.succeed();
            } else {
                this.queue();
            }
        }
    }
});

Iznik.Views.Plugin.Yahoo.SyncPending = Iznik.Views.Plugin.Yahoo.Sync.extend({
    template: 'plugin_syncpending',

    messageLocation: 'pendingMessages',

    collections: [
        'messages_pending',
        'messages_spam'
    ],

    url: function() {
        return YAHOOAPI + this.model.get('nameshort') + "/pending/messages/" + this.offset +
            "/parts?start=1&count=" + this.chunkSize + "&chrome=raw"
    },

    sourceurl: function(id) {
        return YAHOOAPI + this.model.get('nameshort') + '/pending/messages/' + id + '/raw'
    }
});

var IznikPlugin = new Iznik.Views.Plugin.Main();

