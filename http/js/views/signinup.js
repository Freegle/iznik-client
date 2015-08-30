Iznik.Views.SignInUp = Iznik.Views.Modal.extend({
    // TODO remember me.  Server supports it.
    className: "signinup",

    template: "signinup_main",

    events: function () {
        return _.extend({}, _.result(Iznik.Views.Modal, 'events'), {
            'click .js-loginNative': 'showNative',
            'click .js-signin': 'signin',
            'click .js-signup': 'signup',
            'click .js-loginYahoo': 'yahoologin',
            'click .js-loginFB': 'fblogin',
            'click .js-forgot': 'lostPassword',
            'keyup .js-signinform .js-password': 'enterSubmit',
            'keyup .js-signupform .js-password': 'enterSubmit2'
        });
    },

    'enterSubmit': function(e){
        switch (e.keyCode) {
            case 13: //enter
                this.signin();
                break;
        }
    },

    'enterSubmit2': function(e){
        switch (e.keyCode) {
            case 13: //enter
                this.signup();
                break;
        }
    },

    signin: function() {
        var self = this;
        self.$('.js-signinerror').hide();

        try {
            localStorage.setItem('nativeemail', self.$('.js-signinform .js-email').val());
        } catch (e) {}

        $.ajax({
            type: "POST",
            url: API + "session_login.php",
            data: {
                'email': self.$('.js-signinform .js-email').val(),
                'password': self.$('.js-signinform .js-password').val()
            },
            success: function(ret)
            {
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
            error: function() {
                self.$('.js-signinerror .js-errmsg').val('Sorry, something went wrong.  Please try later.');
                self.$('.js-signinerror').fadeIn('slow');
                Iznik.Session.trigger('loginFailed');
            }
        });
    },

    signup: function() {
        var self = this;
        self.$('.js-signinerror').hide();
        $.ajax({
            type: "POST",
            url: API + "register.php",
            data: {
                'email': self.$('.js-signupform .js-email').val(),
                'password': self.$('.js-signupform .js-password').val(),
                'name': self.$('.js-signupform .js-firstname').val() + ' ' + self.$('.js-signupform .js-lastname').val()
            },
            success: function(ret)
            {
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
            error: function() {
                self.$('.js-signuperror .js-errmsg').val('Sorry, something went wrong.  Please try later.');
                self.$('.js-signuperror').fadeIn('slow');
                Iznik.Session.trigger('loginFailed');
            }
        });
    },

    fblogin: function(){
        var login = new Iznik.Views.FBLogin();
        this.listenToOnce(login, 'fbloginsucceeded', function(){
            // We're logged in on the client -
            Iznik.Session.facebookLogin();

            Iznik.Session.listenToOnce(Iznik.Session, 'facebookLoggedIn', function(){
                window.location.reload();
            });
        });
        login.render();
    },

    yahoologin: function() {
        this.listenToOnce(Iznik.Session, 'yahoologincomplete', function(ret) {
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

    showNative: function() {
        this.$('.js-buttons').hide();
        this.$('.js-native').show();
    },

    lostPassword: function() {
        var self = this;
        var email = self.$('.js-signinform .js-email').val();
        if (email.length == 0) {
            self.$('.js-signinform .js-email').focus();
        } else {
            $.ajax({
                url: API + 'lostpassword.php',
                type: 'POST',
                data: {
                    email: email
                }, success: function(ret) {
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

    render: function() {
        var self = this;
        this.open(this.template, null);
        this.$('.js-native').hide();

        self.listenToOnce(FBLoad, 'fbloaded', function(){
            // We have a custom signin button which needs googleising.
            GoogleLoad.signInButton('gConnect');

            if (FBLoad.isDisabled()) {
                self.$('.js-loginFB').addClass('signindisabled');
            }
        });
        FBLoad.render();

        try {
            var email = localStorage.getItem('nativeemail');
            if (email) {
                self.$('.js-email').val(email);
            }
        } catch (e) {}

        return(this);
    }
});

Iznik.Views.SignInUp.Result = Iznik.Views.Modal.extend({
    template: 'signinup_result',

    render: function() {
        var self = this;
        this.open(this.template, self.model);
    }
});

Iznik.Views.CookieError = Iznik.Views.Modal.extend({
    template: 'signinup_cookies',

    render: function() {
        var self = this;
        this.open(this.template, self.model);
        return(this);
    }
});
