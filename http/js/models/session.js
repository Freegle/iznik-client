Iznik.Models.Session = IznikModel.extend({
    url: API + 'session',

    playBeep: false,

    loggedIn: false,
    lastTested: null,

    notificationsSetup: false,

    initialize: function() {
        var self = this;

        // We don't want to beep if we are visible.
        self.playBeep = document.hidden;

        $(document).on('hide', function() {
            console.log("Hide");
            self.playBeep = true;
        });

        $(document).on('show', function() {
            console.log("Show");
            self.playBeep = false;
        });
    },

    updateCounts: function() {
        this.testLoggedIn();
    },

    testLoggedIn: function() {
        var self = this;

        var now = moment().valueOf();

        // Only query the server if it's the first time (perhaps since we loggged out) or it's been a while.  This
        // reduces fairly expensive session calls.
        if (!self.lastTested || now - self.lastTested > 10000) {
            $.ajax({
                url: API + 'session',
                success: function(ret) {
                    self.lastTested = moment().valueOf();
                    if ((ret.ret == 0)) {
                        //console.log("Logged in");
                        self.set(ret);

                        if (!self.notificationsSetup) {
                            // Set up service worker for push notifications
                            self.notificationsSetup = true;

                            if ('serviceWorker' in navigator) {
                                // Add a rand to the service worker to stop it being cached and therefore not picking
                                // up fixes.
                                navigator.serviceWorker.register('/js/iznik/sw.js?' + Math.random()).then(function(reg) {
                                    console.log(':^)', reg);
                                    reg.pushManager.subscribe({
                                        userVisibleOnly: true
                                    }).then(function(sub) {
                                        console.log('endpoint:', sub.endpoint);
                                        var p = sub.endpoint.lastIndexOf('/');
                                        var subscription = sub.endpoint;
                                        var me = Iznik.Session.get('me');
                                        if (me) {
                                            if (me.notifications && me.notifications.push && me.notifications.push.subscription == subscription) {
                                                console.log("Already got our permissions");
                                            } else {
                                                // We don't currently have this
                                                console.log("Not got permissions; save", sub.endpoint, me.notifications.push.subscription);
                                                var type = 'Google';
                                                var key = null;
                                                if (subscription.indexOf('services.mozilla.com') !== -1) {
                                                    type = 'Firefox';
                                                }
                                                Iznik.Session.save({
                                                    id: me.id,
                                                    notifications: {
                                                        push: {
                                                            type: type,
                                                            subscription: subscription
                                                        }
                                                    }
                                                }, {
                                                    patch: true
                                                });
                                            }
                                        }
                                    });
                                }).catch(function(err) {
                                    console.log(':^(', err);
                                });
                            }
                        }

                        // We get an array of groups back - we want it to be a collection.
                        self.set('groups', new IznikCollection(ret.groups));

                        // We may also get an array of modconfigs.
                        if (ret.configs) {
                            self.set('configs', new IznikCollection(ret.configs));
                        }

                        self.loggedIn = true;
                        self.trigger('isLoggedIn', true);

                        var admin = Iznik.Session.isAdmin();
                        var support = Iznik.Session.isAdminOrSupport();

                        // Update our various counts.
                        var counts = [
                            {
                                fi: 'pending',
                                el: '.js-pendingcount',
                                ev: 'pendingcountschanged',
                                window: true,
                                sound: true
                            },
                            {
                                fi: 'spam',
                                el: '.js-spamcount',
                                ev: 'spamcountschanged',
                                window: true,
                                sound: true
                            },
                            {
                                fi: 'pendingother',
                                el: '.js-pendingcountother',
                                ev: 'pendingcountsotherchanged',
                                window: false,
                                sound: false
                            },
                            {
                                fi: 'spammembers',
                                el: '.js-spammemberscount',
                                ev: 'spammembercountschanged',
                                window: false,
                                sound: false
                            },
                            {
                                fi: 'pendingmembers',
                                el: '.js-pendingmemberscount',
                                ev: 'pendingmemberscountschanged',
                                window: true,
                                sound: true
                            },
                            {
                                fi: 'pendingmembersother',
                                el: '.js-pendingmemberscountother',
                                ev: 'pendingmemberscountsotherchanged',
                                window: false,
                                sound: false
                            },
                            {
                                fi: 'spammerpendingadd',
                                el: '.js-spammerpendingaddcount',
                                ev: 'spammerpendingaddcountschanged',
                                window: support,
                                sound: false
                            },
                            {
                                fi: 'spammerpendingremove',
                                el: '.js-spammerpendingremovecount',
                                ev: 'spammerpendingremovecountschanged',
                                window: admin,
                                sound: false
                            }
                        ];

                        var total = 0;

                        _.each(counts, function(count) {
                            if (ret.work[count.fi]) {

                                if (count.window) {
                                    total += ret.work[count.fi];
                                }

                                var countel = $(count.el);
                                var currcount = countel.html();
                                countel.html(ret.work[count.fi]);
                                console.log("Sound", ret.work[count.fi], currcount, self.playBeep);

                                if (ret.work[count.fi] > currcount || currcount == 0) {
                                    // Only trigger this when the counts increase.  This will pick up new messages
                                    // without screen flicker due to re-rendering when we're processing messages and
                                    // deleting them.  There's a minor timing window where a message could arrive as
                                    // one is deleted, leaving the counts the same, but this will resolve itself when
                                    // our current count drops to zero, or worst case when we refresh.
                                    Iznik.Session.trigger(count.ev);

                                    if (ret.work[count.fi] > 0 && count.sound) {
                                        var settings = Iznik.Session.get('me').settings;

                                        if (presdef('playbeep', settings, 1) && self.playBeep) {
                                            var sound = new Audio("/sounds/alert.wav");
                                            sound.play();
                                        }
                                    }
                                }
                            } else {
                                $(count.el).empty();
                            }
                        })

                        document.title = (total == 0) ? 'ModTools' : ('(' + total + ') ModTools');
                    } else {
                        self.loggedIn = false;
                        self.trigger('isLoggedIn', false);
                    }
                },
                error: function() {
                    console.log("Get settings failed");
                    self.lastTested = moment().valueOf();
                    self.loggedIn = false;
                    self.trigger('isLoggedIn', false);
                }
            })
        } else {
            self.trigger('isLoggedIn', self.loggedIn);
        }
    },

    forceLogin: function() {
        var self = this;

        self.listenToOnce(self, 'isLoggedIn', function(loggedin) {
            //console.log("Are we logged in?", loggedin);
            if (loggedin) {
                // We are already logged in.  Inform the view that asked.
                Iznik.Session.trigger('loggedIn');
            } else {
                // We're not logged in - make it happen.
                var sign = new Iznik.Views.SignInUp();
                sign.render();
            }
        });

        self.testLoggedIn();
    },

    facebookLogin: function(){
        var self = this;
        //console.log("Do facebook login");
        $.ajax({
            url: API + 'session',
            type: 'POST',
            data: {
                fblogin: true
            },
            success: function(response){
                if(response.ret === 0){
                    //We fire 2 separate calls, one to tell the CurrentUser that the user has just logged in.
                    //another to tell anyone listening that the user is logged in (In case we were testing).
                    self.trigger('facebookLoggedIn', response);
                    self.trigger('loggedIn', response);
                } else {
                    var v = new Iznik.Views.CookieError();
                    self.listenTo(v, 'modalClosed', function() {
                        self.trigger('loginFailed', response);
                    });
                    v.render();
                }
            }
        });
    },

    yahooLogin: function() {
        console.log("Login with Yahoo");
        var self = this;

        var match,
            pl     = /\+/g,  // Regex for replacing addition symbol with a space
            search = /([^&=]+)=?([^&]*)/g,
            decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
            query  = window.location.search.substring(1);

        // We want to post to the server to do the login there.  We pass all the URL
        // parameters we have, which include the OpenID response.
        var urlParams = {};
        while (match = search.exec(query))
            urlParams[decode(match[1])] = decode(match[2]);
        urlParams['yahoologin'] = true;
        urlParams['returnto'] = document.URL;
        console.log("Got URL params", urlParams);

        $.ajax({
            url: API + 'session',
            type: 'POST',
            data: urlParams,
            success: function(response){
                console.log("Session login returned", response);
                if(response.ret === 0){
                    //We fire 2 separate calls, one to tell the CurrentUser that the user has just logged in.
                    //another to tell anyone listening that the user is logged in (In case we were testing).
                    self.trigger('yahoologincomplete', response);
                    self.trigger('loggedIn', response);
                } else {
                    self.trigger('yahoologincomplete', response);
                    self.trigger('loginFailed', response);
                }
            }
        });
    },

    catsForGroup: function(groupid) {
        var all = [1,2,3,4,5,6,7,8,9,10,11];
        var ret = all;
        this.get('groups').each(function(group) {
            if (group.get('groupid') == groupid) {
                // We have a list of categories if we have a list, and the list isn't all of them (there are 11).
                ret = group.get('categories') && group.get('categories').length > 0  && group.get('categories').split(',').length < all.length ? group.get('categories').split(',') : all;
            }
        });

        //console.log("return cats", ret);
        return(ret);
    },

    getGroup: function(groupid) {
        var group = !_.isUndefined(this.get('groups')) ? this.get('groups').get(groupid) : null;
        return(group);
    },

    getSettings: function(groupid) {
        var settings = [];

        this.get('groups').each(function(group) {
            if (group.get('id') == groupid) {
                settings = group.get('settings');
            }
        });

        return(settings);
    },

    isAdminOrSupport: function() {
        var me = this.get('me');
        return(me && (me.systemrole == 'Admin' || me.systemrole == 'Support'));
    },

    isAdmin: function() {
        var me = this.get('me');
        return(me && me.systemrole == 'Admin');
    }
});

Iznik.Session = new Iznik.Models.Session();
