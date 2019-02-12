// CC Web FB API cannot be in mobile app so (a) signin done (altered) openfb and (b) share to FB disabled for now
define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/openfb'
], function ($, _, Backbone, Iznik, openFB) {

    var facebookAppId = FACEBOOK_APPID;

    var tryingFacebookLogin = false;

    Iznik.Views.FBLoad = Iznik.View.extend({
        FBLoaded: false,
        FBLoading: false,
        FBDisabled: false,

        isDisabled: function () {
            return this.FBDisabled;
        },
        render: function () {
            var self = this;
            //console.log("Render FBLoad");
            this.trigger('fbloaded');   // For Facebook groups
            /*if (self.FBLoaded) {
                // console.log("Already loaded");
                this.trigger('fbloaded');
            } else if ((!self.FBLoaded) && (!self.FBLoading)) {
                // console.log("Load FB API");
                self.FBLoading = true;

                // The load might fail if we have a blocker.  The only way to deal with this is via a timeout.
                self.timeout = window.setTimeout(function () {
                    console.error("Facebook API load failed - blocked?");
                    self.FBLoading = false;
                    self.FBLoaded = true;
                    self.FBDisabled = true;
                    $('.js-privacy').show();
                    self.trigger('fbloaded');
                }, 30000);

                // Load the SDK asynchronously
                (function (d, s, id) {
                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id)) return;
                    js = d.createElement(s);
                    js.id = id;
                    js.src = "https://connect.facebook.net/en_US/sdk.js";
                    fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));

                window.fbAsyncInit = function () {
                    // console.log("FB asyncInit");
                    self.FBLoading = false;
                    self.FBLoaded = true;
                    clearTimeout(self.timeout);

                    try {
                        FB.init({
                            appId: facebookAppId,
                            cookie: true,  // enable cookies to allow the server to access the session
                            version: 'v2.2' // use version 2.2
                        });

                        self.trigger('fbloaded');
                        // console.log("FB Loaded");
                    } catch (e) {
                        console.log("Facebook init failed");
                        console.log(e);
                    }
                }
            } else {
                // console.log("FB still loading...");
            }*/
        },

        signin: function () {  // CC..
            var self = this;
            if (navigator.connection.type === Connection.NONE) {
                console.log("No connection - please try again later.");
                $('.js-signin-msg').text("No internet connection - please try again later")
                $('.js-signin-msg').show()
                return;
            }

            if (tryingFacebookLogin) { return; }
            tryingFacebookLogin = true;

            var fbTokenStore = window.sessionStorage; // or could be window.localStorage
            openFB.init({ appId: facebookAppId, tokenStore: fbTokenStore });
            console.log("Facebook authenticate window open");
            var options = { scope: 'email' };
            openFB.login(self.fb_done, options);
        },

        ///////////////////////////////////////
        fb_done: function (response) {
            tryingFacebookLogin = false;

            if (response.status === 'connected') {

                console.log("API.post session_login fbauthtoken: " + response.authResponse.token);

                // We're logged in on the client -
                Iznik.Session.facebookLogin(response.authResponse.token);

                Iznik.Session.listenToOnce(Iznik.Session, 'facebookLoggedIn', function () {
                    setTimeout(Router.mobileReload(), 0);
                });

            } else {
                console.log(response.error); // Facebook permission not given or failed
                $('.js-signin-msg').text(response.error);
                $('.js-signin-msg').show();
            }
        }
    });

    // This is a singleton view.
    var instance;

    return function (options) {
        if (!instance) {
            instance = new Iznik.Views.FBLoad(options);
        }

        return instance;
    }
});