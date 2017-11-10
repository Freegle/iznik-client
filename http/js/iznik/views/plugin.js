define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    'iznik/models/config/bulkop',
    'iznik/models/yahoo/user',
    'iznik/models/group'
], function($, _, Backbone, moment, Iznik) {
    // Plugin work.
    //
    // We have a collection for the work items, some of which come from the server, and some of which are generated
    // on the client.  We use a collectionview to render these.  The model contains information about which specific
    // view we want.

    var cantban = [];
    var cantremove = [];
    
    Iznik.Models.Plugin.Work = Iznik.Model.extend({
        initialize: function() {
            this.set('added', (new Date()).getTime());
        },
    
        retry: function() {
            //console.log("Retry work", this);
            this.set('running', false);
            var count = this.get('retrycount');
            count = count ? (count + 1) : 1;
            this.set('retrycount', count);
    
            this.collection.sort();
            _.delay(function() {
                window.IznikPlugin.checkWork();
            }, Math.max(30000, 500 * 2 ^ this.get('retrycount')));
        },
    
        requeue: function() {
            this.set('running', false);
            this.collection.sort();
            window.IznikPlugin.checkWork();
        }
    });
    
    Iznik.Collections.Plugin = Iznik.Collection.extend({
        model: Iznik.Models.Plugin.Work,
    
        initialize: function (models, options) {
            this.options = options;
    
            this.comparator = function(a, b) {
                var ret;
    
                // Running work comes first.
                if (a.get('running')) {
                    return(-1);
                } else if (b.get('running')) {
                    return (1)
                } else {
                    // Retrying work goes later
                    if (!a.get('retrycount') && b.get('retrycount')) {
                        ret = -1;
                    } else if (a.get('retrycount') && !b.get('retrycount')) {
                        ret = 1;
                    } else {
                        if (a.get('retrycount') != b.get('retrycount')) {
                            ret = a.get('retrycount') - b.get('retrycount');
                        } else {
                            // Bulk work goes later.
                            if (!a.get('bulk') && b.get('bulk')) {
                                ret = -1;
                            } else if (a.get('bulk') && !b.get('bulk')) {
                                ret = 1;
                            } else {
                                // By time.
                                ret = b.get('added') - a.get('added');
                            }
                        }
                    }
                }
    
                return(ret);
            }
        }
    });
    
    Iznik.Views.Plugin.Main = Iznik.View.extend({
        className: "padbotbig panel panel-default js-plugin",
        template: "layout_plugin",

        connected: false,
        everConnected: false,
        confirmedMod: false,


        yahooGroups: [],
        yahooGroupsWithPendingMessages: [],
        yahooGroupsWithPendingMembers: [],

        render: function() {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'modtools_layout_background';
                v.render().then(function(v) {
                    self.$('.js-background').html(v.el);

                    // We use a collectionview to display the work items
                    self.collection = new Iznik.Collections.Plugin();
                    self.collectionView = new Backbone.CollectionView( {
                        el : self.$('.js-work'),
                        modelView : Iznik.Views.Plugin.Work,
                        collection: self.collection,
                        processKeyEvents: false
                    } );

                    // Update our count when the number of work items changes.
                    self.listenTo(self.collection, 'add remove', self.updatePluginCount);

                    // Also check for work to do when a new item is added
                    self.listenTo(self.collection, 'add', self.checkWork);

                    self.collectionView.render();

                    self.checkPluginStatus();
                });
            });

            return(p);
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
            var self = this;

            function doSync(group) {
                // Whether we start a sync depends on whether we are an active mod.  This allows people
                // who are on many groups as a backup not to have absurdly large numbers of syncs going on.
                var sync = false;

                if (group.get('onyahoo') && (group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                    var settings = group.get('mysettings');
                    //console.log("doSync", key, group, settings);
                    sync = !settings.hasOwnProperty('active') || settings['active'];
                }

                // We rely on groupid being present when we get the crumb.
                group.set('groupid', group.get('id'));

                //console.log("doSync", group.get('nameshort'), key, sync, group);
                return(sync);
            }

            function worthIt(yahoocounts, group, countname) {
                // It's worth doing a sync if we know there is work on Yahoo, or if we know that there is work on ModTools.
                //
                // This avoids doing syncs which will definitely do nothing, which can be the case for people with a lot
                // of groups.
                // console.log("Worthit", group.get('nameshort'));
                // console.log("Work on Yahoo", yahoocounts.indexOf(group.get('nameshort').toLowerCase()) != -1);
                // console.log("Work on MT", group.get('work'));

                var worthit = yahoocounts.indexOf(group.get('nameshort').toLowerCase()) != -1 ||
                        presdef(countname, group.get('work'), 0);

                return(worthit);
            }

            // Only start the syncs if there is no other work to do or never sync'd.
            //console.log("Consider start syncs", window.IznikPlugin.collection.length, window.IznikPlugin.notsynced);
            if (window.IznikPlugin.collection.length == 0 || window.IznikPlugin.notsynced) {
                // Start pending syncs first because if they're wrong, that's normally more annoying.
                //
                // If we only have a few groups, sync them all, as Yahoo has issues with the counts being wrong
                // sometimes.
                var numgroups = Iznik.Session.get('groups').length;
                window.IznikPlugin.notsynced = false;

                Iznik.Session.get('groups').each(function (group) {
                    // We know from our Yahoo scan whether there is any work to do.
                    if (numgroups.length < 5 || worthIt(self.yahooGroupsWithPendingMessages, group, 'pending') &&
                        doSync(group)) {
                        // console.log("Sync pending messages for", group.get('nameshort'));
                        self.collection.add(new Iznik.Models.Plugin.Work({
                            id: group.get('nameshort') + '.SyncMessages.Pending',
                            subview: new Iznik.Views.Plugin.Yahoo.SyncMessages.Pending({
                                model: group
                            }),
                            bulk: false
                        }));
                    }

                    if (numgroups.length < 5 || worthIt(self.yahooGroupsWithPendingMembers, group, 'pendingmembers') &&
                        doSync(group)) {
                        self.collection.add(new Iznik.Models.Plugin.Work({
                            id: group.get('nameshort') + '.SyncMembers.Pending',
                            subview: new Iznik.Views.Plugin.Yahoo.SyncMembers.Pending({
                                model: group
                            }),
                            bulk: false
                        }));
                    }
                });

                Iznik.Session.get('groups').each(function (group) {
                    if (doSync(group)) {
                        var lastsync = group.get('lastyahoomessagesync');
                        var last = moment(lastsync);
                        var hoursago = moment.duration(now.diff(last)).asHours();

                        if ((_.isUndefined(lastsync) || hoursago >= 1) && doSync(group)) {
                            self.collection.add(new Iznik.Models.Plugin.Work({
                                id: group.get('nameshort') + '.SyncMessages.Approved',
                                subview: new Iznik.Views.Plugin.Yahoo.SyncMessages.Approved({
                                    model: group
                                }),
                                bulk: true
                            }));
                        }
                    }
                });

                Iznik.Session.get('groups').each(function (group) {
                    //console.log("Consider membersync", group.get('nameshort'), group.get('lastyahoomembersync'), doSync(group));
                    if (doSync(group)) {
                        var lastsync = group.get('lastyahoomembersync');
                        var last = moment(lastsync);
                        var hoursago = moment.duration(now.diff(last)).asHours();

                        if ((_.isUndefined(lastsync) || hoursago >= 24) && !group.get('membersyncpending') && doSync(group, 'showmembers')) {
                            self.collection.add(new Iznik.Models.Plugin.Work({
                                id: group.get('nameshort') + '.SyncMembers.Approved',
                                subview: new Iznik.Views.Plugin.Yahoo.SyncMembers.Approved({
                                    model: group
                                }),
                                bulk: true
                            }));
                        }
                    }
                });
            }

            // Sync regularly.  Most changes will be picked up by the session poll, but it's possible
            // that someone will delete messages directly on Yahoo which we need to notice have gone, or
            // if Yahoo is not sending out email notifications then we won't find out anything until we
            // sync via the plugin.
            //
            // Delay doesn't set the right context by default.
            _.delay(_.bind(this.listYahooGroups, this), 60000);
        },

        // TODO This whole callback approach is old code and should use promises or something.
        getCrumb: function(groupname, crumblocation, success, fail, drop) {
            // There's a bit of faffing to get a crumb from Yahoo to perform our actions.
            var self = this;

            return(function() {

                function parseCrumb(ret) {
                    var match = /GROUPS.YG_CRUMB = "(.*)"/.exec(ret);

                    if (ret.indexOf("not allowed to perform this operation") !== -1) {
                        // Can't do this - no point keeping the work.
                        drop.call(self);
                    } else if (match) {
                        success.call(self, match[1]);
                    } else {
                        var match = /window.location.href = "(.*)"/.exec(ret);

                        if (match) {
                            var url = match[1];
                            $.ajax({
                                type: "GET",
                                url: url,
                                success: parseCrumb,
                                error: function (request, status, error) {
                                    console.log("Redirect error", status, error);
                                    fail.call(self);
                                }
                            });
                        }
                    }
                }

                var url = "https://groups.yahoo.com/neo/groups/" + groupname + crumblocation + "?" + Math.random();
                // console.log("Get crumb", url);

                $.ajax({
                    type: "GET",
                    url: url,
                    success: parseCrumb,
                    error: function (request, status, error) {
                        console.log("Get crumb error", status, error);
                        fail.call(self);
                    }
                });
            });
        },

        checkWork: function() {
            var self = this;

            if (self.connected) {
                // Get any first item of work to do.
                var first = this.collection.at(0);

                if (first && !first.get('running')) {
                    first.set('running', true);
                    //console.log("First item", first);

                    var groupname;
                    var v = first.get('subview');
                    var mod = v.model;

                    if (mod.get('groupid')) {
                        // Get a crumb from the relevant group
                        var group = Iznik.Session.getGroup(mod.get('groupid'));
                        groupname = group.get('nameshort');
                        //console.log("Get relevant crumb", groupname, first);
                    } else {
                        // We're not acting on a specific group.  Get a crumb from one of ours.
                        var groups = Iznik.Session.get('groups');
                        groupname = groups && groups.length > 0 ? groups.at(0).get('nameshort') : null;
                        //console.log("Get first crumb", groupname, first);
                    }

                    // We need a crumb to do the work.
                    self.getCrumb(groupname, v.crumbLocation, function(crumb) {
                        v.crumb = crumb;
                        // console.log("Start", crumb);
                        v.start.call(v);
                    }, function() {
                        var f = self.collection.at(0);
                        if (f) {
                            f.retry();
                        }
                    }, function() {
                        v.drop.call(v);
                    })();
                }
            }
        },

        updatePluginCount: function() {
            var count = this.collection.length;

            if (count > 0) {
                $('.js-plugincount').html(count).show();
                $('#js-nowork').hide();
            } else {
                $('.js-plugincount').empty().hide();
                $('#js-nowork').fadeIn('slow');
            }

            this.count = count;
        },

        checkPluginStatus: function() {
            var self = this;

            function checkResponse(self) {
                return(function(ret) {
                    if (ret && ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('allMyGroups')) {
                        $('.js-pluginonly').show();
                        $('#js-loginbuildup').fadeOut('slow');

                        if (!self.connected) {
                            self.resume();

                            if (!self.everConnected) {
                                // The plugin state might flipflop between connected and disconnected.  We don't want
                                // to trigger invitations each time.
                                _.delay(_.bind(self.listYahooGroups, self), 5000);
                            }

                            self.everConnected = true;
                        }

                        $('#js-plugindisconnected').fadeOut('slow', function() {
                            $('#js-pluginconnected').fadeIn('slow');
                            $('#js-pluginbuildup').hide();
                        })
                    } else {
                        $('.js-pluginonly').hide();

                        if (self.connected) {
                            self.pause();
                        }

                        $('#js-pluginconnected').fadeOut('slow', function() {
                            $('#js-plugindisconnected').fadeIn('slow');
                        });
                    }
                });
            }

            // Check if we have any plugin work to do from the server.
            var hoursago = 0;
            var now = new moment();

            $.ajax({
                type: 'GET',
                url: API + 'plugin',
                success: function(ret) {
                    if (ret.ret == 0) {
                        _.each(ret.plugin, function(work, index, list) {
                            var added = new moment(work.added);
                            var duration = moment.duration(now.diff(added));
                            var hours = duration.asHours();
                            hoursago = hoursago > hours ? hoursago : hours;
                            //console.log("Work ago", work.added, hours, hoursago, work);

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
                            // out when we add it, because we put an id in it, and collections do that.
                            if (work.hasOwnProperty('groupid')) {
                                // Find our group and add it in.
                                work.group = Iznik.Session.getGroup(work.groupid);

                                if (!work.group) {
                                    // We don't know about this group yet.  Skip this item.
                                    return;
                                }

                                work.group = work.group.toJSON2();
                            }

                            switch (work.type) {
                                case 'ApprovePendingMessage': {
                                    self.collection.add(new Iznik.Models.Plugin.Work({
                                        id: work.id,
                                        subview: new Iznik.Views.Plugin.Yahoo.ApprovePendingMessage({
                                            model: new Iznik.Model(work)
                                        })
                                    }));
                                    break;
                                }

                                case 'RejectPendingMessage': {
                                    self.collection.add(new Iznik.Models.Plugin.Work({
                                        id: work.id,
                                        subview: new Iznik.Views.Plugin.Yahoo.RejectPendingMessage({
                                            model: new Iznik.Model(work)
                                        })
                                    }));
                                    break;
                                }

                                case 'DeleteApprovedMessage': {
                                    self.collection.add(new Iznik.Models.Plugin.Work({
                                        id: work.id,
                                        subview: new Iznik.Views.Plugin.Yahoo.DeleteApprovedMessage({
                                            model: new Iznik.Model(work),
                                            crumbLocation: "/conversations/messages/" + work.id
                                        })
                                    }));
                                    break;
                                }

                                case 'DeliveryType': {
                                    self.collection.add(new Iznik.Models.Plugin.Work({
                                        id: work.id,
                                        subview: new Iznik.Views.Plugin.Yahoo.DeliveryType({
                                            model: new Iznik.Model(work)
                                        })
                                    }));
                                    break;
                                }

                                case 'PostingStatus': {
                                    self.collection.add(new Iznik.Models.Plugin.Work({
                                        id: work.id,
                                        subview: new Iznik.Views.Plugin.Yahoo.PostingStatus({
                                            model: new Iznik.Model(work)
                                        })
                                    }));
                                    break;
                                }

                                case 'ApprovePendingMember': {
                                    self.collection.add(new Iznik.Models.Plugin.Work({
                                        id: work.id,
                                        subview: new Iznik.Views.Plugin.Yahoo.ApprovePendingMember({
                                            model: new Iznik.Model(work)
                                        })
                                    }));
                                    break;
                                }

                                case 'RejectPendingMember':
                                case 'RemovePendingMember': {
                                    self.collection.add(new Iznik.Models.Plugin.Work({
                                        id: work.id,
                                        subview: new Iznik.Views.Plugin.Yahoo.RejectPendingMember({
                                            model: new Iznik.Model(work)
                                        })
                                    }));
                                    break;
                                }

                                case 'RemoveApprovedMember': {
                                    if (!cantremove[work.groupid]) {
                                        self.collection.add(new Iznik.Models.Plugin.Work({
                                            id: work.id,
                                            subview: new Iznik.Views.Plugin.Yahoo.RemoveApprovedMember({
                                                model: new Iznik.Model(work)
                                            })
                                        }));
                                    }
                                    break;
                                }

                                case 'BanPendingMember':
                                case 'BanApprovedMember': {
                                    if (!cantban[work.groupid]) {
                                        self.collection.add(new Iznik.Models.Plugin.Work({
                                            id: work.id,
                                            subview: new Iznik.Views.Plugin.Yahoo.BanApprovedMember({
                                                model: new Iznik.Model(work)
                                            })
                                        }));
                                    }
                                    break;
                                }

                                case 'Invite': {
                                    var mod = new Iznik.Model(work);
                                    mod.set('nameshort', work.group.nameshort);
                                    self.collection.add(new Iznik.Models.Plugin.Work({
                                        id: work.id,
                                        subview: new Iznik.Views.Plugin.Yahoo.Invite({
                                            model: mod
                                        })
                                    }));
                                    break;
                                }
                            }
                        });

                        // Now bulk ops due
                        _.each(ret.bulkops, function(bulkop) {
                            var mod = Iznik.Session.getGroup(bulkop.groupid);
                            if (mod) {
                                var bmod = new Iznik.Models.ModConfig.BulkOp(bulkop);

                                // Record bulk op started on server.
                                var started = (new moment()).format();
                                bmod.set('runstarted', started);
                                bmod.save({
                                    id: bulkop.id,
                                    groupid: bulkop.groupid,
                                    runstarted: started
                                }, { patch: true });

                                // Set id so that the duplicate checking works.  There might be an overlap between this and
                                // other ids above, but if so, we'll just not do a work item until that clash clears.
                                switch (bulkop.action) {
                                    case 'Unbounce': {
                                        self.collection.add(new Iznik.Models.Plugin.Work({
                                            id: bulkop.id,
                                            subview: new Iznik.Views.Plugin.Yahoo.Unbounce({
                                                model: mod
                                            }),
                                            bulk: true
                                        }));
                                        break;
                                    }

                                    case 'Remove': {
                                        mod.set('bouncingfor', bulkop.bouncingfor);
                                        self.collection.add(new Iznik.Models.Plugin.Work({
                                            id: bulkop.id,
                                            subview: new Iznik.Views.Plugin.Yahoo.RemoveBouncing({
                                                model: mod
                                            }),
                                            bulk: true
                                        }));
                                        break;
                                    }

                                    case 'ToSpecialNotices': {
                                        self.collection.add(new Iznik.Models.Plugin.Work({
                                            id: bulkop.id,
                                            subview: new Iznik.Views.Plugin.Yahoo.ToSpecialNotices({
                                                model: mod,
                                                bulkop: bulkop
                                            }),
                                            bulk: true
                                        }));
                                        break;
                                    }

                                    default: {
                                        console.log("Ignore bulkop");
                                    }
                                }
                            }
                        });

                        if (!Storage.get('dontshowpluginbuildup')) {
                            if (hoursago >= 4 && !self.connected) {
                                $('#js-pluginbuildup').fadeIn('slow');

                                $('.js-hidepluginbuildup').one('click', function(e) {
                                    Storage.set('dontshowpluginbuildup', true);
                                    e.preventDefault();
                                    e.stopPropagation();
                                    console.log("Hide buildi");
                                    $('#js-pluginbuildup').hide();
                            });
                            } else {
                                $('#js-pluginbuildup').hide();
                            }
                        }

                        // Now look for work which has been removed from the server because it isn't necessary any more.
                        self.collection.each(function(item) {
                            if (item.get('server')) {
                                var got = false;

                                _.each(ret.plugin, function (work, index, list) {
                                    if (item.model.get('id') == work.id) {
                                        got = true;
                                    }
                                });

                                if (!got) {
                                    // This item of work no longer needs doing by us, so remove it from the list.
                                    console.log("No longer needed", item);
                                    self.collection.remove(item);
                                }
                            }
                        });

                        self.checkWork();
                    }

                    // Get our session, both to keep it alive and update any counts.
                    self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                        var first = self.collection.at(0);
                        //console.log("checkPluginStatus, first", first);
                        if (first && first.get('running')) {
                            // We are in the middle of work.  Don't query Yahoo as we'll break our crumb.
                            //console.log("Running item - don't query");
                            window.setTimeout(_.bind(self.checkPluginStatus, self), 10000);
                        } else {
                            // Check if we are connected to Yahoo by issuing an API call.
                            //console.log("Not running item - query Yahoo");
                            new majax({
                                type: 'GET',
                                url: 'https://groups.yahoo.com/api/v1/user/groups/all',
                                success: checkResponse(self),
                                error: checkResponse(self),
                                complete: function() {
                                    window.setTimeout(_.bind(self.checkPluginStatus, self), 10000);
                                }
                            });
                        }
                    });

                    Iznik.Session.testLoggedIn();
                }
            })
        },

        listYahooGroups: function() {
            // We get a list of all the groups on Yahoo so that we can see whether there are groups on the server
            // for which we need to update our mod status.
            // console.log("List Yahoo groups");
            this.yahooGroupStart = 1;
            this.getYahooGroupChunk();
        },

        getYahooGroupChunk: function() {
            // If this fails, we just won't finish checking, and therefore won't make any changes, which is probably
            // the best option.
            $.ajax({
                type: "GET",
                context: this,
                url: YAHOOAPI + 'user/groups/all?start=' + this.yahooGroupStart + "&count=20&sortOrder=asc&orderBy=name&chrome=raw",
                success: this.processYahooGroupChunk
            });
        },

        processYahooGroupChunk: function(ret) {
            var self = this;

            if (ret.hasOwnProperty('ygData')) {
                if (ret.ygData.hasOwnProperty('allMyGroups') && ret.ygData.allMyGroups.length > 0) {
                    _.each(ret.ygData.allMyGroups, function(group) {
                        if (group.membership == "MOD" || group.membership == "OWN") {
                            self.yahooGroups.push(group.groupName.toLocaleLowerCase());

                            if (group.hasOwnProperty('pendingCountMap') &&
                                (group.pendingCountMap.hasOwnProperty('MESSAGE_COUNT') && group.pendingCountMap.MESSAGE_COUNT != 0)) {
                                self.yahooGroupsWithPendingMessages.push(group.groupName.toLocaleLowerCase());
                                //console.log("Pending messages on ", group.groupName);
                            }

                            if (group.hasOwnProperty('pendingCountMap') &&
                                (group.pendingCountMap.hasOwnProperty('MEM_COUNT') && group.pendingCountMap.MEM_COUNT != 0)) {
                                self.yahooGroupsWithPendingMembers.push(group.groupName.toLocaleLowerCase());
                            }
                        }
                    });

                    if (ret.ygData.allMyGroups.length > 0) {
                        this.yahooGroupStart += 20;
                        this.getYahooGroupChunk();
                    }
                } else {
                    // We've got all the groups we're an owner/mod on.
                    //
                    // We can start our group syncs now, and also pick up any plugin work to do.
                    self.startSyncs();
                    self.checkWork();

                    if (Iznik.Session.get('me').yahooid == Iznik.Session.get('loggedintoyahooas')) {
                        // Although we'll do syncs and work with any Yahoo ID we happen to be logged into Yahoo with, we
                        // only want to auto-add if they're the same as the ID they're using on ModTools.
                        //
                        // Normally that will be the case, and it's the simple case for which we want the auto-add to work.
                        // But it's possible that people are loggingin to Yahoo and ModTools as someone else for some
                        // reason (when helping someone else out) and we don't want to get a confusing set of memberships
                        // in that case.
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
                        console.log("Yahoo groups", self.yahooGroups);
                        console.log("Server groups", serverGroups);
                        console.log("Mod on Yahoo but not server", serverMissing);
                        console.log("Mod on server but but not Yahoo", yahooMissing);
                        console.log("NameToId", nameToId);
                        console.log("Session", Iznik.Session);

                        // If we're a mod on the server but not on Yahoo, then we need to demote ourselves.  But
                        // doing this might cause us to lose groups if we log in with multiple Yahoo IDs.  So we
                        // should only do this if we are sure.  It'll get picked up on the next member sync anyway
                        // so it's not vital.  TODO
                        // _.each(yahooMissing, function(demote) {
                        //     $.ajax({
                        //         url: API + 'memberships',
                        //         type: 'POST',
                        //         headers: {
                        //             'X-HTTP-Method-Override': 'PATCH'
                        //         },
                        //         data: {
                        //             userid: Iznik.Session.get('me').id,
                        //             groupid: nameToId[demote],
                        //             role: 'Member'
                        //         }
                        //     })
                        // });

                        if (!self.confirmedMod) {
                            // If we're a mod on Yahoo but not on the server, and it's a group the server knows about,
                            // then we need to prove to the server that we're a mod so that we can auto-add it to
                            // our list of groups.  We do this by triggering an invitation, which is something only mods
                            // can do.  We shuffle the array as Yahoo has an invitation limit, and we don't want to
                            // get stuck.
                            //
                            // No point doing too many as Yahoo has a limit on invitations.
                            self.confirmedMod = true;
                            serverMissing = _.shuffle(serverMissing);

                            _.each(serverMissing, function(group) {
                                console.log("Confirm mod status on", group);
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
                                            console.log("Confirm mod status server returned", group, ret);
                                            if (ret.ret == 0) {
                                                console.log("Confirm mod to server on on", group);
                                                var email = 'modconfirm-' + g.get('id') + '-' +
                                                    Iznik.Session.get('me').id + '-' + ret.key + '@' + location.host;

                                                self.collection.add(new Iznik.Models.Plugin.Work({
                                                    subview: new Iznik.Views.Plugin.Yahoo.ConfirmMod({
                                                        model: new Iznik.Model({
                                                            nameshort: group,
                                                            email: email
                                                        })
                                                    })
                                                }));
                                            }
                                        }
                                    })
                                });
                            });
                        }
                    }
                }
            }
        }
    });

    Iznik.Views.Plugin.Work = Iznik.View.extend({
        tagName: 'li',

        render: function() {
            // This view is just a wrapper - the meat of the view is in the subview, so get that back and render it.
            var self = this;

            var v = this.model.get('subview');
            v.render().then(function(v) {
                self.$el.html(v.el);
            })
            
            return(this);
        }
    });
    
    Iznik.Views.Plugin.SubView = Iznik.View.extend({
        startBusy: function() {
            // Change icon
            this.$('.glyphicon-time, glyphicon-warning-sign').removeClass('glyphicon-time, glyphicon-warning-sign').addClass('glyphicon-refresh rotate');
        },
    
        requeue: function() {
            window.IznikPlugin.collection.at(0).requeue();
        },
    
        drop: function() {
            window.IznikPlugin.collection.shift();
            window.IznikPlugin.checkWork();
        },
    
        fail: function() {
            this.$('.glyphicon-refresh').removeClass('glyphicon-refresh rotate').addClass('glyphicon-warning-sign');
            var work = window.IznikPlugin.collection.at(0);

            if (work) {
                work.retry();
            }
        },
    
        succeed: function() {
            var self = this;

            function finished() {
                //console.log("Finished work item", this);
                window.IznikPlugin.collection.shift();
                window.IznikPlugin.checkWork();
            }
    
            if (self.server) {
                // This work came from the server - record the success there.
                //
                // Even if this fails, continue.
                //console.log("Server", self.model.get('workid'));
                $.ajax({
                    type: "POST",
                    headers: {
                        'X-HTTP-Method-Override': 'DELETE'
                    },
                    url: API + 'plugin',
                    data: {
                        id: self.model.get('workid')
                    }, complete: _.bind(finished, self)
                });
            } else {
                // Not on server - just remove
                //console.log("Not on server");
                finished.call(this);
            }
        }
    })
    
    Iznik.Views.Plugin.Yahoo.SyncMessages = Iznik.Views.Plugin.SubView.extend({
        offset: 1,
    
        chunkSize: 10,
    
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

                    if (age < self.ageLimit) {
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
                }

                // console.log("Finished?", self.url(), total, this.chunkSize, maxage, self.ageLimit);

                if (total == 0 || total < this.chunkSize || maxage >= self.ageLimit || (ret.ygData.hasOwnProperty('nextPageStart') && ret.ygData.nextPageStart === 0)) {
                    // Finished.  Now check with the server whether we have any messages which it doesn't.
                    $.ajax({
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
                                    //
                                    // Do a localonly delete so that we don't generate any plugin work.  This means that
                                    // if Yahoo is lying to us, we won't trigger a reject.
                                    self.promises = [];
                                    _.each(ret.missingonclient, function(missing, index, list) {
                                        if (self.deleteAllMissing || missing[self.dateField] > self.earliest) {
                                            self.promises.push($.ajax({
                                                type: "POST",
                                                headers: {
                                                    'X-HTTP-Method-Override': 'DELETE'
                                                },
                                                url: API + 'message',
                                                context: self,
                                                data: {
                                                    id: missing.id,
                                                    groupid: self.model.get('id'),
                                                    collection: missing.collection,
                                                    localonly: true,
                                                    reason: 'Not present on Yahoo'
                                                }
                                            }));
                                            console.log("Promise delete", self.promisesCount, self.promisesLen);
                                        }
                                    });
    
                                    // If there are messages which we have but the server doesn't, then the server is
                                    // wrong and we need to add them.
                                    //
                                    // We need a closure to ensure we resolve the right promise.
                                    function handleMissing(missing) {
                                        return(function() {
                                            var url = self.sourceurl(missing[self.idField]);
                                            // console.log("Handle missing", missing, url);
                                            $.ajax({
                                                type: "GET",
                                                url: url,
                                                context: self,
                                                success: function(ret) {
                                                    // console.log("Returned", url, ret);
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

                                                            $.ajax({
                                                                type: "POST",
                                                                headers: {
                                                                    'X-HTTP-Method-Override': 'PUT'
                                                                },
                                                                url: API + 'messages',
                                                                data: data,
                                                                context: self,
                                                                success: function (ret) {
                                                                    if (ret.ret == 0) {
                                                                        missing.deferred.resolve();
                                                                    } else {
                                                                        console.error("Message sync error", ret);
                                                                        missing.deferred.resolve();
                                                                    }
                                                                }, error: function() {
                                                                    // If we failed to sync, we will pick it up next time.
                                                                    console.log("Message post failed", url, self.model.get('nameshort'));
                                                                    missing.deferred.resolve();
                                                                }
                                                            });
                                                        } else {
                                                            // This is an edited message, which is all messed up and difficult
                                                            // to sync.  Ignore it.
                                                            console.log("Can't sync edited message", url, self.model.get('nameshort'), ret);
                                                            missing.deferred.resolve();
                                                        }
                                                    } else {
                                                        // Couldn't fetch.  Not much we can do - Yahoo has some messages
                                                        // which are not accessible.
                                                        console.log("Couldn't fetch", url, self.model.get('nameshort'), ret);
                                                        missing.deferred.resolve();
                                                    }
                                                }, error: function(req, status, error) {
                                                    // Couldn't fetch.  Not much we can do - Yahoo has some messages
                                                    // which are not accessible.
                                                    console.log("Couldn't fetch message", status);
                                                    missing.deferred.resolve();
                                                }
                                            });
                                        })
                                    }
                                    _.each(ret.missingonserver, function(missing) {
                                        missing.deferred = new $.Deferred();
                                        self.promises.push(missing.deferred.promise());
                                        // console.log("Promise missing", missing, self.promisesCount, self.promisesLen)

                                        handleMissing(missing)();
                                    });
    
                                    // Record how many there are and update progress bar
                                    self.promisesLen = self.promises.length;
                                    self.promisesCount = 0;
                                    _.each(self.promises, function(promise) {
                                        promise.done(function() {
                                            self.promisesCount++;
                                            self.ourSyncProgressBar.apply(self);
                                            // console.log("Promise resolved", promise, self.promisesCount, self.promisesLen);

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
                    this.requeue();
                }
            }
        },
    
        render: function() {
            var p = Iznik.Views.Plugin.SubView.prototype.render.call(this);
            p.then(function(self) {
                self.ourSyncProgressBar();
            });
            return(p);
        }
    });
    
    Iznik.Views.Plugin.Yahoo.SyncMessages.Pending = Iznik.Views.Plugin.Yahoo.SyncMessages.extend({
        template: 'plugin_sync_messages_pending',
    
        messageLocation: 'pendingMessages',
        crumbLocation: "/management/pendingmessages",
    
        numField: 'numResults',
        idField: 'yahoopendingid',
        dateField: 'postDate',
        notsynced: true,

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
    
        template: 'plugin_sync_messages_approved',
    
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
    
    Iznik.Views.Plugin.Yahoo.SyncMembers = Iznik.Views.Plugin.SubView.extend({
        offset: 1,
    
        crumbLocation: "/members/all",
    
        chunkSize: 100,
        promisesCount: 0,
        promoteModTools: false,
    
        start: function() {
            var self = this;

            self.synctime = moment().format();
            self.progressBar();
    
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
    
                    // Yahoo ids sometimes have the form of an email address, e.g. for BT users.  But it's the LHS that
                    // counts.
                    var yid = member['yid'];
                    var p = yid ? yid.indexOf('@') : -1;
    
                    if (p >= 0) {
                        yid = yid.substring(0, p);
                    }
    
                    var thisone = {
                        email: member['email'],
                        yahooUserId: member['userId'],
                        yahooid: yid,
                        yahooAlias: member['yalias'],
                        yahooPostingStatus: member.hasOwnProperty('postingStatus') ? member.postingStatus : null,
                        yahooDeliveryType: member.hasOwnProperty(self.deliveryField) ? member[self.deliveryField] : null,
                        yahooModeratorStatus: member.hasOwnProperty('moderatorStatus') ? member.moderatorStatus : 'MEMBER',
                        joincomment: member.hasOwnProperty('joinComment') ? member.joinComment : null,
                        name: member['yid'],
                        date: mom.format()
                    };

                    if (self.promoteModTools && member.email == 'modtools@modtools.org' && !member.hasOwnProperty('moderatorStatus')) {
                        // ModTools has joined the group, but we need it to be a moderator with certain permissions.
                        console.log("Need to promote ModTools");
                        var data = {
                            "moderatorStatus": "MODERATOR",
                            "memberStatus":"CONFIRMED",
                            "postStatus":"MODERATED",
                            "deliveryType":"SINGLE",
                            "resourceTypeCapabilities":[
                                {
                                    "resourceType":"PENDING_MESSAGE",
                                    "capabilities":[{"name":"UPDATE"}]
                                },
                                {
                                    "resourceType":"MEMBER",
                                    "capabilities":[{"name":"UPDATE"}]
                                }
                            ],
                            "notifyBits":7,
                            "fileAccess":true
                        };

                        $.ajax({
                            type: 'POST',
                            url: YAHOOAPI + 'groups/' + self.model.get('nameshort') + '/members/users/' + member['userId'] + '/membership?gapi_crumb=' + self.crumb,
                            data: {
                                membership: JSON.stringify(data)
                            },
                            success: function(ret) {
                                console.log("ModTools promote returned", ret)
                            },
                            error: function(a,b,c) {
                                console.log("ModTools promote returned", a,b,c);
                            }
                        });
                    }
    
                    self.members.push(thisone);
                });
    
                if (total == 0 || members.length < this.chunkSize) {
                    // Finished.
    
                    if (typeof self.completed === 'function') {
                        // We have a custom callback
                        self.completed(self.members);

                    } else {
                        // Pass to server
                        $.ajax({
                            type: 'POST',
                            headers: {
                                'X-HTTP-Method-Override': 'PATCH'
                            },
                            url: API + 'memberships',
                            context: self,
                            data: {
                                groupid: this.model.get('id'),
                                collection: this.collection,
                                synctime: self.synctime,
                                members: this.members,
                                memberspresentbutempty: this.members.length == 0
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
                    }
                } else {
                    this.requeue();
                }
            }
        },
    
        render: function() {
            var p = Iznik.Views.Plugin.SubView.prototype.render.call(this);
            p.then(function(self) {
                self.progressBar();
            });
            return(p);
        }
    });
    
    Iznik.Views.Plugin.Yahoo.SyncMembers.Approved = Iznik.Views.Plugin.Yahoo.SyncMembers.extend({
        // Setting offset to 0 omits start from first one
        offset: 0,
    
        template: 'plugin_sync_members_approved',
    
        crumbLocation: "/members/all",
        memberLocation: 'members',
    
        numField: 'total',
        dateField: 'date',
        deliveryField: 'deliveryType',
        promoteModTools: true,
    
        collection: 'Approved',
    
        url: function() {
            var url = YAHOOAPI + 'groups/' + this.model.get('nameshort') + "/members/confirmed?count=" + this.chunkSize + "&chrome=raw"
    
            if (this.offset) {
                url += "&start=" + this.offset;
            }
    
            return(url);
        }
    });
    
    Iznik.Views.Plugin.Yahoo.SyncMembers.Pending = Iznik.Views.Plugin.Yahoo.SyncMembers.extend({
        // Setting offset to 0 omits start from first one
        offset: 0,
    
        template: 'plugin_sync_members_pending',
    
        crumbLocation: "/management/pendingmembers",
        memberLocation: 'members',
    
        numField: 'total',
        dateField: 'dateCreated',
        deliveryField: 'messageDelivery',
    
        collection: 'Pending',
    
        url: function() {
            var url = YAHOOAPI + 'groups/' + this.model.get('nameshort') + "/pending/members?count=" + this.chunkSize + "&chrome=raw"
    
            if (this.offset) {
                url += "&start=" + this.offset;
            }
    
            return(url);
        }
    });
    
    Iznik.Views.Plugin.Yahoo.Unbounce = Iznik.Views.Plugin.Yahoo.SyncMembers.extend({
        // Setting offset to 0 omits start from first one
        offset: 0,
    
        template: 'plugin_sync_members_bouncing',
    
        crumbLocation: "/members/bouncing",
        memberLocation: 'members',
    
        numField: 'total',
        dateField: 'bounceDate',
    
        url: function() {
            var url = YAHOOAPI + 'groups/' + this.model.get('nameshort') + "/members/bouncing?count=" + this.chunkSize + "&chrome=raw"
    
            if (this.offset) {
                url += "&start=" + this.offset;
            }
    
            return(url);
        },
    
        unbounceone: function() {
            var self = this;
    
            if (self.offset < self.members.length) {
                var percent = Math.round((self.offset / self.members.length) * 100);
                self.$('.progress-bar:last').css('width',  percent + '%').attr('aria-valuenow', percent);
    
                var member = self.members[self.offset++];
    
                // Whatever happens, we want to move on to the next one.  We're not precious about every last unbounce working.
                window.IznikPlugin.getCrumb(self.model.get('nameshort'), '/members/all', function(crumb) {
                    new majax({
                        type: "POST",
                        url: YAHOOAPI + 'groups/' + self.model.get('nameshort') + "/members/users/" + member.yahooUserId + "?gapi_crumb=" + crumb,
                        data: {
                            unbounce: true
                        },
                        success: function (ret) {
                            console.log("Unbounce returned", ret);
                            self.unbounceone();
                        },
                        error: function (request, status, error) {
                            console.log("Unbounce returned", status, error);
                            self.unbounceone();
                        }
                    });
                }, function() {
                    console.log("Failed to get crumb");
                    self.unbounceone();
                })();
            } else {
                // Finished
                self.succeed();
            }
        },
    
        completed: function(members) {
            // Now we have the list of bouncing members.  Switch to new template.
            this.template = 'plugin_bulk_unbounce_members';
            Iznik.Views.Plugin.Yahoo.SyncMembers.prototype.render.call(this).then(function(self) {
                self.startBusy();
                self.offset = 0;
                self.members = members;
                self.unbounceone();
            });
        }
    });
    
    Iznik.Views.Plugin.Yahoo.RemoveBouncing = Iznik.Views.Plugin.Yahoo.SyncMembers.extend({
        // Setting offset to 0 omits start from first one
        offset: 0,
    
        template: 'plugin_sync_members_bouncing',
    
        crumbLocation: "/members/bouncing",
        memberLocation: 'members',
    
        numField: 'total',
        dateField: 'bounceDate',
    
        url: function() {
            var url = YAHOOAPI + 'groups/' + this.model.get('nameshort') + "/members/bouncing?count=" + this.chunkSize + "&chrome=raw"
    
            if (this.offset) {
                url += "&start=" + this.offset;
            }
    
            return(url);
        },
    
        removeone: function() {
            var self = this;
    
            if (self.offset < self.members.length) {
                var percent = Math.round((self.offset / self.members.length) * 100);
                self.$('.progress-bar:last').css('width',  percent + '%').attr('aria-valuenow', percent);
    
                var member = self.members[self.offset++];
                var mom = new moment(member.date);
                var now = new moment();
                var daysago = moment.duration(now.diff(mom)).asDays();
    
                if (daysago > self.model.get('bouncingfor')) {
                    var data = [{
                        userId: member.yahooUserId
                    }];
    
                    // Whatever happens, we move on; if it fails we'll get it next time.
                    new majax({
                        type: "POST",
                        headers: {
                            'X-HTTP-Method-Override': 'DELETE'
                        },
                        url: YAHOOAPIv2 + "groups/" + this.model.get('nameshort') + "/members?gapi_crumb=" + self.crumb + "&members=" + encodeURIComponent(JSON.stringify(data)),
                        data: data,
                        success: function() {
                            self.removeone();
                        }, error: function() {
                            self.removeone();
                        }
                    });
                } else {
                    self.removeone();
                }
            } else {
                // Finished
                self.succeed();
            }
        },
    
        completed: function(members) {
            // Now we have the list of bouncing members.  Switch to new template.
            this.template = 'plugin_bulk_remove_bouncing';
            Iznik.Views.Plugin.Yahoo.SyncMembers.prototype.render.call(this).then(function(self) {
                self.startBusy();
                self.offset = 0;
                self.members = members;
                self.removeone();
            });
        }
    });
    
    Iznik.Views.Plugin.Yahoo.ToSpecialNotices = Iznik.Views.Plugin.SubView.extend({
        // Setting offset to 0 omits start from first one
        offset: 0,
        context: null,
        members: [],
    
        crumbLocation: "/members/all",
    
        template: 'plugin_tospecialnotices',
    
        changeOne: function() {
            var self = this;

            if (self.offset < self.members.length) {
                var percent = Math.round((self.offset / self.members.length) * 100);
                self.$('.progress-bar:last').css('width',  percent + '%').attr('aria-valuenow', percent);
    
                var member = self.members[self.offset++];
                console.log(member);
                var group = Iznik.Session.getGroup(self.options.bulkop.groupid);
                var mod = new Iznik.Models.Yahoo.User({
                    group: group.get('nameshort'),
                    email: member.email,
                    userId: member.yahooUserId
                });
                self.listenToOnce(mod, 'completed', function() {
                    self.changeOne();
                });
                mod.changeAttr('deliveryType', 'ANNOUNCEMENT');
            } else {
                // Finished
                console.log("Finished");
                self.succeed();
            }
        },
    
        getChunk: function() {
            var self = this;
            $.ajax({
                type: 'GET',
                url: API + 'memberships/' + self.options.bulkop.groupid,
                context: self,
                data: {
                    limit: 1000,
                    context: self.context ? self.context : null,
                    yahooDeliveryType: 'NONE'
                },
                success: function(ret) {
                    var self = this;
                    self.context = ret.context;
                    self.$('.js-count').html(ret.members.length);
    
                    if (ret.members.length > 0) {
                        // We returned some - add them to the list.
                        _.each(ret.members, function(member) {
                            if (member.hasOwnProperty('email') && member.email.toLowerCase().indexOf('fbuser') == -1) {
                                // FBUser members are members on Yahoo which are allowed to be on Web Only.
                                self.members.push(member);
                            }
                        });
                        self.getChunk.call(self);
                    } else {
                        // We got them all.
                        self.$('.js-download').hide();
                        self.$('.js-progress').show();
                        self.changeOne();
                    }
                }
            })
        },
    
        start: function() {
            var self = this;
            this.startBusy();
    
            if (self.options.bulkop.criterion != 'WebOnly') {
                console.error("To Special Notices bulk op only supports WebOnly filter");
                self.options.bulkop.criterion = 'WebOnly';
            }
    
            this.getChunk();
        }
    });
    
    Iznik.Views.Plugin.Yahoo.ApprovePendingMessage = Iznik.Views.Plugin.SubView.extend({
        template: 'plugin_pending_approve',
        crumbLocation: "/management/pendingmessages",
    
        server: true,
    
        start: function() {
            var self = this;
            this.startBusy();
    
            $.ajax({
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
    
    Iznik.Views.Plugin.Yahoo.RejectPendingMember = Iznik.Views.Plugin.SubView.extend({
        template: 'plugin_member_pending_reject',
        crumbLocation: "/management/pendingmembers",
    
        server: true,
    
        start: function() {
            var self = this;
            this.startBusy();
    
            $.ajax({
                type: "POST",
                url: YAHOOAPIv2 + 'groups/' + this.model.get('group').nameshort + "/pending/members?gapi_crumb=" + this.crumb,
                data: {
                    R: this.model.get('id')
                }, success: function (ret) {
                    if (ret.hasOwnProperty('ygData') &&
                        ret.ygData.hasOwnProperty('numAccepted') &&
                        ret.ygData.hasOwnProperty('numRejected')) {
                        // If the rection worked, then numRejected = 1.
                        // If the rejection is no longer relevant because the pending member has gone, both are 0.
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
    
    Iznik.Views.Plugin.Yahoo.ApprovePendingMember = Iznik.Views.Plugin.SubView.extend({
        template: 'plugin_member_pending_approve',
        crumbLocation: "/management/pendingmembers",
    
        server: true,
    
        start: function() {
            var self = this;
            this.startBusy();
    
            $.ajax({
                type: "POST",
                url: YAHOOAPIv2 + 'groups/' + this.model.get('group').nameshort + "/pending/members?gapi_crumb=" + this.crumb,
                data: {
                    A: '[{"userId": "' + this.model.get('id') + '"}]'
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
                    if (c.indexOf('Internal Server Error') !== -1) {
                        // For some members Yahoo gives this, and never recovers.  Give up on doing this work.
                        self.succeed();
                    } else {
                        var ret = a.responseJSON;
    
                        if (ret.hasOwnProperty('ygError') && ret.ygError.hasOwnProperty('errorMessage') &&
                            ret.ygError.errorMessage == "Internal error: Error in instantiating form object...") {
                            // This appears to mean that the member is no longer pending.
                            self.succeed();
                        } else {
                            self.fail();
                        }
                    }
                }
            });
        }
    });
    
    Iznik.Views.Plugin.Yahoo.RejectPendingMessage = Iznik.Views.Plugin.SubView.extend({
        template: 'plugin_pending_reject',
        crumbLocation: "/management/pendingmessages",
    
        server: true,
    
        start: function() {
            var self = this;
            this.startBusy();
    
            $.ajax({
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
                        if (ret.ygData.numRejected == 1 ||
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
    
    Iznik.Views.Plugin.Yahoo.DeleteApprovedMessage = Iznik.Views.Plugin.SubView.extend({
        template: 'plugin_message_approved_delete',
        crumbLocation: "/conversations/messages",
    
        server: true,
    
        checkRsp: function(ret) {
            // We may hit a Yahoo redirect.
            var self = this;
    
            if (ret.indexOf("Please wait while we are redirecting") !== -1) {
                var re = /window.location.href = "(.*)"/;
                var match = re.exec(ret);
                if (match[1]) {
                    $.ajax({
                        type: 'GET',
                        url: match[1],
                        context: self,
                        success: self.checkRsp,
                        error: function() {
                            self.fail();
                        }
                    })
                }
            } else if (ret.indexOf('The item you are looking for is not available') !== -1) {
                // It's already been deleted, so this is a success.
                self.succeed();
            } else {
                new majax({
                    type: "DELETE",
                    url: YAHOOAPI + 'groups/' + self.model.get('group').nameshort + "/messages/" + self.model.get('id') + "?gapi_crumb=" + self.crumb,
                    success: function (ret) {
                        if (ret.hasOwnProperty('ygData') && ret.ygData == 1) {
                            self.succeed();
                        } else {
                            if (ret.hasOwnProperty('ygError') && (ret.ygError.errorCode == 1319 || ret.ygError.errorCode == 1002)) {
                                // We get this if we try to delete something invalid.
                                self.succeed();
                            } else {
                                self.fail();
                            }
                        }
                    }, error: function(request, status, error) {
                        console.log("Delete error", status, error, status.indexOf('Not found'));
                        if (status.indexOf('Not Found') !== -1) {
                            // Another way in which we can be told that the item no longer exists; typically because of a 403
                            // error.
                            console.log("Worked really");
                            self.succeed();
                        } else {
                            console.log("Failed really");
                            self.fail();
                        }
                    }
                });
            }
        },
    
        start: function() {
            var self = this;
            this.startBusy();
    
            // If the message has already been deleted, then Yahoo doesn't tell us that in the return - it just
            // gives a crumb error.  So get the message here to double check.
            var url = "https://groups.yahoo.com/neo/groups/" + this.model.get('group').nameshort + this.crumbLocation + "?" + Math.random();
            $.ajax({
                'type': 'GET',
                'url': url,
                context: this,
                success: self.checkRsp,
                error: function() {
                    self.fail();
                }
            })
        }
    });
    
    Iznik.Views.Plugin.Yahoo.ChangeAttribute = Iznik.Views.Plugin.SubView.extend({
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

                    // console.log("Change attr", mod.get(self.attr), self.model.get(self.attr));
                    if (mod.get(self.attr) == self.model.get(self.attr)) {
                        // Already what we want.
                        // console.log("Already what we want");
                        mod.trigger('completed', true);
                    } else {
                        mod.changeAttr(self.attr, self.model.get(self.attr));
                    }
                }
            });
        }
    });
    
    Iznik.Views.Plugin.FakeFail = Iznik.Views.Plugin.SubView.extend({
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
    
    Iznik.Views.Plugin.Yahoo.Invite = Iznik.Views.Plugin.SubView.extend({
        crumbLocation: "/invitations/members",
        template: 'plugin_invite',

        server: true,

        start: function() {
            var self = this;
            this.startBusy();
    
            $.ajax({
                type: "POST",
                url: YAHOOAPI + "groups/" + self.model.get('nameshort') +
                    "/members?actionType=MAILINGLIST_INVITE&gapi_crumb=" + self.crumb,
                data: 'members=[{"email":"' + self.model.get('email') + '"}]',
                success: function (ret) {
                    if (ret.hasOwnProperty('ygData')) {
                        // console.log("Got ygData", ret.ygData.hasOwnProperty('failedInvites'), ret.ygData.failedInvites.hasOwnProperty('ALREADY_MEMBER'));
                        if (ret.ygData.hasOwnProperty('numSuccessfulInvites')) {
                            if (ret.ygData.numSuccessfulInvites == 1) {
                                // If the invite worked, numSuccessfulInvites == 1
                                self.succeed();
                            } else if (ret.ygData.hasOwnProperty('failedInvites') && ret.ygData.failedInvites.hasOwnProperty('ALREADY_MEMBER')) {
                                // We're already a member - no need to keep inviting.
                                self.succeed();
                            } else {
                                self.fail();
                            }
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

        server: false,

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
    
    Iznik.Views.Plugin.Yahoo.RemoveApprovedMember = Iznik.Views.Plugin.SubView.extend({
        template: 'plugin_member_approved_remove',
    
        crumbLocation: "/members/all",
    
        server: true,
    
        start: function() {
            var self = this;

            if (cantremove[self.model.get('group').id]) {
                self.drop();
            }

            var mod = new Iznik.Models.Yahoo.User({
                group: self.model.get('group').nameshort,
                email: self.model.get('email')
            });

            mod.fetch().then(function() {
                console.log("Fetched mod", mod);
                self.listenToOnce(mod, 'removesucceeded', self.succeed);
                self.listenToOnce(mod, 'removefailed', self.fail);
                self.listenToOnce(mod, 'removeprohibited', function() {
                    console.log("Remove prohibited, drop");
                    cantremove[self.model.get('group').id] = true;
                    self.drop();
                });
                mod.remove(self.crumb);
            });
            
            this.startBusy();
        }
    });

    Iznik.Views.Plugin.Yahoo.BanApprovedMember = Iznik.Views.Plugin.SubView.extend({
        template: 'plugin_member_approved_ban',
    
        crumbLocation: "/members/all",
    
        server: true,
    
        start: function() {
            var self = this;

            if (cantban[self.model.get('group').id]) {
                self.drop();
            }

            var mod = new Iznik.Models.Yahoo.User({
                group: self.model.get('group').nameshort,
                email: self.model.get('email')
            });

            mod.fetch().then(function() {
                // Unfortunately the fetch of the model will have trashed our crumb.
                console.log("Refetch crumb", self.crumb);
                self.crumbLocation = '/members/ban';
                window.IznikPlugin.getCrumb(self.model.get('group').nameshort, self.crumbLocation, function() {
                    console.log("Fetched new crumb", self.crumb);
                    self.listenToOnce(mod, 'bansucceeded', self.succeed);
                    self.listenToOnce(mod, 'banfailed', self.fail);
                    self.listenToOnce(mod, 'banprohibited', function() {
                        cantban[self.model.get('group').id] = true;
                        self.drop();
                    });
                    mod.ban(self.crumb);
                }, self.fail)();
            });
            
            this.startBusy();
        }
    });
});