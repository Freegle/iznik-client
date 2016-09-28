define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {

    Iznik.Views.GoogleLoad = Iznik.View.extend({
        authResult: undefined,

        disabled: false,

        tryingGoogleLogin: false,   // CC

        onSignInCallback: function (authResult) {
            var self = this;
            console.log("onSignInCallback", authResult);

            function doIt(authResult) {
                self.authResult = authResult;
                $.ajax({
                    type: 'POST',
                    url: API+'session',
                    data: {
                        'googleauthcode': self.authResult.code,
                        'googlelogin': true,
                        'mobile': true,
                    },
                    success: function (result) {
                      console.log(result);
                      if (result.ret != 0) {
                        $('.js-signin-msg').text(JSON.stringify(result));
                        $('.js-signin-msg').show();
                      } else {
                        Router.userHome();
                      }
                    }
                });
            }

            if (authResult['access_token']) {
                self.accessToken = authResult['access_token'];
                console.log("Signed in");
                // The user is signed in.  Pass the code to the server to allow it to get an access token.
                doIt(authResult);
            } else if (authResult['error']) {
                // TODO
                console.log('There was an error: ' + authResult['error']);
            }
        },

        signInButton: function (id) {
            try {
                var self = this;
                self.buttonId = id;
                self.scopes = "profile email";

                /* // CC if (_.isUndefined(window.gapi)) {
                    // This happens with Firefox privacy blocking.
                    self.disabled = true;
                } */

                console.log("Set up sign in button", id, self.disabled);

                if (self.disabled) {
                    console.error("Google sign in failed - blocked?");
                    $('#' + id + ' img').addClass('signindisabled');
                    $('.js-privacy').show();
                } else {
                    // console.log("Google sign in enabled");
                    $('#' + id + ' img').removeClass('signindisabled');
                    $('#' + id).click(function () {
                        // Get client id
                        console.log("Log in");

                        // CC..
                        if (navigator.connection.type === Connection.NONE) {
                            console.log("No connection - please try again later.");
                            //$('#' + id + ' img').addClass('signindisabled');
                            $('.js-signin-msg').text("No internet connection - please try again later");
                            $('.js-signin-msg').show();
                            return;
                        }
                        self.googleAuth();
                    });
                }
            } catch (e) {
                console.log("Google API load failed", e);
            }
        },

        googleAuth: function(){ // CC
            var self = this;
            if( self.tryingGoogleLogin){ return; }
            self.tryingGoogleLogin = true;
            var googleScope = 'https://www.googleapis.com/auth/plus.me https://www.googleapis.com/auth/userinfo.email';
            self.clientId = $('meta[name=google-signin-client_id]').attr("content");
            console.log("Google clientId: "+self.clientId);
            // Build the OAuth2 consent page URL
            var authUrl = 'https://accounts.google.com/o/oauth2/auth?' + $.param({
                    client_id: self.clientId,
                    redirect_uri: 'http://localhost', // Must match that in Google console API credentials
                    response_type: 'code',
                    //response_type: 'token',
                    scope: googleScope
                });

            var authGiven = false;

            // Open the OAuth2 consent page in the InAppBrowser
            var authWindow = window.open(authUrl, '_blank', 'location=yes,menubar=yes'); // Show location so user knows it's OK

            $(authWindow).on('loadstart', function (e) {
                // This is called more than once, eg on first load, when button pressed and when redirected to localhost with code or error
                var url = e.originalEvent.url;
                console.log("gloadstart: " + url);
                var code = /\?code=(.+)$/.exec(url); 	// code[0] is entire match, code[1] is submatch ie the code
                var error = /\?error=(.+)$/.exec(url); // error[0] is entire match, error[1] is submatch ie the error

                if (code || error) {
                    //Always close the browser when match is found
                    //console.log("Close: " + code + " - " + error);
                    authWindow.close();
                }

                if (code) {
                    if( authGiven) return;
                    authGiven = true;

                    code = code[1].split('&')[0]; // Remove any other returned parameters
                    //console.log("code: " + code);

                    // Try logging in again at FD with given authcode
                    $('.js-signin-msg').text("googleauthcode: "+code);
                    $('.js-signin-msg').show();
                    var authResult = { code:code };
                    authResult['access_token'] = true;
                    self.onSignInCallback(authResult);
                } else if (error) {
                    // The user denied access to the app
                    $('.js-signin-msg').text("Google error:" + error[1]);
                    $('.js-signin-msg').show();
                    console.log("Google error:" + error[1], { typ: 1 });
                }
            });

            $(authWindow).on('exit', function (e) {
                if (!authGiven) {
                    $('.js-signin-msg').text("Google permission not given or failed");
                    $('.js-signin-msg').show();
                    console.log("Google permission not given or failed");
                }
                self.tryingGoogleLogin = false;
            });

        },

        noop: function(authResult) {
            console.log("Noop", authResult)
            $('#googleshim').hide();
        },

        buttonShim: function (id) {
            try {
                gapi.signin.render(id, {
                    'clientid': self.clientId,
                    'cookiepolicy': 'single_host_origin',
                    'callback': self.noop,
                    'immediate': false,
                    'scope': self.scopes
                });
            } catch (e) {
                // Probably a blocker
                console.log("Google button shim failed", e);
                this.disabled = true;
            }
        },

        disconnectUser: function () {
            var self = this;
            var access_token = self.accessToken;
            var revokeUrl = 'https://accounts.google.com/o/oauth2/revoke?token=' +
                access_token;

            // Perform an asynchronous GET request.
            $.ajax({
                type: 'GET',
                url: revokeUrl,
                async: false,
                contentType: "application/json",
                dataType: 'jsonp',
                success: function (nullResponse) {
                    console.log("Revoked access token");
                },
                error: function (e) {
                    console.log("Revoke failed", e);
                }
            });
        }
    });
});