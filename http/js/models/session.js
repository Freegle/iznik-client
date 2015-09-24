Iznik.Models.Session = IznikModel.extend({

    initialize: function() {
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

                    self.trigger('isLoggedIn', true);
                    if (ret.work.pending) {
                        $('.js-pendingcount').html(ret.work.pending);
                    } else {
                        $('.js-pendingcount').empty();
                    }
                    if (ret.work.spam) {
                        $('.js-spamcount').html(ret.work.spam);
                    } else {
                        $('.js-spamcount').empty();
                    }
                } else {
                    //console.log("Not logged in");
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
        urlParams = {};
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
        if (this.get('myGroups')) {
            var ret = all;
            this.get('myGroups').each(function(group) {
                //console.log("Compare ", groupid, group.get('groupid'), group.get('categories'));
                if (group.get('groupid') == groupid) {
                    // We have a list of categories if we have a list, and the list isn't all of them (there are 11).
                    ret = group.get('categories') && group.get('categories').length > 0  && group.get('categories').split(',').length < all.length ? group.get('categories').split(',') : all;
                    //console.log("catsForGroup, found", groupid, ret, group);
                }
            });

            //console.log("return cats", ret);
            return(ret);
        }

        //console.log("catsForGroup", groupid, null);

        return(all);
    }
});

Iznik.Session = new Iznik.Models.Session();