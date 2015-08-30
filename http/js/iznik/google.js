Iznik.Views.GoogleLoad = IznikView.extend({
    authResult: undefined,

    disabled: false,

    onSignInCallback: function (authResult) {
        var self = this;
        console.log("onSignInCallback", authResult);

        function doIt(authResult) {
            self.authResult = authResult;
            $.ajax({
                type: 'POST',
                url: '/api/session_login.php',
                data: {
                    'googleauthcode': self.authResult.code,
                    'googlelogin': true
                },
                success: function (result) {
                    window.location.reload();
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
            console.log("Set up sign in button", id, self.disabled);

            if (self.disabled) {
                console.log("Google sign in disabled");
                $('#' + id).addClass('signindisabled');
            } else {
                console.log("Google sign in enabled");
                $('#' + id).click(function() {
                    console.log("Log in");
                    // Custom signin button
                    var params = {
                        'clientid': '423761283916-1rpa8120tpudgv4nf44cpmlf8slqbf4f.apps.googleusercontent.com',
                        'cookiepolicy': 'single_host_origin',
                        'callback': 'onGoogleSignInCallback',
                        'immediate': false,
                        'scope': self.scopes
                    };

                    gapi.auth.signIn(params);
                });
            }
        } catch (e) {
            console.log("Google API load failed", e);
        }
    },

    buttonShim: function (id) {
        try {
            gapi.signin.render(id, {
                'clientid': '423761283916-1rpa8120tpudgv4nf44cpmlf8slqbf4f.apps.googleusercontent.com',
                'cookiepolicy': 'single_host_origin',
                'callback': 'noop',
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

GoogleLoad = new Iznik.Views.GoogleLoad();

function onGoogleSignInCallback(authResult) {
    console.log('onGoogleSignInCallback');
    GoogleLoad.onSignInCallback.call(GoogleLoad, authResult);
}

function handleGAPILoad(authResult) {
    GoogleLoad.handleGAPILoad.call(GoogleLoad, authResult);
}

function noop(authResult) {
    $('#googleshim').hide();
}