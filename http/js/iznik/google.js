define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function ($, _, Backbone, Iznik) {

    Iznik.Views.GoogleLoad = Iznik.View.extend({
        authResult: undefined,

        disabled: false,

        tryingGoogleLogin: false,   // CC

        onSignInCallback: function (authResult) {
            var self = this;

            function doIt(authResult) {
                self.authResult = authResult
                $.ajax({
                    type: 'POST',
                    url: API + 'session',
                    data: {
                        'googleauthcode': self.authResult.code,
                        'googlelogin': true,
                        'mobile': true,
                    },
                    success: function (result) {
                        console.log(result);
                        if (result.ret != 0) {
                            $('.js-signin-msg').text(JSON.stringify(result))
                            $('.js-signin-msg').show()
                        } else {
                            Router.mobileReload()  // CC
                        }
                    }
                });
            }

            if (authResult['access_token']) {
                self.accessToken = authResult['access_token']
                console.log("Signed in")
                // The user is signed in.  Pass the code to the server to allow it to get an access token.
                doIt(authResult)
            } else if (authResult['error']) {
                // TODO
                console.log('There was an error: ' + authResult['error'])
            }
        },

        signInButton: function (id) {
            // TODO This is an ignorant and outrageous hack which gets the gapi var from index.ejs.
            var gapi = document.getElementById('thebody').gapi

            try {
                console.log("google.signInButton")
                var self = this
                self.buttonId = id
                self.scopes = "profile email"

                /* // CC if (_.isUndefined(window.gapi)) {
                    // This happens with Firefox privacy blocking.
                    self.disabled = true
                } */

                if (self.disabled) {
                    console.error("Google sign in failed - blocked?")
                    $('#' + id + ' img').addClass('signindisabled')
                    $('.js-privacy').show()
                } else {
                    // console.log("Google sign in enabled");
                    $('#' + id + ' img').removeClass('signindisabled')
                    $('#' + id).click(function () {
                        // Get client id
                        console.log("Log in")

                        // CC..
                        if (navigator.connection.type === Connection.NONE) {
                            console.log("No connection - please try again later.")
                            $('.js-signin-msg').text("No internet connection - please try again later")
                            $('.js-signin-msg').show()
                            return
                        }
                        self.googleAuth()
                    });
                }
            } catch (e) {
                console.log("Google API load failed", e)
            }
        },

        googleAuth: function () { // CC
            var self = this

            if (self.tryingGoogleLogin) { return }
            self.clientId = GOOGLE_CLIENT_ID;
            console.log("Google clientId: " + self.clientId)
            self.tryingGoogleLogin = true

            // Not needed: window.plugins.googleplus.trySilentLogin(
            window.plugins.googleplus.login(
                {
                    //'scopes': '... ', // optional - space-separated list of scopes, If not included or empty, defaults to `profile` and `email`.
                    'webClientId': self.clientId, // optional - clientId of your Web application from Credentials settings of your project - On Android, this MUST be included to get an idToken. On iOS, it is not required.
                    'offline': true, // Must be true to get serverAuthCode
                },
                function (obj) {    // SUCCESS
                    //alert(JSON.stringify(obj)); // do something useful instead of alerting
                    console.log(obj)
                    self.tryingGoogleLogin = false
                    if (!obj.serverAuthCode){
                        $('.js-signin-msg').text("No serverAuthCode")
                        $('.js-signin-msg').show()
                        return
                    }
                    // Try logging in again at FD with given authcode
                    var authResult = { code: obj.serverAuthCode }  // accessToken
                    authResult['access_token'] = true
                    self.onSignInCallback(authResult)
                },
                function (msg) {    // ERROR
                    //alert('error: ' + msg);
                    self.tryingGoogleLogin = false
                    $('.js-signin-msg').text("Google error:" + msg)
                    $('.js-signin-msg').show()
                    console.log("Google error:" + msg, { typ: 1 })
                }
            );
            console.log("Google after call")
        },

        noop: function (authResult) {
            console.log("Noop", authResult)
            $('#googleshim').hide()
        },

        disconnectUser: function () {

            //alert("Disconnecting");
            try{
                window.plugins.googleplus.disconnect(
                    function (msg) {
                        //alert("Disconnected");
                        console.log(msg) // do something useful instead of alerting
                    }
                );
            } catch (e) {
                console.log("Disconnect except "+e)
            }

            var self = this
            var access_token = self.accessToken
            var revokeUrl = 'https://accounts.google.com/o/oauth2/revoke?token=' +
                access_token

            // Perform an asynchronous GET request.
            $.ajax({
                type: 'GET',
                url: revokeUrl,
                async: false,
                contentType: "application/json",
                dataType: 'jsonp',
                success: function (nullResponse) {
                    console.log("Revoked access token")
                },
                error: function (e) {
                    console.log("Revoke failed", e)
                }
            });
        }
    });
});
