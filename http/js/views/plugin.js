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
            console.log("First item of work", first);

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
    },

    checkPluginStatus: function() {
        var self = this;

        function checkResponse(self) {
            return(function(ret) {
                console.log("checkPluginStatus", self);
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
        console.log("Plugin failed");
        var self = this;
        this.$el.fadeOut(2000, function() {
            self.remove();
            IznikPlugin.completedWork();
        });
    },

    succeed: function() {
        console.log("Plugin succeeded");
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
        console.log("Work Render", this);
        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        $('#js-work').prepend(this.$el).fadeIn('slow');

        // Queue this item of work.
        IznikPlugin.addWork(this);

        return(this);
    }
});

Iznik.Views.Plugin.Yahoo.SyncPending = Iznik.Views.Plugin.Work.extend({
    offset: 0,

    pending: [],

    template: 'plugin_syncpending',

    start: function() {
        var self = this;
        console.log("SyncPending", this);

        $.ajax({
            type: "GET",
            url: YAHOOAPI + self.model.get('nameshort') + "/pending/messages/" + self.offset + "/parts?start=1&count=1000&chrome=raw",
            success: function (ret) {
                if (ret['ygData']) {
                    console.log("Got pending for ", self.model.get('nameshort'));
                    var total = ret['ygData']['numResults'];
                    self.offset += total;
                    var messages = ret['ygData']['pendingMessages'];

                    for (var i = 0; i < total; i++) {
                        var message = messages[i];
                        console.log("Got message", message);
                        //
                        //self.pendings.push({
                        //    groupid: group['groupid'],
                        //    groupname: groupname,
                        //    id: message['msgId'],
                        //    email: message['email'],
                        //    subject: message['subject'],
                        //    yid: message['profile'],
                        //    textbody: $('<div>' + message['messageParts'][0]['textContent'] + '</div>').text(),
                        //    epochgmt: message['postDate']
                        //});
                    }

                    if (total == 0) {
                        // Finished
                        self.succeed();
                    } else {
                        self.queue();
                    }
                }
            },
            error: function (request, status, error) {
                self.fail();
            }
        });
    }
});

var IznikPlugin = new Iznik.Views.Plugin.Main();

