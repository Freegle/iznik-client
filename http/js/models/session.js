Iznik.Models.Session = IznikModel.extend({
    url: API + 'session',

    initialize: function() {
    },

    updateCounts: function() {
        this.testLoggedIn();
    },

    testLoggedIn: function() {
        var self = this;

        $.ajax({
            url: API + 'session',
            success: function(ret) {
                if ((ret.ret == 0)) {
                    //console.log("Logged in");
                    self.set(ret);

                    // We get an array of groups back - we want it to be a collection.
                    self.set('groups', new IznikCollection(ret.groups));

                    // We may also get an array of modconfigs.
                    if (ret.configs) {
                        self.set('configs', new IznikCollection(ret.configs));
                    }

                    self.trigger('isLoggedIn', true);

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
                            fi: 'spamother',
                            el: '.js-spamcountother',
                            ev: 'spamcountsotherchanged',
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
                            fi: 'spammembers',
                            el: '.js-spammemberscount',
                            ev: 'spammemberscountschanged',
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
                            fi: 'spammembersother',
                            el: '.js-spammemberscountother',
                            ev: 'spammemberscountsotherchanged',
                            window: false,
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
                            if (ret.work[count.fi] > countel.html() || countel.html() == 0) {
                                // Only trigger this when the counts increase.  This will pick up new messages
                                // without screen flicker due to re-rendering when we're processing messages and
                                // deleting them.  There's a minor timing window where a message could arrive as
                                // one is deleted, leaving the counts the same, but this will resolve itself when
                                // our current count drops to zero, or worst case when we refresh.
                                countel.html(ret.work[count.fi]);
                                Iznik.Session.trigger(count.ev);

                                if (ret.work[count.fi] > 0 && count.sound) {
                                    var sound = new Audio("/sounds/alert.wav");
                                    sound.play();
                                }
                            }
                        } else {
                            $(count.el).empty();
                        }
                    })

                    document.title = (total == 0) ? 'ModTools' : ('(' + total + ') ModTools');
                } else {
                    self.trigger('isLoggedIn', false);
                }
            },
            error: function() {
                console.log("Get settings failed");
                self.trigger('isLoggedIn', false);
            }
        })
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

    getSettings: function(groupid) {
        var settings = [];

        this.get('groups').each(function(group) {
            if (group.get('groupid') == groupid) {
                settings = group.get('settings');
            }
        });

        return(settings);
    }
});

Iznik.Session = new Iznik.Models.Session();