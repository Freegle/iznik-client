define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/chat/chat',
    'iznik/models/notification',
    'iznik/events'
], function($, _, Backbone, Iznik, ChatHolder, monitor) {
    // We have a view for everything that is common across all pages, e.g. sidebars.
    var currentPage = null;

    var inout = true;   // TEST-JUN-17

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
                    Storage.remove('session');
                } catch (e) {
                }
                // Force reload of window to clear any data.
                // Remove for MODTOOLS: Router.mobileReload('/'); // CC
            }
        });

        // MODTOOLS only
        var logoutYahooUrl = 'https://login.yahoo.com/config/login?logout=1';
        //var logoutYahooUrl = 'https://uk.yahoo.com/';
        var authWindow = cordova.InAppBrowser.open(logoutYahooUrl, '_blank', 'location=yes,menubar=yes');
        $(authWindow).on('loadstart', function (e) {
        });
        $(authWindow).on('loadstop', function (e) {
            authWindow.close();
        });
        $(authWindow).on('exit', function (e) {
            //alert("InApp logout");
            Router.mobileReload('/'); // CC
        });

        //console.log('Yahoo logout start');
        //$.ajax({    // CC
        //    url: logoutYahooUrl,
        //    success: function (ret) { console.log('Yahoo logout OK'); },
        //    error: function (ret) { console.log('Yahoo logout error'); },
        //});
    };

    Iznik.Views.Page = Iznik.View.extend({
        modtools: false,
        
        footer: false,

        home: function () {
            var homeurl = this.modtools ? '/modtools' : '/';

            if (window.location.pathname == homeurl) {
                // Reload - this is because clicking on this when we're already on it can mean that something's 
                // broken and they're confused.
                Router.mobileReload('/'); // CC
            } else {
                Router.navigate(homeurl, true);
            }
        },

        setTitle: function(title) {
            // This sets new info in the tags used by search engines.
            window.document.title = title;
            $('title').remove();
            $('head').append('<title>' + title + '</title>');
            $('meta[itemprop=title]').remove();
            $('head').append('<meta itemprop="title" content="' + title + '">');
            $("meta[property='og:title']").remove();
            $('head').append('<meta property="og:title" content="' + title + '">');
        },

        setDescription: function(desc) {
            $('meta[name=description]').remove();
            $('head').append( '<meta name="description" content="' + desc + '">');
            $('meta[itemprop=description]').remove();
            $('head').append( '<meta itemprop="description" content="' + desc + '">');
            $("meta[property='og:description']").remove();
            $('head').append( '<meta property="og:description" content="' + desc + '">');
        },

        getTitle: function() {
            var self = this;

            $('.js-pagetitle').each(function() {
                if ($(this).length > 0) {
                    self.setTitle($(this).get(0).textContent);
                }
            });

            $('.js-pagedescription').each(function() {
                if ($(this).length > 0 && $(this).css('display') != 'none') {
                    self.setDescription($(this).get(0).textContent);
                }
            });
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

        notificationCheck: function() {
            var self = this;

            $.ajax({
                url: API + 'notification?count=true',
                type: 'GET',
                context: self,
                success: function(ret) {
                    if (ret.ret == 0) {
                        var el = $('.js-notifholder .js-notifcount');
                        if (el.html() != ret.count) {
                            el.html(ret.count);

                            if (ret.count) {
                                $('.js-notifholder .js-notifcount').show();
                            }
                            else {
                                $('.js-notifholder .js-notifcount').hide();
                            }

                            setTitleCounts(null, ret.count);
                        }

                        setTitleCounts(null, ret.count);
                    }
                }, complete: function() {
                    $.ajax({
                        url: API + 'newsfeed?count=true',
                        type: 'GET',
                        context: self,
                        success: function(ret) {
                            if (ret.ret == 0) {
                                var el = $('.js-unseennews');
                                if (el.html() != ret.unseencount) {
                                    el.html(ret.unseencount);

                                    if (ret.unseencount) {
                                        $('.js-unseennews').show();
                                    }
                                    else {
                                        $('.js-unseennews').hide();
                                    }
                                }
                            }
                        }, complete: function() {
                            _.delay(_.bind(this.notificationCheck, this), 30000);
                        }
                    });
                }
            });
        },

        render: function (options) {
            var self = this;

            // Start event tracking.
            if (monitor) {
                // CC monitor.start();
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
                    if (self.title && self.title.length > 0) {
                        self.setTitle(self.title);
                    }

                    $('#bodyContent').html(window.template(tpl));
                    $('.js-pageContent').html(self.$el);

                    if (!useSwipeRefresh) { $('#refreshbutton').show(); }  // CC
                    showNetworkStatus(); // CC
                    if (self.appButtons) {
                        // Show app buttons.
                        // CC $('#js-appbuttons').show();
                    }

                    $('#botleft').empty();

                    if (self.modtools) {
                        // ModTools menu and sidebar.
                        new Iznik.Views.ModTools.LeftMenu().render().then(function(m) {
                            $('.js-leftsidebar').html(m.el);
                        });

                        rightaccordion = $('#rightaccordion');

                        if (!rightbar) {
                            require(['iznik/accordionpersist', 'iznik/views/plugin'], function() {
                                window.IznikPlugin = new Iznik.Views.Plugin.Main();
                                IznikPlugin.render().then(function(v) {
                                    rightaccordion.append(v.el);
                                })
                                rightaccordion.accordionPersist();
                            });
                        } else {
                            rightaccordion.empty().append(rightbar);
                        }

                        if (options.noSupporters) {
                            $('.js-supporters').hide();
                        } else {
                            $('.js-supporters').show();
                        }

                        // Status LED
                        if (Iznik.Session.isAdminOrSupport()) {
                            var v = new Iznik.Views.Status();
                            v.render().then(function() {
                                $('#js-status').html(v.el);
                            });
                        }
                    } else {
                        if (!self.footer) {
                            // Put bottom left links in.
                            var v = new Iznik.Views.User.Botleft();
                            v.render();
                            $('#botleft').append(v.$el);
                        }

                        var v = new Iznik.Views.User.Social();
                        v.render();
                        $('#botleft').append(v.$el);

                        // Highlight current page if any.
                        var mobilePath =  mobile_pathname(); // CC
                        $('#navbar-collapse a').each(function () {
                            var href = $(this).attr('href');
                            $(this).closest('li').removeClass('active');

                            if (href == mobilePath) {   // CC
                                $(this).closest('li').addClass('active');

                                // Force reload on click, which doesn't happen by default.
                                $(this).click(function () {
                                    Router.mobileReload();  // CC
                                });
                            }
                        });
                    }

                    if (self.options.naked) {
                        // We don't want the page framing.   This is used for plugins.
                        $('.navbar, .js-leftsidebar, .js-rightsidebar').hide();
                        $('.margtopbig').removeClass('margtopbig');
                        $('#botleft').hide();
                    }

                    // Put self page in.  Need to know whether we're logged in first, in order to start the
                    // chats, which some pages may rely on being active.
                    self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                        if (loggedIn) {
                            var me = Iznik.Session.get('me');

                            if (!self.noEmailOk && !me.email) {
                                // We have no email.  This can happen for some login types.  Force them to provide one.
                                require(["iznik/views/pages/user/settings"], function() {
                                    var v = new Iznik.Views.User.Pages.Settings.NoEmail();
                                    v.render();
                                });
                            } else {
                                // Since we're logged in, we can start chat.
                                ChatHolder({
                                    modtools: self.modtools
                                }).render();
                            }

                            // Invitation count.
                            if (me.invitesleft > 0) {
                                $('.js-invitesleft').html(me.invitesleft).show();
                            } else {
                                $('.js-invitesleft').html('').show();
                            }
                        }

                        if ($('.js-notiflist').length) {
                            // Notifications count and dropdown.
                            self.notificationCheck();
                            self.notifications = new Iznik.Collections.Notification();

                            self.notificationsCV1 = new Backbone.CollectionView({
                                el: $('.js-notiflist1'),
                                modelView: Iznik.Views.Notification,
                                collection: self.notifications,
                                processKeyEvents: false
                            });

                            self.notificationsCV2 = new Backbone.CollectionView({
                                el: $('.js-notiflist2'),
                                modelView: Iznik.Views.Notification,
                                collection: self.notifications,
                                processKeyEvents: false
                            });

                            self.notificationsCV1.render();
                            self.notificationsCV2.render();
                            self.notifications.fetch();

                            $(".js-notifholder").click(_.bind(function (e) {
                                var self = this;
                                // Fetch the notifications, which the CV will then render.
                                console.log("Clicked on notifications");
                                self.notifications.fetch().then(function() {
                                    console.log("Notifications", self.notifications);
                                });
                            }, self));

                            $(".js-markallnotifread").click(function (e) {
                                e.preventDefault();
                                e.stopPropagation();

                                self.notifications.each(function(notif) {
                                    if (!notif.get('seen')) {
                                        notif.seen();
                                    }
                                });

                                $('.js-notifholder .js-notifcount').hide();
                            });
                        }

                        templateFetch(self.template).then(function(tpl) {
                            if (self.model) {
                                self.$el.html(window.template(tpl)(self.model.toJSON2()));
                            } else {
                                // Default is that we pass the session as the model.
                                self.$el.html(window.template(tpl)(Iznik.Session.toJSON2()));
                            }

                            $('.js-pageContent').html(self.$el);

                            // Pick up any title and description.
                            // TODO Hacky delay
                            _.delay(_.bind(self.getTitle, self), 5000);

                            $('#footer').remove();

                            if (self.footer) {
                                var v = new Iznik.Views.Page.Footer();
                                v.render().then(function() {
                                    $('body').addClass('Site');
                                    $('body').append(v.$el);
                                });
                            }

                            // CC show debug sunglasses/specs icon
                            if (showDebugConsole) {
                                $('#mobile-debug').show();
                            }

                            // Show anything which should or shouldn't be visible based on login status.
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
                            } else {
                                loggedOutOnly.removeClass('reallyHide');
                                loggedInOnly.addClass('reallyHide');
                            }

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

                            // Now that we're in the DOM, ensure events work.
                            self.delegateEvents();

                            resolve(self);
                        });
                    });

                    Iznik.Session.testLoggedIn();
                });
            });

            return(p);
        }
    });

    Iznik.Views.LocalStorage = Iznik.Views.Page.extend({
        template: "localstorage"
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
                var mobilePath =  mobile_pathname(); // CC
                self.$('a').each(function () {
                    var href = $(this).attr('href');
                    $(this).closest('li').removeClass('active');

                    if (href == mobilePath) {   // CC
                        $(this).closest('li').addClass('active');

                        // Force reload on click, which doesn't happen by default.
                        $(this).click(function () {
                            Router.mobileReload();  // CC
                        });
                    }
                });
                $('#js-mobilelog').html(alllog);

                if (Iznik.Session.isAdminOrSupport()) {
                    self.$('.js-adminsupportonly').removeClass('hidden');
                }

                if (Iznik.Session.hasPermission('Newsletter')) {
                    self.$('.js-newsletter').removeClass('hidden');
                }

                if (Iznik.Session.hasP)

                // We need to create a hidden signin button because otherwise the Google logout method doesn't
                // work properly.  See http://stackoverflow.com/questions/19353034/how-to-sign-out-using-when-using-google-sign-in/19356354#19356354
                var GoogleLoad = new Iznik.Views.GoogleLoad();
                if (GoogleLoad) {
                    GoogleLoad.buttonShim('googleshim');
                }

                // Events site is special.
                var eventsite = $('meta[name=iznikevent]').attr("content");
                self.$('.js-recentsessions').attr('href', 'https://' + eventsite + '/modtools/sessions');
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
    });

    Iznik.Views.User.Botleft = Iznik.View.extend({
        className: 'padleft hidden-sm hidden-xs',
        template: 'user_botleft'
    });
    
    Iznik.Views.User.Social = Iznik.View.extend({
        id: 'social',
        className: 'padleft hidden-sm hidden-xs',
        template: 'user_social'
    });

    Iznik.Views.Status = Iznik.View.extend({
        template: 'status',

        update: function() {
            var self = this;
            console.log("Status update", self);

            $.ajax({
                url: API + 'status',
                type: 'GET',
                context: self,
                success: function(ret) {
                    var self = this;

                    self.$('.js-statuserror').hide();
                    self.$('.js-statuswarning').hide();
                    self.$('.js-statusok').hide();

                    if (ret.ret === 0) {
                        if (ret.error) {
                            self.$('.js-statuserror').show();
                        } else if (ret.warning) {
                            self.$('.js-statuswarning').show();
                        } else {
                            self.$('.js-statusok').show();
                        }
                    } else {
                        self.$('.js-statuserror').show();
                    }
                }, complete: function() {
                    _.delay(_.bind(self.update, self), 60000);
                }
            });
        },

        render: function() {
            var self = this;
            var p = Iznik.View.prototype.render.call(this);
            p.then(function() {
                _.delay(_.bind(self.update, self), 10000);
            });

            return(p);
        }
    });

    Iznik.Views.Notification = Iznik.View.Timeago.extend({
        tagName: 'li',

        className: 'notification',

        template: 'user_newsfeed_notification',

        events: {
            'mouseover': 'markSeen',
            'click .js-top': 'goto'
        },

        goto: function() {
            var self = this;

            var newsfeed = self.model.get('newsfeed');
            var url = self.model.get('url');
            if (newsfeed) {
                if (!self.model.get('seen')) {
                    self.model.seen();
                    Router.navigate('/newsfeed/' + newsfeed.id, true);
                } else {
                    Router.navigate('/newsfeed/' + newsfeed.id, true);
                }
            } else if (url) {
                Router.navigate(url, true);
            }
        },

        markSeen: function() {
            var self = this;

            if (!self.model.get('seen')) {
                self.model.seen().then(function() {
                    self.render();
                });
            }
        },

        render: function() {
            var self = this;
            var p = resolvedPromise(self);

            if (!self.rendered) {
                self.rendered = true;
                var newsfeed = self.model.get('newsfeed');

                if (newsfeed) {
                    if (newsfeed.message) {
                        newsfeed.message = twem(newsfeed.message);
                    }

                    var replyto = newsfeed.replyto;

                    if (replyto && replyto.message) {
                        newsfeed.replyto.message = twem(replyto.message);
                    }

                    self.model.set('newsfeed', newsfeed);
                }

                p = Iznik.View.Timeago.prototype.render.call(this);

                p.then(function(){
                    if (self.$('.js-emoji').length) {
                        var el = self.$('.js-emoji').get()[0];
                        twemoji.parse(el);
                    }
                });
            }

            return(p)
        }
    })
});