define([
    'jquery',
    'backbone',
    'iznik/base',
    'jquery-visibility'
], function($, Backbone, Iznik) {

    var tryingYahooLogin = false; // CC

    Iznik.Models.Session = Iznik.Model.extend({
        url: API + 'session',

        maintenanceMode: false, // CC

        playBeep: false,

        loggedIn: false,

        notificationsSetup: false,

        lastServerHit: null,

        initialize: function () {
            var self = this;

            // We don't want to beep if we are visible.
            self.playBeep = document.hidden;

            $(document).on('hide', function () {
                console.log("Tab Hide");
                self.playBeep = true;
            });

            $(document).on('show', function () {
                console.log("Tab Show");
                self.playBeep = false;

                // Check if we're still logged in - this tab might have been hidden for a while.  If we were logged
                // in and we no longer are, this will trigger a reload, so we don't look as though we're logged in
                // when we're not.
                self.testLoggedIn();
            });
        },

        save: function(attrs, options) {
            var self = this;
            Backbone.Model.prototype.save.call(this, attrs, options).then(function() {
                self.testLoggedIn();
            });
        },

        updateCounts: function () {
            this.testLoggedIn();
        },

        askSubscription: function() {
            var self = this;

            // Don't ask for push notif permissions too often.
            var lastasked = null;
            var now = (new Date()).getTime();
            try {
                lastasked = localStorage.getItem('lastAskedPush');
            } catch (e) {}

            //console.log("askSubscription", lastasked, now,now - lastasked, 60 * 60 * 1000);

            if (window.serviceWorker &&
                (!lastasked || (now - lastasked > 60 * 60 * 1000)))  {
                // Try to get push notification permissions.
                try {
                    try {
                        localStorage.setItem('lastAskedPush', now);
                    } catch (e) {}

                    window.serviceWorker.pushManager.getSubscription().then(function (subscription) {
                        if (!subscription) {
                            var p = window.serviceWorker.pushManager.subscribe({
                                userVisibleOnly: true
                            });
                            pushManagerPromise = p;
                            p.then(self.gotSubscription, function (error) {
                                if (!_.isUndefined(error) && error.indexOf("permission denied") == -1) {
                                    // Permission denied is normal.
                                    console.log("Subscribe error", error);
                                }
                            });
                        } else {
                            self.gotSubscription(subscription);
                        }
                    });
                } catch (e) {
                    console.log("Can't get sub", e);
                }
            }
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
                console.log("Passed subscription to service worker");
            } catch (e) {
                console.log("Pass subscription to service worker failed", e.message);
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

        testLoggedIn: function (forceserver) {
            var self = this;

            // The mainline case is that we have our session cached in local storage, which allows us to get on
            // with things rapidly - in conjunction with use of the appcache it means that we don't need any server
            // interactions before we can start rendering the page.
            self.testing = true;
            if (!forceserver) {
                try {
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
                } catch (e) {
                    console.error("testLoggedIn exception", e.message);
                }
            }

            // Now we may or may not have already triggered, but we still want to refresh our data from the server
            // regularly.  This means we are loosely up to date.  It also means that if we have been logged out on
            // the server side, we'll find out.
            //
            // We may have a persistent session from local storage which we can use to revive this session if the
            // PHP session has timed out.
            var now = (new Date()).getTime();
            if (!self.lastServerHit || now - self.lastServerHit > 1000) {
                self.lastServerHit = now;

                $.ajax({
                    url: API + 'session',
                    type: 'GET',
                    data: {
                        persistent: self.get('persistent')
                    },
                    success: function (ret) {
                        self.maintenanceMode = false; // CC
                        if (ret.ret == 111) {
                            // Down for maintenance
                            self.testing = false; // CC
                            console.log("set maintenanceMode");
                            self.maintenanceMode = true;
                            Router.navigate("/maintenance", true);
                            self.trigger('isLoggedIn', false);
                        } else if ((ret.ret == 0)) {
                            // Save off the returned session information into local storage.
                            var now = (new Date()).getTime();
                            try {
                                localStorage.setItem('session', JSON.stringify(ret));
                                lastloggedinas = localStorage.getItem('lastloggedinas');
                                localStorage.setItem('lastloggedinas', ret.me.id);

                                console.log("lastloggedinas", lastloggedinas, ret.me.id);
                                if (ret.me.id != lastloggedinas) {
                                    // We have logged in as someone else.  Zap our fetch cache.
                                    console.log("Login change - zap cache");
                                    for (var i = 0; i < localStorage.length; i++){
                                        var key = localStorage.key(i);

                                        if (key.indexOf('cache.') === 0) {
                                            localStorage.removeItem(key);
                                        }
                                    }
                                }

                                // We use this to decide whether to show sign up or sign in.
                                localStorage.setItem('signedinever', true);
                            } catch (e) {
                            }
                            self.set(ret);

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
                                        fi: 'pendingevents',
                                        el: '.js-pendingeventscount',
                                        ev: 'pendingeventscountschanged',
                                        window: false,
                                        sound: false
                                    },
                                    {
                                        fi: 'chatreview',
                                        el: '.js-repliescount',
                                        ev: 'repliescountschanged',
                                        window: false,
                                        sound: false
                                    },
                                    {
                                        fi: 'chatreviewother',
                                        el: '.js-repliescountother',
                                        ev: 'repliescountsotherchanged',
                                        window: false,
                                        sound: false
                                    },
                                    {
                                        fi: 'socialactions',
                                        el: '.js-socialactionscount',
                                        ev: 'socialactionscountschanged',
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
                                                    try {
                                                        // Some browsers prevent us using play unless in response to a
                                                        // user gesture, so catch any exception.
                                                        sound.play();
                                                    } catch (e) {}
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
                            try {
                                var sess = localStorage.getItem('session');

                                if (sess && ret.ret == 1) {
                                    // We thought we were logged in but we're not.  Clear our local storage and reload.
                                    // This will look slightly odd but means that the mainline case of still being logged
                                    // in is handled more quickly.
                                    try {
                                        localStorage.removeItem('session');
                                    } catch (e) {
                                    }
                                    Backbone.history.loadUrl(); // CC
                                } else {
                                    // We're not logged in.
                                    self.loggedIn = false;

                                    if (self.testing) {
                                        self.testing = false;
                                        self.trigger('isLoggedIn', false);
                                    }
                                }
                            } catch (e) {
                            }

                            // We're not logged in
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
                });
            } else {
                if (self.testing) {
                    self.testing = false;
                    self.trigger('isLoggedIn', self.loggedIn);
                }
            }
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

        facebookLogin: function (token) { // CC
            var self = this;
            //console.log("Do facebook login");
            $.ajax({
                url: API + 'session',
                type: 'POST',
                data: {
                    fbauthtoken: token, // CC
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

            if (tryingYahooLogin) { return; }
            tryingYahooLogin = true;

            // Try Yahoo login // CC
            var urlParams = {};
            urlParams['yahoologin'] = true;
            console.log("URL params", urlParams);

            $.ajax({
                url: API + 'session',
                type: 'POST',
                data: urlParams,
                success: function (response) {
                    console.log("Session login returned", response);
                    if (response.ret === 0) {
                        self.trigger('loggedIn', response);
                        Router.userHome();  // CC
                        tryingYahooLogin = false;
                    } else if (response.ret === 1) {  // CC
                      self.yahooAuth(response.redirect);
                    } else {
                        $('.js-signin-msg').text("Yahoo log in failed " + response.ret);
                        $('.js-signin-msg').show();
                        self.trigger('loginFailed', response);
                        tryingYahooLogin = false;
                    }
                }
            });
        },

        ///////////////////////////////////////
        // Request user authenticates by opening passed URL
        // If user gives Ok, then pop-up window tries to open a page at ilovefreegle.
        // We catch and stop this open, get passed parameters and pass them as part of repeat FD login request

        yahooAuth: function (yauthurl) {   // CC
          var self = this;
          console.log("Yahoo authenticate window open");
          console.log("yahooAuth: " + yauthurl);

          var authGiven = false;

          var authWindow = window.open(yauthurl, '_blank', 'location=yes,menubar=yes');

          $(authWindow).on('loadstart', function (e) {
            var url = e.originalEvent.url;
            console.log("yloadstart: " + url);

            // Catch redirect after auth back to ilovefreegle
            if (url.indexOf("https://www.ilovefreegle.org/") === 0) {
              authWindow.close();
              var urlParams = self.extractQueryStringParams(url);
              if (urlParams) {
                authGiven = true;
                urlParams.yahoologin = true;
                console.log(urlParams);

                // Try logging in again at FD
                console.log("Got URL params", urlParams);
                $.ajax({
                  url: API + 'session',
                  type: 'POST',
                  data: urlParams,
                  success: function (response) {
                    console.log("Session login returned", response);
                    if (response.ret === 0) {
                      self.trigger('loggedIn', response);
                      Router.userHome();  // CC
                    } else {
                      $('.js-signin-msg').text("Yahoo log in failed " + response.ret);
                      $('.js-signin-msg').show();
                      self.trigger('loginFailed', response);
                    }
                  }
                });
              }
            }
          });

          $(authWindow).on('exit', function (e) {
            if (!authGiven) {
              console.log("Yahoo permission not given or failed");
              $('.js-signin-msg').text("Yahoo permission not given or failed");
              $('.js-signin-msg').show();
            }
            tryingYahooLogin = false;
          });
        },

        extractQueryStringParams: function (url) {  // CC
          var urlParams = false;
          var qm = url.indexOf('?');
          if (qm >= 0) {
            var qs = url.substring(qm + 1);
            // http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
            var match;
            var pl = /\+/g;  // Regex for replacing addition symbol with a space
            var search = /([^&=]+)=?([^&]*)/g;
            var decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); };
            urlParams = {};
            while (match = search.exec(qs)) {
              urlParams[decode(match[1])] = decode(match[2]);
            }
          }
          return urlParams;
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

        getSetting: function(key, def) {
            // Gets a user setting
            if (this.loggedIn) {
                // We are logged in.
                var me = Iznik.Session.get('me');
                var settings = me.settings;
                if (settings.hasOwnProperty(key)) {
                    return(settings[key]);
                } else {
                    return(def);
                }
            } else {
                // Just return default.
                return(def);
            }
        },

        setSetting: function(key, val) {
            var me = Iznik.Session.get('me');

            if (me) {
                me.settings[key] = val;
                // console.log("setSetting", typeof me.settings, key, val, me);
                this.set('me', me);
                Iznik.Session.save({
                    id: me.id,
                    settings: me.settings
                }, {
                    patch: true
                });
            }
        },

        isModeratorOf: function(groupid) {
            // Support have moderator rights.
            var me = this.get('me');
            if (me) {
                if (me.systemrole == 'Admin' || me.systemrole == 'Support') {
                    return(true);
                }

                var group = this.getGroup(groupid);
                return(group && (group.role == 'Owner' || group.role == 'Moderator'));
            }
        },

        isAdminOrSupport: function () {
            var me = this.get('me');
            return (me && (me.systemrole == 'Admin' || me.systemrole == 'Support'));
        },

        isAdmin: function () {
            var me = this.get('me');
            return (me && me.systemrole == 'Admin');
        },

        isFreegleMod: function() {
            var ret = false;

            this.get('groups').each(function (group) {
                if (group.get('type') == 'Freegle' && (group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                    ret = true;
                }
            });

            return(ret);
        }
    });
});
