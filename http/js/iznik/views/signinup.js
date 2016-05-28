define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/modal',
    'iznik/facebook',
    'iznik/google'
], function($, _, Backbone, Iznik) {
    Iznik.Views.SignInUp = Iznik.Views.Modal.extend({
        className: "signinup",

        events: {
            'click .js-loginNative': 'showNative',
            'click .js-signin': 'signin',
            'click .js-signup': 'signup',
            'click .js-register': 'register',
            'click .js-loginYahoo': 'yahoologin',
            'click .js-loginFB': 'fblogin',
            'click .js-forgot': 'lostPassword',
            'keyup .js-signinform .js-password': 'enterSubmit',
            'keyup .js-signupform .js-password': 'enterSubmit2'
        },

        'enterSubmit': function (e) {
            switch (e.keyCode) {
                case 13: //enter
                    this.signin();
                    break;
            }
        },

        'enterSubmit2': function (e) {
            switch (e.keyCode) {
                case 13: //enter
                    this.signup();
                    break;
            }
        },

        register: function(e) {
            this.$('.js-registerhide').hide();
            this.$('.js-signinerror').hide();
            this.$('.js-registershow').fadeIn('slow');
            this.$('.js-email').focus();
        },

        signin: function () {
            var self = this;
            self.$('.js-signinerror').hide();

            try {
                localStorage.setItem('myemail', self.$('.js-signinform .js-email').val());
            } catch (e) {
            }

            $.ajax({
                type: "POST",
                url: API + "session",
                data: {
                    'email': self.$('.js-signinform .js-email').val(),
                    'password': self.$('.js-signinform .js-password').val()
                },
                success: function (ret) {
                    if (ret.ret == 0) {
                        Iznik.Session.set('loggedin', true);
                        Iznik.Session.trigger('loggedIn');

                        // We're logged in.  Reload this page, and now that we are logged in the route
                        // should behave differently.
                        window.location.reload();
                    } else {
                        self.$('.js-signinerror .js-errmsg').html(ret.status);
                        self.$('.js-signinerror').fadeIn('slow');
                        Iznik.Session.trigger('loginFailed');
                    }
                },
                error: function () {
                    self.$('.js-signinerror .js-errmsg').val('Sorry, something went wrong.  Please try later.');
                    self.$('.js-signinerror').fadeIn('slow');
                    Iznik.Session.trigger('loginFailed');
                }
            });
        },

        signup: function () {
            var self = this;
            self.$('.js-signinerror').hide();
            $.ajax({
                type: "PUT",
                url: API + "user",
                data: {
                    'email': self.$('.js-email').val(),
                    'password': self.$('.js-password').val()
                },
                success: function (ret) {
                    console.log("Register", ret);
                    if (parseInt(ret.ret) == 0) {
                        // We're logged in.  Reload this page, and now that we are logged in the route
                        // should behave differently.
                        window.location.reload();
                    } else {
                        self.$('.js-signuperror .js-errmsg').html(ret.error);
                        self.$('.js-signuperror').fadeIn('slow');
                    }
                },
                error: function () {
                    self.$('.js-signuperror .js-errmsg').val('Sorry, something went wrong.  Please try later.');
                    self.$('.js-signuperror').fadeIn('slow');
                    Iznik.Session.trigger('loginFailed');
                }
            });
        },

        fblogin: function () {
            var self = this;

            // Now, load the FB API.
            FB.login(function(response) {
                console.log("FBLogin returned", response);
                if (response.authResponse) {
                    // We're logged in on the client -
                    Iznik.Session.facebookLogin();

                    Iznik.Session.listenToOnce(Iznik.Session, 'facebookLoggedIn', function () {
                        window.location.reload();
                    });
                }
            });
        },
        
        yahoologin: function () {
            this.listenToOnce(Iznik.Session, 'yahoologincomplete', function (ret) {
                if (ret.hasOwnProperty('redirect')) {
                    window.location = ret.redirect;
                } else if (ret.ret == 0) {
                    window.location.reload();
                } else {
                    window.location.reload();
                }
            });

            Iznik.Session.yahooLogin();
        },

        showNative: function () {
            this.$('.js-buttons').hide();
            this.$('.js-native').show();

            try {
                var email = localStorage.getItem('myemail');
                if (email) {
                    self.$('.js-email').val(email);
                }
            } catch (e) {
            }
        },

        lostPassword: function () {
            var self = this;
            var email = self.$('.js-signinform .js-email').val();
            if (email.length == 0) {
                self.$('.js-signinform .js-email').focus();
            } else {
                $.ajax({
                    url: API + 'user_lostpassword',
                    type: 'POST',
                    data: {
                        email: email
                    }, success: function (ret) {
                        var v = new Iznik.Views.SignInUp.Result({
                            model: new FDModel({
                                ret: ret
                            })
                        });
                        v.render();
                    }
                })
            }
        },

        render: function () {
            var self = this;
            this.template = this.options.modtools ? "signinup_modtools" : "signinup_user";
            this.open(this.template, null);
            this.$('.js-native').hide();

            try {
                var email = localStorage.getItem('nativeemail');
                if (email) {
                    self.$('.js-email').val(email);
                }
            } catch (e) {
            }

            // We have to load the FB API now because otherwise when we click on the login button, we can't load
            // it synchronously, and therefore the login popup would get blocked by the browser.
            var FBLoad = new Iznik.Views.FBLoad();
            self.listenToOnce(FBLoad, 'fbloaded', function () {
                if (!FBLoad.isDisabled()) {
                    self.$('.js-loginFB').removeClass('signindisabled');
                }
            });
            FBLoad.render();

            // Load the Google API
            var GoogleLoad = new Iznik.Views.GoogleLoad();

            // We have a custom signin button which needs googleising.
            GoogleLoad.signInButton('gConnect');

            return (this);
        }
    });

    Iznik.Views.SignInUp.Result = Iznik.Views.Modal.extend({
        template: 'signinup_result'
    });

    Iznik.Views.CookieError = Iznik.Views.Modal.extend({
        template: 'signinup_cookies'
    });
});