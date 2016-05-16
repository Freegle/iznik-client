define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/chat/chat',
    'iznik/events',
    'iznik/accordionpersist',
    'iznik/views/group/select',
    'iznik/views/infinite',
    'iznik/views/plugin',
], function($, _, Backbone, Iznik, ChatHolder, monitorDOM) {
    // We have a view for everything that is common across all pages, e.g. sidebars.
    var currentPage = null;

    function logout() {
        try {
            // We might be signed in to Google.  Make sure we're not.
            gapi.auth.signOut();
            console.log("Google signed out");
            var GoogleLoad = new Iznik.Views.GoogleLoad();
            GoogleLoad.disconnectUser();
            console.log("Google access token revoked");
        } catch (e) {
            console.log("Google signout failed", e);
        };

        $.ajax({
            url: API + 'session',
            type: 'POST',
            headers: {
                'X-HTTP-Method-Override': 'DELETE'
            },
            complete: function () {
                // Zap our session cache - we're no longer logged in.
                try {
                    localStorage.removeItem('session');
                } catch (e) {
                }

                // Force reload of window to clear any data.
                window.location = window.location.protocol + '//' + window.location.host;
            }
        })
    }

    Iznik.Views.Page = Iznik.View.extend({
        modtools: false,

        events: {
            'click .js-signin': 'signin'
        },

        home: function () {
            Router.navigate(this.modtools ? '/modtools' : '/', true);
        },

        signin: function () {
            var sign = new Iznik.Views.SignInUp({
                modtools: this.modtools
            });
            sign.render();
        },

        logout: function() {
            console.log("logout");
            logout();
        },

        render: function (options) {
            var self = this;

            // Start event tracking.
            if (monitorDOM) {
                monitorDOM.start();
            }

            // try {
                if (currentPage) {
                    // We have previous rendered a page.  Kill that off, so that it is not listening for events and
                    // messing about with the DOM.
                    currentPage.remove();
                }

                currentPage = self;

                // Record whether we are showing a user or ModTools page.
                Iznik.Session.set('modtools', self.modtools);

                options = typeof options == 'undefined' ? {} : options;

                var rightbar = null;
                var rightaccordion = $('#rightaccordion');

                if (rightaccordion.length > 0) {
                    // We render the right sidebar only once, so that the plugin work remains there if we route to a new page
                    rightbar = rightaccordion.children().detach();
                }

                // Set the base page layout.  Save and restore the minimised chats which would otherwise get zapped.
                var chats = $('#notifchatdropdown').children().detach();
                $('#bodyContent').html(this.modtools ?
                    window.template('modtools_layout_layout') :
                    window.template('user_layout_layout'));
                $('.js-pageContent').html(this.$el);
                $('#notifchatdropdown').html(chats);
                if (chats.length) {
                    $('#js-notifchat').show();
                } else {
                    $('#js-notifchat').hide();
                }

                if (this.modtools) {
                    // ModTools menu and sidebar.
                    var m = new Iznik.Views.ModTools.LeftMenu();
                    $('.js-leftsidebar').html(m.render().el);

                    rightaccordion = $('#rightaccordion');

                    if (!rightbar) {
                        var s = new Iznik.Views.Supporters();
                        rightaccordion.append(s.render().el);

                        window.IznikPlugin = new Iznik.Views.Plugin.Main();
                        rightaccordion.append(IznikPlugin.render().el);
                        rightaccordion.accordionPersist();
                    } else {
                        rightaccordion.empty().append(rightbar);
                    }

                    if (options.noSupporters) {
                        $('.js-supporters').hide();
                    } else {
                        $('.js-supporters').show();
                    }
                }

                // Put this page in
                this.$el.html(window.template(this.template)(Iznik.Session.toJSON2()));
                $('.js-pageContent').append(this.$el);

                // Show anything which should or shouldn't be visible based on login status.
                this.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    var loggedInOnly = $('.js-loggedinonly');
                    var loggedOutOnly = $('.js-loggedoutonly');

                    if (loggedIn) {
                        loggedInOnly.removeClass('reallyHide');
                        loggedOutOnly.addClass('reallyHide');

                        // Since we're logged in, we can start chat.
                        ChatHolder({
                            modtools: self.modtools
                        }).render();
                    } else {
                        loggedOutOnly.removeClass('reallyHide');
                        loggedInOnly.addClass('reallyHide');
                    }
                });

                Iznik.Session.testLoggedIn();

                // Sort out any menu
                $("#menu-toggle").click(function (e) {
                    e.preventDefault();
                    $("#wrapper").toggleClass("toggled");
                });

                window.scrollTo(0, 0);

                // Let anyone who cares know.
                this.trigger('pageContentAdded');

                // This doesn't work as an event as it's outwith our element, so attach manually.
                if (this.home) {
                    $('#bodyContent .js-home').click(_.bind(this.home, this));
                }

                if (this.signin) {
                    $('#bodyContent .js-signin').click(_.bind(this.signin, this));
                }

                $('.js-logout').click(function() {
                    logout();
                });
            // } catch (e) {
            //     console.error("Page render failed", e.message);
            // }
        }
    });

    Iznik.Views.User.Pages.NotFound = Iznik.Views.Page.extend({
        template: "notfound"
    });

    Iznik.Views.ModTools.LeftMenu = Iznik.View.extend({
        template: "layout_leftmenu",

        events: {
            'click .js-logout': 'logout'
        },

        logout: function () {
            logout();
        },

        render: function () {
            this.$el.html(window.template(this.template));

            // Bypass caching for plugin load
            this.$('.js-firefox').attr('href',
                this.$('.js-firefox').attr('href') + '?' + Math.random()
            );

            // Highlight current page if any.
            this.$('a').each(function () {
                var href = $(this).attr('href');
                $(this).closest('li').removeClass('active');

                if (href == window.location.pathname) {
                    $(this).closest('li').addClass('active');

                    // Force reload on click, which doesn't happen by default.
                    $(this).click(function () {
                        Backbone.history.loadUrl(href);
                    });
                }
            });

            if (Iznik.Session.isAdminOrSupport()) {
                this.$('.js-adminsupportonly').removeClass('hidden');
            }

            if (Iznik.Session.isAdmin()) {
                this.$('.js-adminonly').removeClass('hidden');
            }

            // We need to create a hidden signin button because otherwise the Google logout method doesn't
            // work properly.  See http://stackoverflow.com/questions/19353034/how-to-sign-out-using-when-using-google-sign-in/19356354#19356354
            var GoogleLoad = new Iznik.Views.GoogleLoad();
            GoogleLoad.buttonShim('googleshim');

            return this;
        }
    });

    Iznik.Views.Supporters = Iznik.View.extend({
        className: "panel panel-default js-supporters",

        template: "layout_supporters",

        render: function () {
            var self = this;

            $.ajax({
                url: API + 'supporters',
                success: function (ret) {
                    self.$el.html(window.template(self.template));

                    var html = '';
                    _.each(ret.supporters.Wowzer, function (el, index, list) {
                        if (index == ret.supporters.Wowzer.length - 1) {
                            html += ' and '
                        } else if (index > 0) {
                            html += ', '
                        }

                        html += el.name;
                    });
                    self.$('.js-wowzer').html(html);

                    var html = '';
                    _.each(ret.supporters['Front Page'], function (el, index, list) {
                        if (index == ret.supporters['Front Page'].length - 1) {
                            html += ' and '
                        } else if (index > 0) {
                            html += ', '
                        }

                        html += el.name;
                    });
                    self.$('.js-frontpage').html(html);

                    self.$('.js-content').fadeIn('slow');
                }
            });

            return self;
        }
    });

    Iznik.Views.ModTools.Pages.Supporters = Iznik.Views.Page.extend({
        modtools: true,

        template: "supporters",

        render: function () {
            var self = this;

            Iznik.Views.Page.prototype.render.call(this, {
                noSupporters: true
            });

            $.ajax({
                url: API + 'supporters',
                success: function (ret) {
                    self.$el.html(window.template(self.template));

                    var html = '';

                    function add(el, index, list) {
                        console.log("Add", el.name);
                        if (html) {
                            html += ', '
                        }

                        html += el.name;
                    }

                    _.each(ret.supporters['Wowzer'], add);
                    _.each(ret.supporters['Front Page'], add);
                    _.each(ret.supporters['Supporter'], add);

                    self.$('.js-list').html(html);
                    self.$('.js-content').fadeIn('slow');
                }
            });

            return self;
        }
    });
});