// CC var Raven = require('raven-js');

define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/chat/chat',
    'jquery-visibility'
], function($, _, Backbone, Iznik) {

    var tryingYahooLogin = false; // CC
    var gotYahooCookies = false; // CC

    Iznik.Models.Session = Iznik.Model.extend({
        url: API + 'session',

        maintenanceMode: false, // CC

        playBeep: false,

        loggedIn: false,

        notificationsSetup: false,

        initialize: function () {
            var self = this;

            // We don't want to beep if we are visible.
            self.playBeep = document.hidden;

            $(document).on('hide', function () {
                self.playBeep = true;
                //console.log("PLAYBEEP HIDDEN");
            });

            $(document).on('show', function () {
                self.playBeep = false;
                //console.log("PLAYBEEP SHOWN");

                // Check if we're still logged in - this tab might have been hidden for a while.  If we were logged
                // in and we no longer are, this will trigger a reload, so we don't look as though we're logged in
                // when we're not.
                self.testLoggedIn([]);
            });

            // Make sure that the chats are set up, even if not yet fetched - used all over.
            self.chats = new Iznik.Collections.Chat.Rooms();
        },

        save: function(attrs, options) {
            var self = this;
            return Backbone.Model.prototype.save.call(this, attrs, options).then(function() {
                self.testLoggedIn();
            });
        },

        updateCounts: function () {
            this.testLoggedIn([
                'work'
            ]);
        },

        askSubscription: function() {

            console.log("askSubscription " + window.mobilePushId);
            if (window.mobilePushId) {
                var subscription = window.mobilePushId;
                //alert("Subs: "+subscription);
                var me = Iznik.Session.get('me');
                if (me) {
                    Iznik.Session.save({
                        id: me.id,
                        notifications: {
                            push: {
                                type: window.isiOS ? 'FCMIOS' : 'FCMAndroid',
                                subscription: subscription
                            }
                        }
                    }, {
                        patch: true
                    });
                }
            }


            /*var self = this;
            console.log("askSubscription");

            if (window.serviceWorker) {
                window.serviceWorker.pushManager.permissionState({
                    userVisibleOnly: true
                }).then(function(PushMessagingState) {
                    var ask = false;

                    switch (PushMessagingState) {
                        case 'granted':
                            // We want to ask to get the latest subscription.  Because we've been granted permissions
                            // it won't cause a user popup.
                            ask = true;
                            break
                        case 'denied':
                            // We don't want to ask again;
                            ask = false;
                            break;
                        case 'prompt':
                            // Don't ask for push notif permissions too often.
                            var lastasked = null;
                            var now = (new Date()).getTime();
                            try {
                                lastasked = Storage.get('lastAskedPush');
                                lastasked = !_.isUndefined(lastasked) ? lastasked : null;
                            } catch (e) {}

                            ask = (!lastasked || (now - lastasked > 60 * 60 * 1000));
                    }

                    if (ask)  {
                        // Try to get push notification permissions.
                        try {
                            try {
                                Storage.set('lastAskedPush', now);
                            } catch (e) {}

                            window.serviceWorker.pushManager.getSubscription().then(function (subscription) {
                                if (!subscription) {
                                    var p = window.serviceWorker.pushManager.subscribe({
                                        userVisibleOnly: true
                                    });
                                    pushManagerPromise = p;
                                    p.then(self.gotSubscription, function (error) {
                                        if (!_.isUndefined(error) && error.indexOf && error.indexOf("permission denied") == -1) {
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
                });
            }*/
        },

        gotSubscription: function (sub) {
            console.log('Subscription endpoint:', sub);

            /*var subscription = sub.endpoint;

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
            }*/
        },

        checkWork: function() {
            this.testLoggedIn([
                'work'
            ]);
        },

        testLoggedIn: function (components) {
            var self = this;
            self.testing = true;

            // We may have a persistent session from local storage which we can use to revive this session if the
            // PHP session has timed out.
            var sess = null;
            var parsed = null;

            try {
                sess = Storage.get('session');
                if (sess) {
                    //console.log("testLoggedIn: OK");
                    //console.log(sess.substring(0,30));
                    parsed = JSON.parse(sess);
                }
            } catch (e) {
                console.error("testLoggedIn exception", e.message);
            }

            $.ajax({
                url: API + 'session',
                type: 'GET',
                data: {
                    persistent: sess ? parsed.persistent : null,
                    components: components
                },
                success: function (ret) {
                  //ret.ret = 111;
                    if (ret.ret == 111) {
                        // Down for maintenance
                        self.testing = false; // CC
                        console.log("set maintenanceMode");
                        self.maintenanceMode = true;
                        Router.navigate("/maintenance", true);
                        self.trigger('isLoggedIn', false);
                    } else if ((ret.ret == 0)) {
                        var now = (new Date()).getTime();

                        try {
                            if (ret.hasOwnProperty('persistent') && ret.persistent) {
                                // Save off the persistent session.  This allows us to log back in if the PHP
                                // session expires.
                                Storage.set('session', JSON.stringify({
                                    persistent: ret.persistent
                                }));
                            }

                            var lastloggedinas = Storage.get('lastloggedinas');

                            if (ret.hasOwnProperty('myid') && ret.myid) {
                                Storage.set('lastloggedinas', ret.myid);

                                if (ret.hasOwnProperty('me') && ret.me.hasOwnProperty('email')) {
                                    Storage.set('myemail', ret.me.email);
                                }

                                if (lastloggedinas && ret.myid != lastloggedinas) {
                                    // We have logged in as someone else.  Make sure nothing odd is cached.
                                    // CC window.location.reload(true);
                                    Router.navigate("/modtools/", true);
                                }
                            }

                            // We use this to decide whether to show sign up or sign in.
                            Storage.set('signedinever', true);
                        } catch (e) {
                        }

                        self.set(ret);

                        if (ret.hasOwnProperty('groups')) {
                            // We get an array of groups back - we want it to be a collection.
                            self.set('groups', new Iznik.Collection(ret.groups));
                        }

                        if (ret.hasOwnProperty('configs')) {
                            // We may also get an array of modconfigs.
                            self.set('configs', new Iznik.Collection(ret.configs));
                        }

                        self.loggedIn = true;

                        // CC Raven.setUserContext({
                        // CC     user: self.get('me')
                        // CC });

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
                                    fi: 'spammembersother',
                                    el: '.js-spammemberscountother',
                                    ev: 'spammembercountsotherchanged',
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
                                    fi: 'pendingvolunteering',
                                    el: '.js-pendingvolunteeringcount',
                                    ev: 'pendingvolunteeringcountschanged',
                                    window: false,
                                    sound: false
                                },
                                {
                                    fi: 'chatreview',
                                    el: '.js-repliescount',
                                    ev: 'repliescountschanged',
                                    window: true,
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
                                },
                                {
                                    fi: 'stories',
                                    el: '.js-storiescount',
                                    ev: 'storiescountchanged',
                                    window: false,
                                    sound: false
                                },
                                {
                                    fi: 'newsletterstories',
                                    el: '.js-newsletterstoriescount',
                                    ev: 'newsletterstoriescountchanged',
                                    window: false,
                                    sound: false
                                }
                            ];

                            var total = 0;
                            var countschanged = false;

                            _.each(counts, function (count) {
                                var countel = $(count.el);
                                var currcount = countel.html();
                                if (ret.work && ret.work[count.fi] != currcount) {
                                    countschanged = true;
                                }

                                if (ret.work && ret.work[count.fi]) {
                                    if (count.window) {
                                        total += ret.work[count.fi];
                                    }

                                    countel.html(ret.work[count.fi]);
                                    //console.log("Sound", ret.work[count.fi], currcount, self.playBeep, count.sound);

                                    if (ret.work[count.fi] > currcount || currcount == 0) {
                                        // Only trigger this when the counts increase.  This will pick up new messages
                                        // without screen flicker due to re-rendering when we're processing messages and
                                        // deleting them.  There's a minor timing window where a message could arrive as
                                        // one is deleted, leaving the counts the same, but this will resolve itself when
                                        // our current count drops to zero, or worst case when we refresh.
                                        Iznik.Session.trigger(count.ev);

                                        if (ret.work[count.fi] > 0 && count.sound) {
                                            var settings = Iznik.Session.get('me').settings;

                                            if (Iznik.presdef('playbeep', settings, 1) && self.playBeep) {
                                                var sound = new Audio("/sounds/alert.wav");
                                                try {
                                                    // Some browsers prevent us using play unless in response to a
                                                    // user gesture, so catch any exception.
                                                    sound.play();
                                                } catch (e) {
                                                    console.log("Failed to play beep", e.message);
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $(count.el).empty();
                                }
                            })

                            document.title = (total == 0) ? 'ModTools' : ('(' + total + ') ModTools');

                            if (total) {
                                $('.js-workcount').html(total).show();
                            } else {
                                $('.js-workcount').html('').hide();
                            }

                            if (countschanged) {
                                Iznik.Session.trigger('countschanged');
                            }
                        }
                    } else {
                        try {
                            var sess = Storage.get('session');

                            if (sess && ret.ret == 1) {
                                // We thought we were logged in but we're not.  If the persistent session
                                // (which should help us deal with PHP session expiry) wasn't valid, then we
                                // must be supposed to be logged out.  Clear our local storage and reload.
                                //
                                // This will look slightly odd but means that the mainline case of still being logged
                                // in is handled more quickly.
                                try {
                                    console.error("Not logged in after all", sess, parsed, ret);
                                    Storage.remove('session');
                                } catch (e) {
                                }
                                window.location.reload();
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
        },

        forceLogin: function (components) {
            var self = this;

            self.listenToOnce(self, 'isLoggedIn', function (loggedin) {
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

            self.testLoggedIn(components);
        },

        facebookLogin: function (token) { // CC
            var self = this;
            //console.log("Do facebook login");
            $.ajax({
                url: API + 'session',
                type: 'POST',
                data: {
                    fbaccesstoken: token, // CC
                    fblogin: true
                },
                success: function (response) {
                    console.log("Facebook login response", response);
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

        ///////////////////////////////////////
        // For ModTools we need cookies direct from Yahoo so 'plugin' code can talk direct to Yahoo
        // and the CORS headers need to be right - which doesn't seem to be an issue here.
        //
        // Flow is:
        //  - we may have persistent session so try logging in at MT
        //  - if not, log in options are shown
        //  - if Yahoo chosen then come back to yahooMTLogin() here
        //  - this tries MT session log in again
        //  - if ret==1 then we have redirect info to pass to yahooAuth() here
        //  - yahooAuth shows InAppBrowser to show Yahoo login to user
        //  - this catches redirect back to modtools.org and stores authenticated info that is in URL and extracts user email and fullname
        //  - authenticated info is passed to MT session and we will be able to log in.


        //  - in iOS the InAppBrowser cookies are shared with the WebView so no problem
        //  - in Android it is harder to get the cookies in the right place

        // Using https://github.com/apache/cordova-plugin-inappbrowser executeScript

        // ANDROID: NOT USING CROSSWALK SO NOT NOW NEEDED
        // NO: Setting document.cookie doesn't work
        // NO: Using com.cordova.plugins.cookiemaster doesn't work
        // NO: Amending InAppBrowser to add getCookies() call does work - https://github.com/apache/cordova-plugin-inappbrowser/pull/122
        // NO: https://github.com/wymsee/cordova-HTTP not tried
        // NO: https://forums.meteor.com/t/meteor-1-3-beta-11-setting-cookies-from-cordova-on-android/18637 not tried
        // NO: http://stackoverflow.com/questions/28107313/set-cookies-programatically-in-crosswalk-webview-on-android not tried
        // NO: http://stackoverflow.com/questions/17228785/yahoo-authentication-by-oauth-without-any-redirectionclient-side-is-it-possib
        // NO:  - looked at: OpenID solution uses window.open https://gist.github.com/erikeldridge/619947

        yahooMTLogin: function () {
            console.log("yahooMTLogin");
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
                        self.checkYahooCookies();
                        Router.mobileReload('/');  // CC
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
          console.log("yahooAuth: Yahoo authenticate window open");
          console.log(yauthurl);

          var authGiven = false;

          var authWindow = cordova.InAppBrowser.open(yauthurl, '_blank', 'location=yes,menubar=yes');

          $(authWindow).on('loadstart', function (e) {
            var url = e.originalEvent.url;
            console.log("yloadstart: " + url);

            // Catch redirect after auth back to modtools
            if (url.indexOf("https://modtools.org/") === 0) {
              authWindow.close();
              var urlParams = self.extractQueryStringParams(url);
              if (urlParams) {
                authGiven = true;
                urlParams.yahoologin = true;
                console.log(urlParams);
                //alert(JSON.stringify(urlParams));
                var email = urlParams['openid.ax.value.email'];
                var fullname = urlParams['openid.ax.value.fullname'];
                localStorage.setItem('yahoo.email', email);
                localStorage.setItem('yahoo.fullname', fullname);

                // Try logging in again at FD
                $.ajax({
                  url: API + 'session',
                  type: 'POST',
                  data: urlParams,
                  success: function (response) {
                    console.log("Session login returned", response);
                    if (response.ret === 0) {
                      self.trigger('loggedIn', response);
                      self.checkYahooCookies();
                      Router.mobileReload('/');  // CC
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

        checkYahooCookies: function () {    // CC
            var self = this;
            console.log("checkYahooCookies");
            var urlGetGroups = "https://groups.yahoo.com/api/v1/user/groups/all";

            // If we've already got cookies then this will work
            function checkResponse(ret) {
                try{
                    console.log("session typeof ret=" + typeof ret);
                    if (typeof ret == "string") {
                        console.log("session ret=" + ret.substring(0, 50));
                    }

                    if (ret && ret.hasOwnProperty('ygData') && ret.ygData.hasOwnProperty('allMyGroups')) {
                        gotYahooCookies = true;
                        console.log("checkYahooCookies OK");
                        return;
                    }
                    console.log("checkYahooCookies not OK");
                    self.getYahooCookies();
                } catch (e) {
                    console.log(e.message);
                }
            }
            $.ajax({
                url: urlGetGroups,
                context: this,
                success: checkResponse,
                error: self.getYahooCookies,
            });
        },

        getYahooCookies: function () {    // CC

            console.log("getYahooCookies start");

            var urlGetGroups = "https://groups.yahoo.com/api/v1/user/groups/all";

            // If not got cookies then try to get them in inAppBrowser
            var wGetGroups = cordova.InAppBrowser.open(urlGetGroups, '_blank', 'hidden=yes');
            //var wGetGroups = cordova.InAppBrowser.open(urlGetGroups, '_blank', 'location=yes,menubar=yes');
            $(wGetGroups).on('loadstop', function (e) {
                var url = e.originalEvent.url;
                console.log("getYahooCookies: " + url);

                function getBodyAndClose() {
                    console.log("getBodyAndClose start");
                    var jsReturnContent = "document.body.innerHTML";
                    function cbReturnContent(params) {
                        console.log("Android ReturnContent returned:");
                        console.log(params[0]);
                        //$('#js-mobiledebug').html(JSON.stringify(params[0]));
                        wGetGroups.close();
                    }
                    wGetGroups.executeScript({ code: jsReturnContent }, cbReturnContent);
                }

                if (isiOS) {
                    var jsReturnCookies = "document.cookie;";
                    function cbReturnCookies(params) {
                        console.log("iOS getYahooCookies returned:");
                        console.log(params);
                        var yahooCookies = params[0];
                        localStorage.setItem('yahoo.cookies', yahooCookies);
                        //getBodyAndClose();
                    }
                    wGetGroups.executeScript({ code: jsReturnCookies }, cbReturnCookies);
                } else {

                    // CC wGetGroups.getCookies({ url: 'https://groups.yahoo.com' }, function (count) {
                    // CC     console.log("Android getCookies returned: " + count);
                    // CC     getBodyAndClose();
                    // CC });
                    wGetGroups.close();
                }
                gotYahooCookies = true;
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

        roleForGroup: function(groupid, overrides) {
            var me = this.get('me');
            var ret = 'Non-member';

            if (me) {
                var group = this.getGroup(groupid);

                if (group) {
                    ret = group.get('role');

                    if (ret != 'Owner' && overrides && me.systemrole == 'Admin') {
                        ret = 'Owner';
                    } else if (ret == 'Member' && overrides && me.systemrole == 'Support') {
                        ret = 'Moderator'
                    }
                }
            }

            return(ret);
        },

        isModeratorOf: function(groupid, overrides) {
            // Support have moderator rights.
            var me = this.get('me');
            if (me) {
                if (overrides && (me.systemrole == 'Admin' || me.systemrole == 'Support')) {
                    return(true);
                }

                var group = this.getGroup(groupid);
                return(group && (group.role == 'Owner' || group.role == 'Moderator'));
            }
        },

        isOwnerOf: function(groupid, overrides) {
            // Support have moderator rights.
            var me = this.get('me');
            if (me) {
                if (overrides && (me.systemrole == 'Admin' || me.systemrole == 'Support')) {
                    return(true);
                }

                var group = this.getGroup(groupid);
                return(group && (group.role == 'Owner'));
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
            var groups = this.get('groups');

            if (groups) {
                groups.each(function (group) {
                    if (group.get('type') == 'Freegle' && (group.get('role') == 'Owner' || group.get('role') == 'Moderator')) {
                        ret = true;
                    }
                });
            }

            return(ret);
        },

        hasFacebook: function() {
            var facebook = true;    // CC true on mobile to enable any sharing
            /*var facebook = null;
            _.each(this.get('logins'), function(login) {
                if (login.type == 'Facebook') {
                    facebook = login;
                }
            });*/

            return(facebook);
        },

        hasPermission: function(perm) {
            var perms = this.get('me') ? this.get('me').permissions : null;
            // console.log("Check permission", perms, perm, this);
            return(perms && perms.indexOf(perm) !== -1);
        },

        forget: function() {
            var self = this;

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: API + 'session',
                    type: 'POST',
                    data: {
                        action: 'Forget'
                    }, success: function (ret) {
                        if (ret.ret === 0) {
                            resolve();
                        } else {
                            reject(ret);
                        }
                    }, error: function() {
                        reject(null);
                    }
                })
            });

            return(p);
        },

        savePhone: function(phone) {
            var self = this;

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: API + 'session',
                    type: 'POST',
                    headers: {
                        'X-HTTP-Method-Override': 'PATCH'
                    },
                    data: {
                        phone: phone
                    }, success: function (ret) {
                        if (ret.ret === 0) {
                            resolve();
                        } else {
                            reject(ret);
                        }

                    }, error: function() {
                        reject(null);
                    }
                })
            });

            return(p);
        },

        saveAboutMe: function(aboutme) {
            var self = this;

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: API + 'session',
                    type: 'POST',
                    headers: {
                        'X-HTTP-Method-Override': 'PATCH'
                    },
                    data: {
                        aboutme: aboutme
                    }, success: function (ret) {
                        if (ret.ret === 0) {
                            resolve();
                        } else {
                            reject(ret);
                        }

                    }, error: function() {
                        reject(null);
                    }
                })
            });

            return(p);
        }
    });
});
