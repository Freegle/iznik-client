define([
    'jquery',
    'backbone',
    'iznik/base',
], function($, Backbone, Iznik) {
    Iznik.Models.Session = Iznik.Model.extend({
        url: API + 'session',

        playBeep: false,

        loggedIn: false,

        notificationsSetup: false,

        initialize: function () {
            var self = this;

            // We don't want to beep if we are visible.
            self.playBeep = document.hidden;

            $(document).on('hide', function () {
                console.log("Hide");
                self.playBeep = true;
            });

            $(document).on('show', function () {
                console.log("Show");
                self.playBeep = false;
            });
        },

        updateCounts: function () {
            this.testLoggedIn();
        },

        gotSubscription: function (sub) {
            console.log('Subscription endpoint:', sub);
            var subscription = sub.endpoint;

            try {
                // Pass the subscription to the service worker, so that it can use it to authenticate to the server if we
                // later log out from the client.  We need this because there is no payload in the push notification and
                // therefore the only way we can work out what to show the client is to query the server, and we must
                // therefore have a way to identify ourselves to the server - otherwise we'll get the dread "updated in the
                // background" message.
                navigator.serviceWorker.controller.postMessage({
                    type: 'subscription',
                    subscription: subscription
                });
                console.log("Passed to service worker");
            } catch (e) {
                // This can happen when you do a Ctrl+Shift+R.
                console.log("Passed to service worker failed", e.message);
            }

            // See if we have this stored on the server.
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

                    // Save the subscription to the server.
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
        },

        testLoggedIn: function () {
            var self = this;

            // The mainline case is that we have our session cached in local storage, which allows us to get on
            // with things rapidly - in conjunction with use of the appcache it means that we don't need any server
            // interactions before we can start rendering the page.
            self.testing = true;
            // try {
                var sess = localStorage.getItem('session');

                if (sess) {
                    self.testing = false;
                    var parsed = JSON.parse(sess);
                    self.set(parsed);

                    // We get an array of groups back - we want it to be a collection.
                    self.set('groups', new Iznik.Collection(parsed.groups));

                    // We may also get an array of modconfigs.
                    if (parsed.configs) {
                        self.set('configs', new Iznik.Collection(parsed.configs));
                    }

                    self.loggedIn = true;
                    self.trigger('isLoggedIn', true);
                }
            // } catch (e) {
            //     console.error("testLoggedIn exception", e.message);
            // }

            // Now we may or may not have already triggered, but we still want to refresh our data from the server.  This
            // means we are loosely up to date.  It also means that if we have been logged out on the server side, we'll
            // find out.
            $.ajax({
                url: API + 'session',
                success: function (ret) {
                    if (ret.ret == 111) {
                        // Down for maintenance
                        window.location = '/maintenance_on.html';
                    } else if ((ret.ret == 0)) {
                        // Save off the returned session information into local storage.
                        try {
                            localStorage.setItem('session', JSON.stringify(ret));
                        } catch (e) {
                        }
                        //console.log("Logged in");
                        self.set(ret);

                        if (!self.notificationsSetup) {
                            // Set up service worker for push notifications
                            self.notificationsSetup = true;

                            if ('serviceWorker' in navigator) {
                                // Add rand to avoid SW code being cached.
                                navigator.serviceWorker.register('/sw.js?' + Math.random()).then(function (reg) {
                                    console.log("Registered service worker");
                                    // Spot when the service worker has been activated.
                                    navigator.serviceWorker.addEventListener('message', function (event) {
                                        if (event.data.type == 'activated') {
                                            console.log("SW has been activated");
                                            reg.pushManager.getSubscription().then(function (subscription) {
                                                if (!subscription) {
                                                    var p = reg.pushManager.subscribe({
                                                        userVisibleOnly: true
                                                    });
                                                    pushManagerPromise = p;
                                                    p.then(self.gotSubscription, function (error) {
                                                        console.log("Subscribe error", error);
                                                    });
                                                } else {
                                                    self.gotSubscription(subscription);
                                                }
                                            });
                                        }
                                    });
                                }).catch(function (err) {
                                    console.log(':^(', err);
                                });
                            }
                        }

                        // We get an array of groups back - we want it to be a collection.
                        self.set('groups', new Iznik.Collection(ret.groups));

                        // We may also get an array of modconfigs.
                        if (ret.configs) {
                            self.set('configs', new Iznik.Collection(ret.configs));
                        }

                        self.loggedIn = true;

                        if (self.testing) {
                            self.testing = false;
                            self.trigger('isLoggedIn', true);
                        }

                        if (Iznik.Session.get('modtools')) {
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
                            var countschanged = false;

                            _.each(counts, function (count) {
                                var countel = $(count.el);
                                var currcount = countel.html();
                                if (ret.work[count.fi] != currcount) {
                                    countschanged = true;
                                }

                                if (ret.work[count.fi]) {
                                    if (count.window) {
                                        total += ret.work[count.fi];
                                    }

                                    countel.html(ret.work[count.fi]);
                                    // console.log("Sound", ret.work[count.fi], currcount, self.playBeep);

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

                            if (countschanged) {
                                Iznik.Session.trigger('countschanged');
                            }
                        }
                    } else {
                        // We're not logged in - clear our local storage.
                        try {
                            localStorage.removeItem('session');
                        } catch (e) {
                        }
                        self.loggedIn = false;

                        if (self.testing) {
                            self.testing = false;
                            self.trigger('isLoggedIn', false);
                        }
                    }
                },
                error: function () {
                    console.log("Get settings failed");
                    self.loggedIn = false;

                    if (self.testing) {
                        self.testing = false;
                        self.trigger('isLoggedIn', false);
                    }
                }
            })
        },

        forceLogin: function (options) {
            var self = this;

            self.listenToOnce(self, 'isLoggedIn', function (loggedin) {
                //console.log("Are we logged in?", loggedin);
                if (loggedin) {
                    // We are already logged in.  Inform the view that asked.
                    Iznik.Session.trigger('loggedIn');
                } else {
                    // We're not logged in - make it happen.
                    var sign = new Iznik.Views.SignInUp(options);
                    sign.render();
                }
            });

            self.testLoggedIn();
        },

        facebookLogin: function () {
            var self = this;
            //console.log("Do facebook login");
            $.ajax({
                url: API + 'session',
                type: 'POST',
                data: {
                    fblogin: true
                },
                success: function (response) {
                    if (response.ret === 0) {
                        //We fire 2 separate calls, one to tell the CurrentUser that the user has just logged in.
                        //another to tell anyone listening that the user is logged in (In case we were testing).
                        self.trigger('facebookLoggedIn', response);
                        self.trigger('loggedIn', response);
                    } else {
                        var v = new Iznik.Views.CookieError();
                        self.listenTo(v, 'modalClosed', function () {
                            self.trigger('loginFailed', response);
                        });
                        v.render();
                    }
                }
            });
        },

        yahooLogin: function () {
            console.log("Login with Yahoo");
            var self = this;

            var match,
                pl = /\+/g,  // Regex for replacing addition symbol with a space
                search = /([^&=]+)=?([^&]*)/g,
                decode = function (s) {
                    return decodeURIComponent(s.replace(pl, " "));
                },
                query = window.location.search.substring(1);

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
                success: function (response) {
                    console.log("Session login returned", response);
                    if (response.ret === 0) {
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

        catsForGroup: function (groupid) {
            var all = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
            var ret = all;
            this.get('groups').each(function (group) {
                if (group.get('groupid') == groupid) {
                    // We have a list of categories if we have a list, and the list isn't all of them (there are 11).
                    ret = group.get('categories') && group.get('categories').length > 0 && group.get('categories').split(',').length < all.length ? group.get('categories').split(',') : all;
                }
            });

            //console.log("return cats", ret);
            return (ret);
        },

        getGroup: function (groupid) {
            var group = !_.isUndefined(this.get('groups')) ? this.get('groups').get(groupid) : null;
            return (group);
        },

        getSettings: function (groupid) {
            var settings = [];

            this.get('groups').each(function (group) {
                if (group.get('id') == groupid) {
                    settings = group.get('settings');
                }
            });

            return (settings);
        },

        isAdminOrSupport: function () {
            var me = this.get('me');
            return (me && (me.systemrole == 'Admin' || me.systemrole == 'Support'));
        },

        isAdmin: function () {
            var me = this.get('me');
            return (me && me.systemrole == 'Admin');
        }
    });
});
