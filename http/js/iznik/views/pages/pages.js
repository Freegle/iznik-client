define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/chat/chat',
    'iznik/events'
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
        
        footer: false,

        events: {
            'click .js-signin': 'signin',
            'click .js-notifchat': 'refreshChats'
        },

        refreshChats: function() {

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
            logout();
        },

        refreshChats: function() {
            Iznik.Session.chats.fetch({
                remove: false
            });
        },

        render: function (options) {
            var self = this;

            // Start event tracking.
            if (monitorDOM) {
                monitorDOM.start();
            }

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

            // Set the base page layout.
            var p = new Promise(function(resolve, reject) {
                templateFetch(self.modtools ? 'modtools_layout_layout' : 'user_layout_layout').then(function(tpl) {
                    // Save and restore the minimised chats which would otherwise get zapped.
                    var chats = $('#notifchatdropdown').children().detach();

                    $('#bodyContent').html(window.template(tpl));
                    $('.js-pageContent').html(self.$el);

                    $('#notifchatdropdown').html(chats);

                    if (chats.length) {
                        $('#js-notifchat').show();
                    } else {
                        $('#js-notifchat').hide();
                    }

                    if (self.modtools) {
                        // ModTools menu and sidebar.
                        new Iznik.Views.ModTools.LeftMenu().render().then(function(m) {
                            $('.js-leftsidebar').html(m.el);
                        });

                        rightaccordion = $('#rightaccordion');

                        if (!rightbar) {
                            var s = new Iznik.Views.Supporters();
                            s.render().then(function(s) {
                                rightaccordion.append(s.el);

                                require(['iznik/accordionpersist', 'iznik/views/plugin'], function() {
                                    window.IznikPlugin = new Iznik.Views.Plugin.Main();
                                    IznikPlugin.render().then(function(v) {
                                        rightaccordion.append(v.el);
                                    })
                                    rightaccordion.accordionPersist();
                                });
                            });
                        } else {
                            rightaccordion.empty().append(rightbar);
                        }

                        if (options.noSupporters) {
                            $('.js-supporters').hide();
                        } else {
                            $('.js-supporters').show();
                        }
                    }

                    // Put self page in
                    templateFetch(self.template).then(function(tpl) {
                        self.$el.html(window.template(tpl)(Iznik.Session.toJSON2()));
                        $('.js-pageContent').append(self.$el);

                        $('#footer').remove();

                        if (self.footer) {
                            var v = new Iznik.Views.Page.Footer();
                            v.render().then(function() {
                                $('body').addClass('Site');
                                $('body').append(v.$el);
                            });
                        }

                        // Show anything which should or shouldn't be visible based on login status.
                        self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                            var loggedInOnly = $('.js-loggedinonly');
                            var loggedOutOnly = $('.js-loggedoutonly');

                            if (!self.modtools && !self.noback) {
                                // For user pages, we add our background if we're logged in.
                                $('body').addClass('bodyback');
                            } else {
                                $('body').removeClass('bodyback');
                            }

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
                        self.trigger('pageContentAdded');

                        // This doesn't work as an event as it's outwith our element, so attach manually.
                        if (self.home) {
                            $('#bodyContent .js-home').click(_.bind(self.home, self));
                        }

                        if (self.signin) {
                            $('#bodyContent .js-signin').click(_.bind(self.signin, self));
                        }

                        $('.js-logout').click(function() {
                            logout();
                        });
                        
                        resolve(self);
                    });
                });
            });

            return(p);
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
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                // Bypass caching for plugin load
                self.$('.js-firefox').attr('href',
                    self.$('.js-firefox').attr('href') + '?' + Math.random()
                );

                // Highlight current page if any.
                self.$('a').each(function () {
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
                    self.$('.js-adminsupportonly').removeClass('hidden');
                }

                if (Iznik.Session.isAdmin()) {
                    self.$('.js-adminonly').removeClass('hidden');
                }

                // We need to create a hidden signin button because otherwise the Google logout method doesn't
                // work properly.  See http://stackoverflow.com/questions/19353034/how-to-sign-out-using-when-using-google-sign-in/19356354#19356354
                var GoogleLoad = new Iznik.Views.GoogleLoad();
                GoogleLoad.buttonShim('googleshim');
            });

            return p;
        }
    });

    Iznik.Views.Supporters = Iznik.View.extend({
        className: "panel panel-default js-supporters",

        template: "layout_supporters",

        render: function () {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function (self) {
                $.ajax({
                    url: API + 'supporters',
                    success: function (ret) {
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
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Pages.Supporters = Iznik.Views.Page.extend({
        modtools: true,

        template: "supporters",

        render: function () {
            var self = this;

            Iznik.Views.Page.prototype.render.call(this, {
                noSupporters: true
            }).then(function() {
                $.ajax({
                    url: API + 'supporters',
                    success: function (ret) {
                        var html = '';

                        function add(el, index, list) {
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
            });
        }
    });

    Iznik.Views.Page.Footer = Iznik.View.extend({
        id: 'footer',
        tagName: 'footer',
        className: 'footer',
        template: 'footer'
    })
});