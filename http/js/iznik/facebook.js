// TODO Make configurable
var facebookAppId = 123;

Iznik.Views.FBLoad = IznikView.extend({
    FBLoaded: false,
    FBLoading: false,
    FBDisabled: false,

    isDisabled: function() {
        return this.FBDisabled;
    },

    render: function(){
        var self = this;

        if (self.FBLoaded){
            console.log("Loaded already");
            this.trigger('fbloaded');
        } else if((!self.FBLoaded) && (!self.FBLoading)){
            console.log("Not loaded FB yet");
            self.FBLoading = true;

            // The load might fail if we have a blocker.  The only way to deal with this is via a timeout.
            self.timeout = window.setTimeout(function() {
                console.log("Facebook failed to load");
                self.FBLoading = false;
                self.FBLoaded = true;
                self.FBDisabled = true;
                self.trigger('fbloaded');
            }, 5000);

            // Load the SDK asynchronously
            (function(d, s, id){
                var js, fjs = d.getElementsByTagName(s)[0];
                if(d.getElementById(id)) return;
                js = d.createElement(s);
                js.id = id;
                js.src = "//connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));

            window.fbAsyncInit = function(){
                console.log("Facebook loaded");
                self.FBLoading = false;
                self.FBLoaded = true;

                try{
                    console.log("Call init");
                    FB.init({
                        appId: facebookAppId,
                        cookie: true,  // enable cookies to allow the server to access the session
                        xfbml: true,  // parse social plugins on this page
                        version: 'v2.0' // use version 2.0
                    });

                    // We need to check the login status - otherwise if we try to share using
                    // dialog mode the SDK will use a popup instead, which the browser will block.
                    console.log("Get login status");
                    FB.getLoginStatus(function(response){
                        window.clearTimeout(self.timeout);
                        console.log("FB Login status rsp");
                        console.log(response);
                        if(((window.location.search.indexOf('fb_sig_in_iframe=1') > -1) ||
                            (window.location.search.indexOf('session=') > -1) ||
                            (window.location.search.indexOf('signed_request=') > -1) ||
                            (window.name.indexOf('iframe_canvas') > -1) ||
                            (window.name.indexOf('app_runner') > -1))){
                            //console.log("In FB canvas");
                            Iznik.Session.set('canvas', true);

                            var login = new Iznik.Views.FBLogin();
                            //console.log(login);
                            Iznik.Session.listenToOnce(login, 'fbloginsucceeded', function(){
                                // We are in a canvas app, so we want to trigger a login to pass the session through
                                // to the server.
                                //console.log("Logged in for canvas");
                                Iznik.Session.listenToOnce(Iznik.Session, 'facebookLoggedIn', function(){
                                    console.log("Logged in on server, go to " + Backbone.history.fragment);
                                    Router.navigate(Backbone.history.fragment + "?t=" + (new Date()).getTime() , {
                                        trigger: true
                                    });
                                });

                                Iznik.Session.listenToOnce(Iznik.Session, 'loginFailed', function(){
                                    // Failed.  This can happen with session weirdness.  Log out and reload.
                                    FB.logout();
                                    Router.navigate(Backbone.history.fragment + "?t=" + (new Date()).getTime() , {
                                        trigger: true
                                    });
                                });

                                //console.log("Trigger login for server");
                                Iznik.Session.facebookLogin();
                            });

                            login.render();
                        }else{
                            //console.log("Not in canvas app");
                            self.trigger('fbloaded');
                        }
                    });
                    //console.log("Called");
                }catch(e){
                    console.log("Facebook init failed"); console.log(e);
                }
            }
        }else{
            //console.log("FB still loading...");
        }
    }
});

FBLoad = new Iznik.Views.FBLoad();

Iznik.Views.FBLogin = IznikView.extend({
    statusChangeCallback: function(response){
        var self = this;

        //console.log('statusChangeCallback');
        //console.log(response);
        // The response object is returned with a status field that lets the
        // app know the current login status of the person.
        // Full docs on the response object can be found in the documentation
        // for FB.getLoginStatus().
        if(response.status === 'connected'){
            // Logged into your app and Facebook.
            //console.log("Logged in to app and Facebook");
            //console.log(self);
            self.trigger('fbloginsucceeded');
        }else if(response.status === 'not_authorized'){
            // The person is logged into Facebook, but not your app.
            //console.log("Login failed 1");
            self.trigger('fbloginfailed');
        }else{
            // The person is not logged into Facebook, so we're not sure if
            // they are logged into this app or not.
            //console.log("Login failed 2");
            FB.logout();
            window.location.reload();
        }
    },

    checkLoginState: function(){
        var self = this;

        FB.getLoginStatus(function(response){
            self.statusChangeCallback.call(self, response);
        });
    },

    render: function(){
        var self = this;

        FB.login(function(response){
            //console.log("Done Fb.login");
            //console.log(response);
            FB.getLoginStatus(function(response){
                self.statusChangeCallback.call(self, response);
            });
        }, {scope: 'email'});

        return this;
    }
});

Iznik.Views.FBShare = IznikView.extend({
    initialize: function(options){
        this.options = options;
        this.render.call(this);
    },

    render: function(){
        var self = this;

        FB.ui(
            {
                method: 'feed',
                display: 'iframe',
                link:  self.options.joingroup,
                picture: self.options.attachment ? self.options.attachment : "http://directv2.ilovefreegle.org/images/logo.png",
                name: 'just posted ' + self.options.subject,
                description: 'Don\'t throw it away, give it away!',
                caption : 'Click here to explore ' + self.options.grouptitle + ' Freegle',
                actions: {
                    name: 'Explore ' + self.options.grouptitle + ' Freegle',
                    link: self.options.joingroup
                },
                user_message_prompt: 'Help spread the word - let people know you\'re freegling.'
            },
            function(response) {
                Iznik.Session.trigger('shared');
            });
    }
});
