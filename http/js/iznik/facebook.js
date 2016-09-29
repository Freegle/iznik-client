define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/openfb'
], function ($, _, Backbone, Iznik) {
  // TODO Make configurable
  var facebookAppId = 134980666550322;

  var tryingFacebookLogin = false;

  Iznik.Views.FBLoad = Iznik.View.extend({

    signin: function () {  // CC..
      var self = this;
      if (navigator.connection.type === Connection.NONE) {
        console.log("No connection - please try again later.");
        $('.js-signin-msg').text("No internet connection - please try again later");
        $('.js-signin-msg').show();
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

      $('.js-signin-msg').text("response.status: " + response.status);
      $('.js-signin-msg').show();

      if (response.status === 'connected') {

        console.log("API.post session_login fbauthtoken: " + response.authResponse.token);

        // don't seem to need response.authResponse.token
        $('.js-signin-msg').text(response.authResponse.token);
        $('.js-signin-msg').show();

        // We're logged in on the client -
        Iznik.Session.facebookLogin(response.authResponse.token);

        Iznik.Session.listenToOnce(Iznik.Session, 'facebookLoggedIn', function () {
          Router.userHome();
        });

      } else {
        console.log(response.error); // Facebook permission not given or failed
        $('.js-signin-msg').text(response.error);
        $('.js-signin-msg').show();
      }
    }
	
  });
});