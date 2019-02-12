define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/facebook',
    'iznik/views/modal',
    'iznik/google'
], function($, _, Backbone, Iznik, FBLoad) {
    Iznik.Views.SignInUp = Iznik.Views.Modal.extend({
        className: "signinup",

        signInShown: true,

        events: {
            'click .js-loginNative': 'showNative',
            'click .js-signin': 'signin',
            'click .js-signup': 'signup',
            'click .js-register': 'register',
            'click .js-already': 'already',
            'click .js-loginYahoo': 'yahoologin',
            'click .js-loginFB': 'fblogin',
            'click .js-forgot': 'lostPassword',
            'focus .js-password': 'checkEmail',
            'keyup .js-signinform': 'enterSubmit'
        },

        checkEmail: function() {
            var self = this;

            if (!self.signInShown) {
                // Only suggest for register, not for signin.
                var email = self.$('.js-email').val();

                if (email.length > 0) {
                    var p = email.indexOf('@');

                    if (p !== -1) {
                        var domain = email.substring(p + 1);

                        $.ajax({
                            url: API + 'domains',
                            type: 'GET',
                            data: {
                                domain: domain
                            }, success: function(ret) {
                                if (ret.ret == 0 && ret.hasOwnProperty('suggestions') && ret.suggestions.length) {
                                    console.log("Suggestions", ret.suggestions);
                                    self.$('.js-suggestionlist').empty();

                                    _.each(ret.suggestions, function(s) {
                                        var m = new Iznik.Model({
                                            suggestion: email.substring(0, p + 1) + s
                                        });

                                        var v = new Iznik.Views.SignInUp.Suggestion({
                                            model: m
                                        });

                                        v.render();
                                        self.$('.js-suggestionlist').append(v.$el);
                                        self.listenTo(v, 'chosen', _.bind(function(email) {
                                            this.$('.js-email').val(email);
                                            self.$('.js-suggestions').fadeOut('slow');
                                        }, self));
                                    })
                                    self.$('.js-suggestions').fadeIn('slow');
                                }
                            }
                        })
                    }
                }
            }
        },

        enterSubmit: function (e) {
            switch (e.keyCode) {
                case 13: //enter
                    if (this.signInShown) {
                        this.signin();
                    } else {
                        this.signup();
                    }
                    break;
            }
        },

        register: function(e) {
            this.signInShown = false;
            this.$('.js-registerhide').hide();
            this.$('.js-signinerror').hide();
            this.$('.js-registershow').fadeIn('slow');
            this.$('.js-firstname').focus();
        },

        already: function(e) {
            this.signInShown = true;
            this.$('.js-registershow').hide();
            this.$('.js-signinerror').hide();
            this.$('.js-registerhide').fadeIn('slow');
            this.$('.js-email').focus();
        },

        signin: function () {
            console.log("signin1");
            var self = this;
            self.$('.js-signinerror').hide();

            try {
                Storage.set('myemail', self.$('.js-signinform .js-email').val());
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
                        // We're logged in.  Reload this page, and now that we are logged in the route
                        // should behave differently.
                        // We're logged in.  Force a test login to get our info.
                        self.listenToOnce(Iznik.Session, 'isLoggedIn', function() {
                            // Reload this page, and now that we are logged in the route
                            // should behave differently.
                            Router.mobileReload()  // CC
                            // CC window.location.reload()
                        });

                        Iznik.Session.testLoggedIn(['all']);
                    } else if (parseInt(ret.ret) == 2) {
                        self.$('.js-unknown').fadeIn('slow');
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
            return false;
        },

        signup: function () {
            var self = this;
            self.$('.js-signinerror').hide();
            $.ajax({
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PUT'
                },
                url: API + "user",
                data: {
                    'firstname': self.$('.js-firstname').val(),
                    'lastname': self.$('.js-lastname').val(),
                    'email': self.$('.js-email').val(),
                    'password': self.$('.js-password').val()
                },
                success: function (ret) {
                    if (parseInt(ret.ret) == 0) {
                        // We're logged in.  Force a test login, which will refresh any locally cached session.
                        self.listenToOnce(Iznik.Session, 'isLoggedIn', function() {
                            // Reload this page, and now that we are logged in the route
                            // should behave differently.
                            Router.mobileReload()  // CC
                            // CC window.location.reload()
                        });

                        Iznik.Session.testLoggedIn(true);
                    } else {
                        self.$('.js-signuperror .js-errmsg').html(ret.status);
                        self.$('.js-signuperror').fadeIn('slow');
                    }
                },
                error: function () {
                    self.$('.js-signuperror .js-errmsg').val('Sorry, something went wrong.  Please try later.');
                    self.$('.js-signuperror').fadeIn('slow');
                    Iznik.Session.trigger('loginFailed');
                }
            });
            return false;
        },

        fblogin: function () {
            var self = this;
            // CC: FB causes iOS exception: console.log("FB Login", FB)

            var FBLoad = new Iznik.Views.FBLoad();
            FBLoad.signin();
        },
        
        yahoologin: function () {
            if (navigator.connection.type === Connection.NONE) {  // CC
              console.log("No connection - please try again later.")
              $('.js-signin-msg').text("No internet connection - please try again later")
              $('.js-signin-msg').show()
              return
            }

            Iznik.Session.yahooLogin()
        },

        showNative: function () {
            var self = this;

            this.$('.js-buttons').hide();
            this.$('.js-native').show();

            try {
                var email = Storage.get('myemail');
                if (email) {
                    self.$('.js-email').val(email);
                }
            } catch (e) {
            }
        },

        lostPassword: function (e) {
            e.preventDefault();
            var v = new Iznik.Views.SignInUp.LostPassword();
            v.render();
        },

        render: function () {
            var self = this;
            this.template = MODTOOLS ? "signinup_modtools" : "signinup_user";
            var p = this.open(this.template, null);
            p.then(function() {
                self.$('.js-native').hide();

                // We do a trick with submitting to a hidden iframe to make browsers save the password.  If there was
                // one, it'll have been restored by the browser on page load. Move it into our form now.
                self.$('.js-email').val($('#hiddenloginemail').val());
                self.$('.js-password').val($('#hiddenloginpassword').val());

                try {
                    var ever = Storage.get('signedinever');
                    if (ever) {
                        self.already();
                    } else {
                        self.register();
                    }

                } catch (e) {
                }

                $('.js-privacy').hide()
                $('.js-signin-msg').hide() // CC
                
                self.$('.js-loginFB').removeClass('signindisabled')  // CC

                // Load the Google API
                var GoogleLoad = new Iznik.Views.GoogleLoad();

                // We have a custom signin button which needs googleising.
                GoogleLoad.signInButton('gConnect');
            });

            return (p);
        }
    });

    Iznik.Views.SignInUp.Suggestion = Iznik.View.extend({
        template: 'signinup_suggestion',

        events: {
            'click': 'click'
        },

        click: function() {
            this.trigger('chosen', this.model.get('suggestion'));
        }
    });

    Iznik.Views.SignInUp.Result = Iznik.Views.Modal.extend({
        template: 'signinup_result'
    });

    Iznik.Views.CookieError = Iznik.Views.Modal.extend({
        template: 'signinup_cookies'
    });
    
    Iznik.Views.SignInUp.LostPassword = Iznik.Views.Modal.extend({
        template: 'signinup_lostpassword',
        
        events: {
            'click .js-send': 'send'
        },
        
        send: function() {
            var self = this;
            var email = self.$('.js-email').val();
            if (email.length == 0) {
                self.$('.js-email').focus();
            } else {
                $.ajax({
                    url: API + 'session',
                    type: 'POST',
                    data: {
                        action: 'LostPassword',
                        email: email
                    }, success: function (ret) {
                        var v = new Iznik.Views.SignInUp.LostPassword.Result();
                        v.render();
                    }
                })
            }
        },

        render: function() {
            var self = this;

            Iznik.Views.Modal.prototype.render.call(this).then(function() {
                try {
                    var email = Storage.get('myemail');
                    if (email) {
                        self.$('.js-email').val(email);
                    }
                } catch (e) {
                }
            })
        }
    });

    Iznik.Views.SignInUp.LostPassword.Result = Iznik.Views.Modal.extend({
        template: 'signinup_lostpasswordresult'
    });
});