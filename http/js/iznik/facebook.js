define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    var facebookAppId = $('meta[name=facebook-app-id]').attr("content");
    var facebookGraffitiAppId = $('meta[name=facebook-graffiti-app-id]').attr("content");

    Iznik.Views.FBLoad = Iznik.View.extend({
        FBLoaded: false,
        FBLoading: false,
        FBDisabled: false,

        isDisabled: function () {
            return this.FBDisabled;
        },

        render: function (appid) {
            appid = appid ? appid : facebookAppId;
            var self = this;
            // console.log("Render FBLoad");

            if (self.FBLoaded) {
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
                    js.src = "//connect.facebook.net/en_US/sdk.js";
                    fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));

                window.fbAsyncInit = function () {
                    // console.log("FB asyncInit");
                    self.FBLoading = false;
                    self.FBLoaded = true;
                    clearTimeout(self.timeout);

                    try {
                        FB.init({
                            appId: appid,
                            cookie: true,  // enable cookies to allow the server to access the session
                            version: 'v2.10'
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
            }
        }
    });

    // This is a singleton view.
    var instance;

    return function(options) {
        if (!instance) {
            instance = new Iznik.Views.FBLoad(options);
        }

        return instance;
    }
});