define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/events',
    'iznik/models/session',
    'iznik/views/modal',
    'iznik/views/help',
    'iznik/views/signinup'
], function($, _, Backbone, Iznik, monitor) {
    Iznik.Session = new Iznik.Models.Session();

    Iznik.Session.askedPush = false;

    var IznikRouter = Backbone.Router.extend({
        initialize: function () {
            var self = this;

            // We want the ability to abort all outstanding requests, for example when we switch to a new route.
            self.xhrPool = [];
            self.abortAll = function() {
                _.each(self.xhrPool, function(jqXHR) {
                    try {
                        jqXHR.abort();
                    } catch (e) {}
                });

                self.xhrPool = [];
            };

            $.ajaxSetup({
                beforeSend: function(jqXHR) {
                    self.xhrPool.push(jqXHR);
                },
                complete: function(jqXHR) {
                    var index = $.inArray(jqXHR, self.xhrPool);
                    if (index > -1) {
                        self.xhrPool.splice(index, 1);
                    }
                }
            });

            // Any pages with trailing slashes should route the same as ones without.
            this.route(/(.*)\/+$/, "trailFix", function (id) {
                this.navigate(id, true);
            });

            this.bind('route', this.pageView);
        },

        pageView: function () {
            var url = Backbone.history.getFragment();

            if (!/^\//.test(url) && url != "") {
                url = "/" + url;
            }

            // Make sure we have google analytics for Backbone routes.
            try {
                var ua = require('universal-analytics');
                var visitor = ua('UA-10627716-2');
                visitor.pageview(url).send();

                var timestamp = (new Date()).getTime();
                monitor.trackEvent('route', url, null, null, null, timestamp);
            } catch (e) {
                console.log("Google exception - privacy blocker?", e);
            }
        },

        routes: {
            // TODO Legacy routes - hopefully we can retire these at some point.
            "tryfd.php?groupid=:id": "userExploreGroup",
            "m.php?a=se(&g=:id)": "legacyUserCommunityEvents",
            "events(/:id)": "legacyUserCommunityEvents",
            "mygroups/:id/message/:id": "legacyUserMessage",
            "explore/:id/message/:id": "legacyUserMessage",
            "groups": "legacyUserGroups",
            "location/:id": "legacyUserGroups",
            "index.php?action=home": "modtools",
            "index.php?action=pending": "pendingMessages",
            "main.php?action=look&groupid=:id": "userExploreGroup",
            "main.php?action=showevents*t": "userCommunityEvents",
            "main.php?&action=join&then=displaygroup&groupid=:id": "userExploreGroup",
            "main.php?action=mygroups": "userMyGroups",
            "main.php?action=myposts": "userHome",
            "main.php?action=post*t": "userHome",
            "main.php?action=findgroup": "userExplore",
            "login.php?action=mygroups&subaction=displaypost&msgid=:id&groupid=:id*": "legacyUserMessage2",
            "legacy?action=join&groupid=:id&then=displaygroup": "userExploreGroup",
            "legacy?action=look&groupid=:id": "userExploreGroup",
            "legacy?action=mygroups*t": "userMyGroups",
            "legacy?action=myposts": "userHome",
            "legacy?action=mysettings": "userSettings",
            "legacy?action=post*t": "userHome",
            "legacy?action=showevents*t": "userCommunityEvents",
            "legacy?a=se&g=:id": "legacyUserCommunityEvents",
            "post": "userHome",
            // End legacy

            "localstorage": "localstorage",
            "yahoologin": "yahoologin",
            "modtools/chat/:id": "modChat",
            "modtools/chats": "modChats",
            "modtools/logs(/:type)": "modLogs",
            "modtools/supporters": "supporters",
            "modtools/messages/pending": "pendingMessages",
            "modtools/messages/approved/messagesearch/:search": "approvedMessagesSearchMessages",
            "modtools/messages/approved/membersearch/:search": "approvedMessagesSearchMembers",
            "modtools/messages/approved": "approvedMessages",
            "modtools/messages/spam": "spamMessages",
            "modtools/members/pending(/:search)": "pendingMembers",
            "modtools/members/approved(/:search)": "approvedMembers",
            "modtools/members/spam": "spamMembers",
            "modtools/members/happiness": "happinessMembers",
            "modtools/members/stories": "storiesMembers",
            "modtools/members/newsletter": "storiesNewsletter",
            "modtools/events/pending": "pendingEvents",
            "modtools/volunteering/pending": "pendingVolunteering",
            "modtools/publicity": "socialActions",
            "modtools/fbgroups": "facebookGroups",
            "modtools/admins": "admins",
            "modtools/conversations/spam": "chatReview",
            "modtools/conversations/reported": "chatReport",
            "modtools/spammerlist/pendingadd(/:search)": "spammerListPendingAdd",
            "modtools/spammerlist/confirmed(/:search)": "spammerListConfirmed",
            "modtools/spammerlist/pendingremove(/:search)": "spammerListPendingRemove",
            "modtools/spammerlist/whitelisted(/:search)": "spammerListWhitelisted",
            "modtools/settings/all/map": "mapAll",
            "modtools/settings/:id/map": "mapSettings",
            "modtools/settings/confirmmail/(:key)": "confirmMail",
            "modtools/settings": "settings",
            "modtools/support": "support",
            "modtools/sessions": "sessions",
            "modtools/replay/(:id)": "replay",
            "modtools": "modtools",
            "replay/(:id)": "replay",
            "find": "userFindWhereAmI",
            "find/whereami": "userFindWhereAmI",
            "find/search/(:search)": "userSearched",
            "find/search": "userSearch",
            "find/whatnext": "userFindWhatNext",
            "find/whatisit": "userFindWhatIsIt",
            "find/whoami": "userFindWhoAmI",
            "give": "userGiveWhereAmI",
            "give/whereami": "userGiveWhereAmI",
            "give/whatisit": "userGiveWhatIsIt",
            "give/whoami": "userGiveWhoAmI",
            "give/whatnext": "userGiveWhatNext",
            "edit/:id": "userEdit",
            "m/:id": "userMessage",
            "message/:id": "userMessage",
            "mygroups": "userMyGroups",
            "settings/confirmmail/(:key)": "userConfirmMail",
            "settings": "userSettings",
            "explore/region/:id": "userExploreRegion",
            "explore/:id/join": "userJoinGroup",
            "explore/:id": "userExploreGroup",
            "explore": "userExplore",
            "livemap": "liveMap",
            "helpus/aviva2017": "userAviva",
            "aviva": "userAviva",
            "ebay": "userStatsEbay",
            "stats/ebay": "userStatsEbay",
            "stats/eBay": "userStatsEbay",
            "stats/heatmap": "userStatsHeatMap",
            "stats/region/:id": "userStatsRegion",
            "stats/authorities": "userStatsAuthorities",
            "stats/authority/:id": "userStatsAuthority",
            "stats(/:id)": "userStatsGroup",
            "communityevents(/:id)": "userCommunityEvents",
            "communityevent(/:id)": "userCommunityEvent",
            "newuser": "newUser",
            "unsubscribe(/:id)": "unsubscribe",
            "chats": "userChats",
            "chat/:id/external(/:id)": "userChatExternal",
            "chat/:id": "userChat",
            "alert/viewed/:id": "alertViewed",
            "mobile": "userMobile",
            "mobile/": "userMobile",
            "about": "userAbout",
            "terms": "userTerms",
            "handbook": "userHandbook",
            "privacy": "userPrivacy",
            "disclaimer": "userDisclaimer",
            "donate": "userDonate",
            "contact": "userContact",
            "help": "userContact",
            "invite/:id": "userInvited",
            "invite": "userInvite",
            "newsfeed/:id": "userNewsfeedSingle",
            "newsfeed": "userNewsfeed",
            "plugins/events/:id": "communityEventsPlugin",
            "plugins/group?groupid=:id(&*t)": "groupPlugin",
            "plugins/group/:id": "groupPlugin",
            "mypost/:id/:id": "userMyPostAction",
            "mypost/:id": "userMyPost",
            "schedule(/:id)": "userSchedule",
            "stories/fornewsletter": "userNewsletterReview",
            "stories": "userStories",
            "story/:id": "userStory",
            "streetwhack(/:id)": "findMyStreet",
            "volunteering": "userVolunteerings",
            "volunteering/group/(/:id)": "userVolunteerings",
            "volunteering/:id": "userVolunteering",
            "why": "userWhy",
            "myposts": "userHome",
            "*path": "userDefault"
        },

        loadRoute: function (routeOptions) {
            var self = this;

            // We're no longer interested in any outstanding requests, and we also want to avoid them clogging up
            // our per-host limit.
            self.abortAll();

            // Tidy any modal grey.
            $('.modal-backdrop').remove();

            // The top button might be showing.
            $('.js-scrolltop').addClass('hidden');

            //console.log("loadRoute"); console.log(routeOptions);
            routeOptions = routeOptions || {};

            self.modtools = routeOptions.modtools;
            Iznik.Session.set('modtools', self.modtools);

            function loadPage() {
                // Hide the page loader, which might still be there.
                $('#pageloader').remove();
                $('body').css('height', '');

                routeOptions.page.render();
            }
             
            loadPage();
        },

        localstorage: function () {
            var self = this;
            require(["iznik/views/pages/pages"], function() {
                var page = new Iznik.Views.LocalStorage();
                self.loadRoute({page: page});
            });
        },

        userHome: function () {
            var self = this;

            if (document.URL.indexOf('modtools') !== -1) {
                Router.navigate('/modtools', true);
            } else {
                function f(loggedIn) {
                    // console.log("Logged in", loggedIn);
                    if (loggedIn || _.isUndefined(loggedIn)) {
                        require(["iznik/views/pages/user/home"], function() {
                            var page = new Iznik.Views.User.Pages.Home();
                            self.loadRoute({page: page});
                        });
                    } else {
                        require(["iznik/views/pages/user/landing"], function() {
                            console.log("Load landing");
                            var page = new Iznik.Views.User.Pages.Landing();
                            self.loadRoute({page: page});
                        });
                    }
                }

                self.listenToOnce(Iznik.Session, 'isLoggedIn', f);
                Iznik.Session.testLoggedIn();
            }
        },

        userMyPostAction: function(msgid, action) {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function () {
                require(["iznik/views/pages/user/home"], function() {
                    var page = new Iznik.Views.User.Pages.MyPost({
                        id: msgid,
                        action: action
                    });
                    self.loadRoute({page: page});
                });
            });

            Iznik.Session.forceLogin();
        },

        userMyPost: function(msgid) {
            var self = this;
            
            self.listenToOnce(Iznik.Session, 'loggedIn', function () {
                require(["iznik/views/pages/user/home"], function() {
                    var page = new Iznik.Views.User.Pages.MyPost({
                        id: msgid
                    });
                    self.loadRoute({page: page});
                });
            });

            Iznik.Session.forceLogin();
        },

        userSchedule: function(id) {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function () {
                require(["iznik/views/pages/user/schedule"], function() {
                    var page = new Iznik.Views.User.Pages.Schedule({
                        id: id
                    });
                    self.loadRoute({page: page});
                });
            });

            Iznik.Session.forceLogin();
        },

        userNewsletterReview: function() {
            var self = this;

            require(["iznik/views/pages/user/stories"], function() {
                var page = new Iznik.Views.User.Pages.Stories({
                    reviewnewsletter: true
                });
                self.loadRoute({page: page});
            });
        },

        userDefault: function() {
            var self = this;

            function f(loggedIn) {
                // console.log("Logged in", loggedIn);
                if (loggedIn || _.isUndefined(loggedIn)) {
                    // Load the last of the main pages that they had open.
                    var page = Storage.get('lasthomepage');

                    switch (page) {
                        case 'news': {
                            self.userNewsfeed();
                            break;
                        }
                        case 'myposts': {
                            self.userHome();
                            break;
                        }
                        case 'mygroups': {
                            self.userMyGroups();
                            break;
                        }
                        default: {
                            self.userNewsfeed();
                            break;
                        }
                    }
                } else {
                    require(["iznik/views/pages/user/landing"], function() {
                        console.log("Load landing");
                        var page = new Iznik.Views.User.Pages.Landing();
                        self.loadRoute({page: page});
                    });
                }
            }

            self.listenToOnce(Iznik.Session, 'isLoggedIn', f);
            Iznik.Session.testLoggedIn();

        },

        userStories: function() {
            var self = this;

            require(["iznik/views/pages/user/stories"], function() {
                var page = new Iznik.Views.User.Pages.Stories();
                self.loadRoute({page: page});
            });
        },

        userStory: function(id) {
            var self = this;

            require(["iznik/views/pages/user/stories"], function() {
                var page = new Iznik.Views.User.Pages.Stories.Single({
                    id: id
                });
                self.loadRoute({page: page});
            });
        },

        userChats: function() {
            var self = this;
            require(["iznik/views/pages/chat"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.Chat.Page();
                    self.loadRoute({page: page});
                });

                Iznik.Session.forceLogin();
            });
        },

        userChat: function(chatid) {
            var self = this;
            require(["iznik/views/pages/chat"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.Chat.Page({
                        chatid: chatid
                    });
                    self.loadRoute({page: page});
                });

                Iznik.Session.forceLogin();
            });
        },

        userChatExternal: function(chatid, msgid) {
            var self = this;
            require(["iznik/views/pages/chat"], function() {
                var page = new Iznik.Views.Chat.External({
                    chatid: chatid,
                    msgid: msgid
                });
                self.loadRoute({page: page});
            });
        },

        userFindWhereAmI: function () {
            var self = this;

            require(["iznik/views/pages/user/find"], function() {
                var page = new Iznik.Views.User.Pages.Find.WhereAmI();
                self.loadRoute({page: page});
            });
        },

        userSearch: function () {
            var self = this;

            require(["iznik/views/pages/user/find"], function() {
                var page = new Iznik.Views.User.Pages.Find.Search({
                    browse: true
                });
                self.loadRoute({page: page});
            });
        },

        userSearched: function (query) {
            var self = this;

            require(["iznik/views/pages/user/find"], function() {
                var page = new Iznik.Views.User.Pages.Find.Search({
                    search: query
                });

                try {
                    Storage.set('lastsearch', query);
                } catch (e) {}

                self.loadRoute({page: page});
            });
        },

        userGiveWhereAmI: function () {
            var self = this;

            require(["iznik/views/pages/user/give"], function() {
                var page = new Iznik.Views.User.Pages.Give.WhereAmI();
                self.loadRoute({page: page});
            });
        },

        userGiveWhatIsIt: function () {
            var self = this;

            require(["iznik/views/pages/user/give"], function() {
                var page = new Iznik.Views.User.Pages.Give.WhatIsIt();
                self.loadRoute({page: page});
            });
        },

        userGiveWhoAmI: function () {
            var self = this;

            require(["iznik/views/pages/user/give"], function() {
                var page = new Iznik.Views.User.Pages.Give.WhoAmI();
                self.loadRoute({page: page});
            });
        },

        userFindWhatIsIt: function() {
            var self = this;

            require(["iznik/views/pages/user/find"], function() {
                var page = new Iznik.Views.User.Pages.Find.WhatIsIt();
                self.loadRoute({page: page});
            });
        },

        userFindWhoAmI: function () {
            var self = this;

            require(["iznik/views/pages/user/find"], function() {
                var page = new Iznik.Views.User.Pages.Find.WhoAmI();
                self.loadRoute({page: page});
            });
        },

        userGiveWhatNext: function () {
            var self = this;

            require(["iznik/views/pages/user/give"], function() {
                var page = new Iznik.Views.User.Pages.Give.WhatNext();
                self.loadRoute({page: page});
            });
        },

        userFindWhatNext: function () {
            var self = this;

            require(["iznik/views/pages/user/find"], function() {
                var page = new Iznik.Views.User.Pages.Find.WhatNext();
                self.loadRoute({page: page});
            });
        },

        userMyGroups: function () {
            var self = this;

            require(["iznik/views/pages/user/mygroups"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.User.Pages.MyGroups();
                    self.loadRoute({page: page});
                });

                Iznik.Session.forceLogin();
            });
        },

        userConfirmMail: function (key) {
            var self = this;

            require(["iznik/views/pages/user/settings"], function() {
                $.ajax({
                    type: 'PATCH',
                    url: API + 'session',
                    data: {
                        key: key
                    },
                    success: function (ret) {
                        var v;

                        if (ret.ret == 0) {
                            v = new Iznik.Views.User.Pages.Settings.VerifySucceeded();
                        } else {
                            v = new Iznik.Views.User.Pages.Settings.VerifyFailed();
                        }
                        self.listenToOnce(v, 'modalCancelled modalClosed', function () {
                            // Reload to force session refresh.
                            // TODO lame.
                            window.location = '/';
                        });

                        v.render();
                    },
                    error: function () {
                        var v = new Iznik.Views.User.Pages.Settings.VerifyFailed();
                        self.listenToOnce(v, 'modalCancelled modalClosed', function () {
                            Router.navigate('/settings', true)
                        });

                        v.render();
                    }
                });
            });
        },

        userSettings: function () {
            var self = this;

            require(["iznik/views/pages/user/settings"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.User.Pages.Settings();
                    self.loadRoute({page: page});
                });

                Iznik.Session.forceLogin();
            });
        },

        userJoinGroup: function(id) {
            var self = this;

            require(["iznik/views/pages/user/explore"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.User.Pages.ExploreGroup({
                        id: id,
                        join: true
                    });
                    self.loadRoute({page: page});
                });

                Iznik.Session.forceLogin();
            });
        },

        legacyUserGroups: function(loc) {
            var self = this;

            require(["iznik/views/pages/user/explore"], function() {
                // Legacy route.  If we have a name, we need to search.
                if (loc) {
                    // This is the route for /location/loc
                    var page = new Iznik.Views.User.Pages.Explore({
                        search: loc
                    });
                    self.loadRoute({page: page});
                } else {
                    // This is the route for /groups or /groups#loc.
                    var hash = Backbone.history.getHash();

                    if (hash) {
                        var page = new Iznik.Views.User.Pages.Explore({
                            search: hash
                        });
                        self.loadRoute({page: page});
                    } else {
                        Router.navigate('/explore', true);
                    }
                }
            });
        },

        userInvite: function() {
            var self = this;

            require(["iznik/views/pages/user/invite"], function() {
                var page = new Iznik.Views.User.Pages.Invite();
                self.loadRoute({page: page});
            });
        },

        userNewsfeed: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                require(["iznik/views/pages/user/newsfeed"], function() {
                    var page = new Iznik.Views.User.Pages.Newsfeed();
                    self.loadRoute({page: page});
                });
            });

            Iznik.Session.forceLogin();
        },

        userNewsfeedSingle: function(id) {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                require(["iznik/views/pages/user/newsfeed"], function() {
                    var page = new Iznik.Views.User.Pages.Newsfeed.Single({
                        id: id
                    });
                    self.loadRoute({page: page});
                });
            });

            Iznik.Session.forceLogin();
        },

        userInvited: function(id) {
            // Record result of invitation.
            var self = this;
            $.ajax({
                url: API + 'invitation',
                type: 'PATCH',
                data: {
                    id: id,
                    outcome: 'Accepted'
                }, complete: function() {
                    self.userHome();
                }
            })
        },

        userExploreGroup: function(id, naked) {
            var self = this;

            require(["iznik/views/pages/user/explore"], function() {
                var page = new Iznik.Views.User.Pages.ExploreGroup({
                    id: id,
                    naked: naked
                });
                self.loadRoute({page: page});
            });
        },

        userExploreRegion: function(region) {
            var self = this;

            require(["iznik/views/pages/user/explore"], function() {
                var page = new Iznik.Views.User.Pages.Explore({
                    region: region
                });
                self.loadRoute({page: page});
            });
        },

        userExplore: function() {
            var self = this;

            require(["iznik/views/pages/user/explore"], function() {
                var page = new Iznik.Views.User.Pages.Explore();
                self.loadRoute({page: page});
            });
        },

        liveMap: function() {
            var self = this;

            require(["iznik/views/pages/user/livemap"], function() {
                var page = new Iznik.Views.User.Pages.LiveMap();
                self.loadRoute({page: page});
            });
        },

        userStatsHeatMap: function(area) {
            var self = this;

            require(["iznik/views/pages/user/stats"], function() {
                var page = new Iznik.Views.User.Pages.Heatmap();
                self.loadRoute({page: page});
            });
        },

        userAviva: function(area) {
            var self = this;

            require(["iznik/views/pages/pages"], function() {
                require(["iznik/views/supportus"], function() {
                    var page = new Iznik.Views.Aviva();
                    self.loadRoute({page: page});
                });
            });
        },

        userStatsEbay: function(area) {
            var self = this;

            require(["iznik/views/pages/user/stats"], function() {
                var page = new Iznik.Views.User.Pages.Ebay();
                self.loadRoute({page: page});
            });
        },

        userStatsRegion: function(region) {
            var self = this;

            require(["iznik/views/pages/user/stats"], function() {
                var page = new Iznik.Views.User.Pages.StatsGroup({
                    region: region
                });
                self.loadRoute({page: page});
            });
        },

        userStatsAuthorities: function() {
            var self = this;

            require(["iznik/views/pages/user/stats"], function() {
                var page = new Iznik.Views.User.Pages.Authorities();
                self.loadRoute({page: page});
            });
        },

        userStatsAuthority: function(authorityid) {
            var self = this;

            require(["iznik/views/pages/user/stats"], function() {
                var page = new Iznik.Views.User.Pages.StatsAuthority({
                    id: authorityid
                });
                self.loadRoute({page: page});
            });
        },

        userStatsGroup: function(id) {
            var self = this;

            require(["iznik/views/pages/user/stats"], function() {
                var page = new Iznik.Views.User.Pages.StatsGroup({
                    id: id
                });
                self.loadRoute({page: page});
            });
        },

        legacyUserCommunityEvents: function(legacyid) {
            var self = this;

            require(["iznik/models/group"], function() {
                // Map the legacy id to a real id.
                var group = new Iznik.Models.Group({
                    id: legacyid
                });

                group.fetch().then(function () {
                    self.userCommunityEvents(group.get('id'));
                })
            });
        },

        userCommunityEvents: function(groupid, naked) {
            var self = this;

            // We might be called in the legacy case with some random guff on the end of the url.
            if (groupid && typeof groupid == 'string') {
                groupid = groupid.substr(0,1) == '&' ? null : parseInt(groupid);
            }

            require(["iznik/views/pages/user/communityevents"], function() {
                var page = new Iznik.Views.User.Pages.CommunityEvents({
                    groupid: groupid,
                    naked: naked
                });

                if (groupid) {
                    // We can see events for a specific group when we're logged out.
                    self.loadRoute({page: page});
                } else {
                    // But for all groups, we need to log in.
                    self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                        self.loadRoute({page: page});
                    });

                    Iznik.Session.forceLogin();
                }
            });
        },

        userCommunityEvent: function(id) {
            var self = this;
            require(["iznik/views/pages/user/communityevents"], function() {
                var page = new Iznik.Views.User.Pages.CommunityEvent({
                    id: id
                });
                self.loadRoute({page: page});
            });
        },

        userVolunteerings: function(groupid, naked) {
            var self = this;

            require(["iznik/views/pages/user/volunteering"], function() {
                var page = new Iznik.Views.User.Pages.Volunteerings({
                    groupid: groupid,
                    naked: naked
                });

                if (groupid) {
                    // We can see volunteer vacancies for a specific group when we're logged out.
                    self.loadRoute({page: page});
                } else {
                    // But for all groups, we need to log in.
                    self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                        self.loadRoute({page: page});
                    });

                    Iznik.Session.forceLogin();
                }
            });
        },

        userVolunteering: function(id) {
            var self = this;
            require(["iznik/views/pages/user/volunteering"], function() {
                var page = new Iznik.Views.User.Pages.Volunteering({
                    id: id
                });
                self.loadRoute({page: page});
            });
        },

        legacyUserMessage: function(groupid, messageid) {
            var self = this;

            require(["iznik/views/pages/user/explore"], function() {
                var page = new Iznik.Views.User.Pages.LegacyMessage({
                    id: messageid,
                    groupid: groupid
                });
                self.loadRoute({page: page});
            });
        },

        legacyUserMessage2: function(messageid, groupid) {
            var self = this;

            require(["iznik/views/pages/user/explore"], function() {
                var page = new Iznik.Views.User.Pages.LegacyMessage({
                    id: messageid,
                    groupid: groupid
                });
                self.loadRoute({page: page});
            });
        },

        userMessage: function(id) {
            var self = this;

            require(["iznik/views/pages/user/explore"], function() {
                var page = new Iznik.Views.User.Pages.Message({
                    id: id
                });
                self.loadRoute({page: page});
            });
        },

        userEdit: function(id) {
            var self = this;

            // We convert the message back into a draft, and assuming that works, navigate to the appropriate
            // page.
            $.ajax({
                url: API + 'message',
                type: 'POST',
                data: {
                    id: id,
                    action: 'RejectToDraft'
                },
                success: function(ret) {
                    if (ret.ret === 0) {
                        try {
                            Storage.set('draft', id);

                            if (ret.messagetype == 'Offer') {
                                // Make them reconfirm the location
                                Router.navigate('/give/whereami', true);
                            } else {
                                // TODO Should we be able to change the location?
                                Router.navigate('/find/whatisit', true);
                            }
                        } catch (e) {}
                    }
                }
            })
        },

        unsubscribe: function () {
            var self = this;

            require(["iznik/views/pages/user/unsubscribe"], function() {
                var page = new Iznik.Views.User.Pages.Unsubscribe();
                self.loadRoute({page: page});
            });
        },

        newUser: function() {
            var self = this;

            require(["iznik/views/pages/user/new"], function() {
                var page = new Iznik.Views.User.Pages.New();
                self.loadRoute({page: page});
            });
        },

        getURLParam: function (name) {
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(location.search);
            return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
        },

        yahoologin: function (path) {
            var self = this;

            // We have been redirected here after an attempt to sign in with Yahoo.  We now try again to login
            // on the server.  This time we should succeed.
            var returnto = this.getURLParam('returnto');

            self.listenToOnce(Iznik.Session, 'yahoologincomplete', function (ret) {
                if (ret.ret == 0) {
                    if (returnto) {
                        window.location = returnto;
                    } else {
                        self.home.call(self);
                    }
                } else {
                    // TODO
                    window.location = '/';
                }
            });

            Iznik.Session.yahooLogin();
        },

        modtools: function () {
            var self = this;
            require(['iznik/views/dashboard', "iznik/views/pages/user/settings", "iznik/views/pages/modtools/landing"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.Landing();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        supporters: function () {
            var page = new Iznik.Views.ModTools.Pages.Supporters();
            this.loadRoute({page: page});
        },

        pendingMessages: function () {
            var self = this;

            require(["iznik/views/pages/modtools/messages_pending"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.PendingMessages();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        spamMessages: function () {
            var self = this;

            require(["iznik/views/pages/modtools/messages_spam"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.SpamMessages();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        approvedMessagesSearchMessages: function (search) {
            this.approvedMessages(search, null);
        },

        approvedMessagesSearchMembers: function (search) {
            this.approvedMessages(null, search);
        },

        approvedMessages: function (searchmess, searchmemb) {
            var self = this;

            require(["iznik/views/pages/modtools/messages_approved"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.ApprovedMessages({
                        searchmess: searchmess,
                        searchmemb: searchmemb
                    });
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        pendingMembers: function (search) {
            var self = this;

            require(["iznik/views/pages/modtools/members_pending", "iznik/views/pages/modtools/messages_pending"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.PendingMembers({
                        search: search
                    });
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        approvedMembers: function (search) {
            var self = this;

            require(["iznik/views/pages/modtools/members_approved"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.ApprovedMembers({
                        search: search
                    });
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        pendingEvents: function (search) {
            var self = this;

            require(["iznik/views/pages/modtools/events_pending"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.PendingEvents();
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        pendingVolunteering: function (search) {
            var self = this;

            require(["iznik/views/pages/modtools/volunteering_pending"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.PendingVolunteering();
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        socialActions: function () {
            var self = this;

            require(["iznik/views/pages/modtools/social"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.SocialActions();
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        facebookGroups: function () {
            var self = this;

            require(["iznik/views/pages/modtools/fbgroups"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.FacebookGroups();
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        admins: function () {
            var self = this;

            require(["iznik/views/pages/modtools/admins"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.Admins();
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        chatReview: function () {
            var self = this;

            require(["iznik/views/pages/modtools/chat_review"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.ChatReview();
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        chatReport: function () {
            var self = this;

            require(["iznik/views/pages/modtools/chat_report"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.ChatReport();
                    self.loadRoute({
                        page: page,
                        modtools: true
                    });
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        spamMembers: function () {
            var self = this;

            require(["iznik/views/pages/modtools/members_spam"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.SpamMembers();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        happinessMembers: function () {
            var self = this;

            require(["iznik/views/pages/modtools/members_happiness"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.HappinessMembers();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        storiesMembers: function () {
            var self = this;

            require(["iznik/views/pages/modtools/members_stories"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.StoriesMembers();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        storiesNewsletter: function () {
            var self = this;

            require(["iznik/views/pages/modtools/members_stories"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.StoriesMembers({
                        newsletter: true
                    });
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        spammerListPendingAdd: function (search) {
            var self = this;

            require(["iznik/views/pages/modtools/spammerlist"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.SpammerList({
                        search: search,
                        urlfragment: 'pendingadd',
                        collection: 'PendingAdd',
                        helpTemplate: 'modtools_spammerlist_help_pendingadd'
                    });
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        spammerListPendingRemove: function (search) {
            var self = this;

            require(["iznik/views/pages/modtools/spammerlist"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.SpammerList({
                        search: search,
                        urlfragment: 'pendingremove',
                        collection: 'PendingRemove',
                        helpTemplate: 'modtools_spammerlist_help_pendingremove'
                    });
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        spammerListConfirmed: function (search) {
            var self = this;

            require(["iznik/views/pages/modtools/spammerlist"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.SpammerList({
                        search: search,
                        urlfragment: 'confirmed',
                        collection: 'Spammer',
                        helpTemplate: 'modtools_spammerlist_help_confirmed'
                    });
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        spammerListWhitelisted: function (search) {
            var self = this;

            require(["iznik/views/pages/modtools/spammerlist"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.SpammerList({
                        search: search,
                        urlfragment: 'whitelisted',
                        collection: 'Whitelisted',
                        helpTemplate: 'modtools_spammerlist_help_whitelisted'
                    });
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        support: function () {
            var self = this;

            require(["iznik/views/pages/modtools/support"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    if (!Iznik.Session.isAdminOrSupport()) {
                        // You're not supposed to be here, are you?
                        Router.navigate('/', true);
                    } else {
                        var page = new Iznik.Views.ModTools.Pages.Support();
                        this.loadRoute({page: page, modtools: true});
                    }
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        confirmMail: function (key) {
            var self = this;

            require(["iznik/views/pages/modtools/settings"], function() {
                $.ajax({
                    type: 'PATCH',
                    url: API + 'session',
                    data: {
                        key: key
                    },
                    success: function (ret) {
                        var v;

                        if (ret.ret == 0) {
                            v = new Iznik.Views.ModTools.Settings.VerifySucceeded();
                        } else {
                            v = new Iznik.Views.ModTools.Settings.VerifyFailed();
                        }
                        self.listenToOnce(v, 'modalCancelled modalClosed', function () {
                            Router.navigate('/modtools/settings', true)
                        });

                        v.render();
                    },
                    error: function () {
                        var v = new Iznik.Views.ModTools.Settings.VerifyFailed();
                        self.listenToOnce(v, 'modalCancelled modalClosed', function () {
                            Router.navigate('/modtools/settings', true)
                        });

                        v.render();
                    }
                });
            });
        },

        settings: function () {
            var self = this;

            require(["iznik/views/pages/modtools/settings"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.Settings();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        alertViewed: function(alertid) {
            var self = this;

            require(["iznik/views/pages/user/alerts"], function() {
                var page = new Iznik.Views.User.Pages.Alert.Viewed({
                    model: new Iznik.Model({
                        id: alertid
                    })
                });
                self.loadRoute({page: page, modtools: false});
            });
        },

        mapSettings: function (groupid) {
            var self = this;

            require(["iznik/views/pages/modtools/settings"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.MapSettings({
                        groupid: groupid
                    });
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        mapAll: function () {
            var self = this;

            require(["iznik/views/pages/modtools/settings"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.MapSettings();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        sessions: function() {
            var self = this;
            require(["iznik/views/pages/modtools/replay"], function () {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.Sessions();
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        modLogs: function(logtype) {
            var self = this;
            require(["iznik/views/pages/modtools/logs"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.Logs({
                        logtype: logtype
                    });
                    page.modtools = true;
                    self.loadRoute({page: page});
                });

                Iznik.Session.forceLogin();
            });
        },

        modChats: function() {
            var self = this;
            require(["iznik/views/pages/chat"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.Chat.Page();
                    page.modtools = true;
                    self.loadRoute({page: page});
                });

                Iznik.Session.forceLogin();
            });
        },

        modChat: function(chatid) {
            var self = this;
            console.log("Mod Chat");
            require(["iznik/views/pages/chat"], function() {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.Chat.Page({
                        chatid: chatid
                    });
                    page.modtools = true;
                    self.loadRoute({page: page});
                });

                Iznik.Session.forceLogin();
            });
        },

        replay: function(sessionid) {
            var self = this;

            // Disable chat animation which interacts badly with replay.
            $("<style type='text/css'> .chat-window{ animation: none !important;} </style>").appendTo("head");
            console.log("Added CSS");

            require(["iznik/views/pages/modtools/replay"], function () {
                self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                    var page = new Iznik.Views.ModTools.Pages.Replay({
                        sessionid: sessionid
                    });
                    self.loadRoute({page: page, modtools: true});
                });

                Iznik.Session.forceLogin({
                    modtools: true
                });
            });
        },

        userMobile: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.Mobile();
                self.loadRoute({page: page});
            });
        },

        userAbout: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.About();
                self.loadRoute({page: page});
            });
        },

        userHandbook: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.Handbook();
                self.loadRoute({page: page});
            });
        },

        userTerms: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.Terms();
                self.loadRoute({page: page});
            });
        },
        
        userPrivacy: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.Privacy();
                self.loadRoute({page: page});
            });
        },
        
        userDisclaimer: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.Disclaimer();
                self.loadRoute({page: page});
            });
        },
        
        userDonate: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.Donate();
                self.loadRoute({page: page});
            });
        },

        userWhy: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.Why();
                self.loadRoute({page: page});
            });
        },

        userContact: function() {
            var self = this;

            require(["iznik/views/pages/user/landing"], function() {
                var page = new Iznik.Views.User.Pages.Landing.Contact();
                self.loadRoute({page: page});
            });
        },

        communityEventsPlugin: function(groupid) {
            this.userCommunityEvents(groupid, true);
        },

        groupPlugin: function(groupid) {
            // Might be trailing guff in legacy routes.
            groupid = parseInt(groupid);
            this.userExploreGroup(groupid, true);
        },

        findMyStreet: function(id) {
            var self = this;

            require(["iznik/views/pages/user/findmystreet"], function() {
                var page = new Iznik.Views.User.Pages.FindMyStreet();
                self.loadRoute({page: page});
            });
        }
    });

    $(document).ready(function() {
        // We're ready.  Get backbone up and running.
        var Router = new IznikRouter();
        window.Storage = null;

        try {
            try {
                // Set up storage.
                Storage = new Persist.Store("Iznik");

                // Make sure it works
                Storage.set('enabled', true);

                try {
                    // The version may have been put in localStorage.
                    Storage.set('version', localStorage.getItem('version'));
                } catch (e) {}

                Backbone.history.start({
                    pushState: true
                });
            } catch (e) {
                // We don't.
                Router.navigate('/localstorage', true);
            }
        } catch (e) {
            // We've got an uncaught exception.
            // TODO Log it to the server.
            window.alert("Top-level exception " + e);
            console.log("Top-level exception", e);
            console.trace();
        }

        // We can flag anchors as not to be handled via Backbone using data-realurl
        $(document).on('click', 'a:not([data-realurl]):not([data-toggle])', function (evt) {
            // Only trigger for our own anchors, except selectpicker which relies on #.
            // console.log("a click", $(this), $(this).parents('#bodyEnvelope').length);
            if (($(this).parents('#bodyEnvelope').length > 0 || $(this).parents('footer').length > 0) &&
                $(this).parents('.selectpicker').length == 0) {
                evt.preventDefault();
                evt.stopPropagation();
                var href = $(this).attr('href');
                var ret = Router.navigate(href, {trigger: true});

                if (ret === undefined && $(this).hasClass('allow-reload')) {
                    Backbone.history.loadUrl(href);
                }
            }
        });

        window.Router = Router;
    });
});