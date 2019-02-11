// CC var Raven = require('raven-js')
// CC var google_analytics = require('iznik/google_analytics.js')

define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'iznik/models/session',
  'iznik/views/modal',
  'iznik/views/help',
  'iznik/views/signinup'
], function ($, _, Backbone, Iznik) {
  Iznik.Session = new Iznik.Models.Session()

  Iznik.Session.askedPush = false

  // We have issues with PATCH and PUT.
  Backbone.emulateHTTP = true

  var IznikRouter = Backbone.Router.extend({
    initialize: function () {
      var self = this

      // CC google_analytics.init()

      // We want the ability to abort all outstanding requests, for example when we switch to a new route.
      self.xhrPool = []
      self.abortAll = function () {
        _.each(self.xhrPool, function (jqXHR) {
          try {
            jqXHR.abort()
          } catch (e) {}
        })

        self.xhrPool = []
      }

      $.ajaxSetup({
        beforeSend: function (jqXHR) {
          self.xhrPool.push(jqXHR)
        },
        complete: function (jqXHR) {
          var index = $.inArray(jqXHR, self.xhrPool)
          if (index > -1) {
            self.xhrPool.splice(index, 1)
          }
        }
      })

      // Any pages with trailing slashes should route the same as ones without.
      this.route(/(.*)\/+$/, 'trailFix', function (id) {
        this.navigate(id, true)
      })

      this.bind('route', this.pageView)
    },

    pageView: function () {
      var url = Backbone.history.getFragment()

      // CC if (!/^\//.test(url) && url != '') {
      // CC   url = '/' + url
      // CC }

      /* CC // Make sure we have google analytics for Backbone routes.
      try {
        ga('send', {
          hitType: 'pageview',
          page: url,
          location: window.location.origin + url
        })

        var timestamp = (new Date()).getTime()
      } catch (e) {
        console.log('Google exception - privacy blocker?', e)
      }*/
    },

    routes: {
      // TODO Legacy routes - hopefully we can retire these at some point.
      'tryfd.php?groupid=:id': 'userExploreGroup',
      'm.php?a=se(&g=:id)': 'legacyUserCommunityEvents',
      'events(/:id)': 'legacyUserCommunityEvents',
      'mygroups/:id/message/:id': 'legacyUserMessage',
      'explore/:id/message/:id': 'legacyUserMessage',
      'groups': 'legacyUserGroups',
      'location/:id': 'legacyUserGroups',
      'index.php?action=home': 'modtools',
      'index.php?action=pending': 'pendingMessages',
      'main.php?action=look&groupid=:id': 'userExploreGroup',
      'main.php?action=showevents*t': 'userCommunityEvents',
      'main.php?&action=join&then=displaygroup&groupid=:id': 'userExploreGroup',
      'main.php?action=mygroups': 'userMyGroups',
      'main.php?action=myposts': 'userHome',
      'main.php?action=post*t': 'userHome',
      'main.php?action=findgroup': 'userExplore',
      'login.php?action=mygroups&subaction=displaypost&msgid=:id&groupid=:id*': 'legacyUserMessage2',
      'legacy?action=join&groupid=:id&then=displaygroup': 'userExploreGroup',
      'legacy?action=look&groupid=:id': 'userExploreGroup',
      'legacy?action=mygroups*t': 'userMyGroups',
      'legacy?action=myposts': 'userHome',
      'legacy?action=mysettings': 'userSettings',
      'legacy?action=post*t': 'userHome',
      'legacy?action=showevents*t': 'userCommunityEvents',
      'legacy?a=se&g=:id': 'legacyUserCommunityEvents',
      'post': 'userHome',
      // End legacy

      'localstorage': 'localstorage',
      'yahoologin': 'yahoologin',
      'modtools/chat/:id': 'modChat',
      'modtools/chats': 'modChats',
      'modtools/logs(/:type)': 'modLogs',
      'modtools/supporters': 'supporters',
      'modtools/messages/pending': 'pendingMessages',
      'modtools/messages/approved/messagesearch/:search': 'approvedMessagesSearchMessages',
      'modtools/messages/approved/membersearch/:search': 'approvedMessagesSearchMembers',
      'modtools/messages/approved': 'approvedMessages',
      'modtools/messages/spam': 'spamMessages',
      'modtools/messages/edits': 'editReviewMessages',
      'modtools/members/pending(/:search)': 'pendingMembers',
      'modtools/members/approved/member/:groupid/:userid': 'approvedMember',
      'modtools/members/approved(/:search)': 'approvedMembers',
      'modtools/members/spam': 'spamMembers',
      'modtools/members/happiness': 'happinessMembers',
      'modtools/members/stories': 'storiesMembers',
      'modtools/members/newsletter': 'storiesNewsletter',
      'modtools/events/pending': 'pendingEvents',
      'modtools/volunteering/pending': 'pendingVolunteering',
      'modtools/publicity': 'socialActions',
      'modtools/admins': 'admins',
      'modtools/conversations/spam': 'chatReview',
      'modtools/conversations/reported': 'chatReport',
      'modtools/spammerlist/pendingadd(/:search)': 'spammerListPendingAdd',
      'modtools/spammerlist/confirmed(/:search)': 'spammerListConfirmed',
      'modtools/spammerlist/pendingremove(/:search)': 'spammerListPendingRemove',
      'modtools/spammerlist/whitelisted(/:search)': 'spammerListWhitelisted',
      'modtools/settings/all/map': 'mapAll',
      'modtools/settings/:id/map': 'mapSettings',
      'modtools/settings/confirmmail/(:key)': 'confirmMail',
      'modtools/settings': 'settings',
      'modtools/teams': 'teams',
      'modtools/mydata': 'myData',
      'modtools/support': 'support',
      'modtools/shortlinks': 'shortlinks',
      'modtools': 'modtools',
      'mobiledebug': 'mobiledebug',
      'find': 'userFindWhereAmI',
      'find/whereami': 'userFindWhereAmI',
      'find/search/(:search)': 'userSearched',
      'find/search': 'userSearch',
      'find/whatnext': 'userFindWhatNext',
      'find/whatisit': 'userFindWhatIsIt',
      'find/whoami': 'userFindWhoAmI',
      'give': 'userGiveWhereAmI',
      'give/whereami': 'userGiveWhereAmI',
      'give/whatisit': 'userGiveWhatIsIt',
      'give/whoami': 'userGiveWhoAmI',
      'give/whatnext': 'userGiveWhatNext',
      'edit/:id': 'userEdit',
      'm/:id': 'userMessage',
      'message/:id': 'userMessage',
      'mygroups': 'userMyGroups',
      'settings/confirmmail/(:key)': 'userConfirmMail',
      'settings': 'userSettings',
      'shortlinks': 'userShortlinks',
      'shortlinks/:id': 'userShortlink',
      'explore/region/:id': 'userExploreRegion',
      'explore/:id/join': 'userJoinGroup',
      'explore/:id': 'userExploreGroup',
      'explore': 'userExplore',
      'livemap': 'userLiveMap',
      'recentfreegles': 'userRecentFreegles',
      'helpus/aviva2017': 'userAviva',
      'aviva': 'userAviva',
      'ebay': 'userStatsEbay',
      'stats/ebay': 'userStatsEbay',
      'stats/eBay': 'userStatsEbay',
      'stats/heatmap': 'userStatsHeatMap',
      'stats/region/:id': 'userStatsRegion',
      'stats/authorities': 'userStatsAuthorities',
      'stats/authority/:id': 'userStatsAuthority',
      'stats(/:id)': 'userStatsGroup',
      'communityevents(/:id)': 'userCommunityEvents',
      'communityevent(/:id)': 'userCommunityEvent',
      'newuser': 'newUser',
      'unsubscribe(/:id)': 'userUnsubscribe',
      'chats': 'userChats',
      'chat/:id/external(/:id)': 'userChatExternal',
      'chat/:id': 'userChat',
      'alert/viewed/:id': 'alertViewed',
      'mobile': 'userMobile',
      'mobile/': 'userMobile',
      'about': 'userAbout',
      'volunteers': 'userVolunteers',
      'board': 'userBoard',
      'terms': 'userTerms',
      'handbook': 'userHandbook',
      'privacy': 'userPrivacy',
      'disclaimer': 'userDisclaimer',
      'donate': 'userDonate',
      'contact': 'userContact',
      'help': 'userContact',
      'invite/:id': 'userInvited',
      'invite': 'userInvite',
      'newsfeed/:id': 'userNewsfeedSingle',
      'newsfeed': 'userNewsfeed',
      'plugins/events/:id': 'communityEventsPlugin',
      'plugins/group?groupid=:id(&*t)': 'groupPlugin',
      'plugins/group/:id': 'groupPlugin',
      'mypost/:id/:id': 'userMyPostAction',
      'mypost/:id': 'userMyPost',
      'stories/fornewsletter': 'userNewsletterReview',
      'stories(/:id)': 'userStories',
      'story/:id': 'userStory',
      'volunteering': 'userVolunteerings',
      'volunteering/group/(/:id)': 'userVolunteerings',
      'volunteering/:id': 'userVolunteering',
      'why': 'userWhy',
      'myposts': 'userHome',
      'mydata': 'myData',
      'profile/:id': 'userProfile',
      'councils/overview': 'userCouncilsOverview',
      'councils/volunteers': 'userCouncilsVolunteers',
      'councils/keylinks(/:section)': 'userCouncilsKeyLinks',
      'councils/workbest(/:section)': 'userCouncilsWorkBest',
      'councils/graphics(/:section)': 'userCouncilsGraphics',
      'councils/photosvideos(/:section)': 'userCouncilsPhotosVideos',
      'councils/posters': 'userCouncilsPosters',
      'councils/banners': 'userCouncilsBanners',
      'councils/businesscards': 'userCouncilsBusinessCards',
      'councils/media(/:section)': 'userCouncilsMedia',
      'councils/socialmedia(/:section)': 'userCouncilsSocialMedia',
      'councils/pressrelease': 'userCouncilsPressRelease',
      'councils/stories': 'userCouncilsUserStories',
      'councils/othercouncils': 'userCouncilsOtherCouncils',
      'councils/bestpractice': 'userCouncilsBestPractice',
      'councils': 'userCouncils',
      'maintenance': 'userMaintenance',
      '*path': 'userDefault'
    },

    loadRoute: function (routeOptions) {
      var self = this

      try {
        // We're no longer interested in any outstanding requests, and we also want to avoid them clogging up
        // our per-host limit.
        self.abortAll()

        // Tidy any modal grey.
        $('.modal-backdrop').remove()

        // The top button might be showing.
        $('.js-scrolltop').addClass('hidden')

        //console.log("loadRoute"); console.log(routeOptions);
        routeOptions = routeOptions || {}

        self.modtools = routeOptions.modtools
        Iznik.Session.set('modtools', self.modtools)

        function loadPage () {
          // Hide the page loader, which might still be there.
          $('#pageloader').remove()
          $('body').css('height', '')

          routeOptions.page.render()
        }

        loadPage()

      } catch (e) {
        throw e;
        // CC Raven.captureException(e)
      }
    },

    localstorage: function () {
      var self = this
      require(['iznik/views/pages/pages'], function () {
        var page = new Iznik.Views.LocalStorage()
        self.loadRoute({page: page})
      })
    },

    mobileReload: function (url) {  // CC url not used - could be used to specify route to use
        window.location.href = window.initialURL;  // Could add ?route=Xxx
    },

    userHome: function () {
      if (!MODTOOLS) {
        var self = this

        if (document.URL.indexOf('modtools') !== -1) {
          Router.navigate('/modtools', true)
        } else {
          if (Iznik.Session.maintenanceMode) {  // CC
            console.log("userHome in maintenanceMode");
          }
          function f (loggedIn) {
            // console.log("Logged in", loggedIn);
            if (Iznik.Session.maintenanceMode) {  // CC
              console.log("Don't load home or landing as in maintenanceMode");
            } else if (loggedIn || _.isUndefined(loggedIn)) {
              require(['iznik/views/pages/user/home'], function () {
                var page = new Iznik.Views.User.Pages.Home()
                self.loadRoute({page: page})
              })
            } else {
              require(['iznik/views/pages/user/landing'], function () {
                var page = new Iznik.Views.User.Pages.Landing()
                self.loadRoute({page: page})
              })
            }
          }

          self.listenToOnce(Iznik.Session, 'isLoggedIn', f)
          Iznik.Session.testLoggedIn(['all'])
        }
      }
    },

    userMyPostAction: function (msgid, action) {
      if (!MODTOOLS) {
        var self = this

        self.listenToOnce(Iznik.Session, 'loggedIn', function () {
          require(['iznik/views/pages/user/home'], function () {
            var page = new Iznik.Views.User.Pages.MyPost({
              id: msgid,
              action: action
            })
            self.loadRoute({page: page})
          })
        })

        Iznik.Session.forceLogin([
          'me',
          'groups'
        ])
      }
    },

    userProfile: function (id) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/profile'], function () {
          var page = new Iznik.Views.User.Pages.Profile({
            model: new Iznik.Models.ModTools.User({
              id: id
            })
          })
          self.loadRoute({page: page})
        })
      }
    },

    userMyPost: function (msgid) {
      if (!MODTOOLS) {
        var self = this

        self.listenToOnce(Iznik.Session, 'loggedIn', function () {
          require(['iznik/views/pages/user/home'], function () {
            var page = new Iznik.Views.User.Pages.MyPost({
              id: msgid
            })
            self.loadRoute({page: page})
          })
        })

        Iznik.Session.forceLogin([
          'me',
          'groups'
        ])
      }
    },

    userNewsletterReview: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stories'], function () {
          var page = new Iznik.Views.User.Pages.Stories({
            reviewnewsletter: true
          })
          self.loadRoute({page: page})
        })
      }
    },

    userDefault: function () {
      if (!MODTOOLS) {
        var self = this

        function f (loggedIn) {
          // console.log("Logged in", loggedIn);
          if (loggedIn || _.isUndefined(loggedIn)) {
            // Load the last of the main pages that they had open.
            var page = Storage.get('lasthomepage')

            switch (page) {
              case 'news': {
                self.userNewsfeed()
                break
              }
              case 'myposts': {
                self.userHome()
                break
              }
              case 'mygroups': {
                self.userMyGroups()
                break
              }
              default: {
                self.userNewsfeed()
                break
              }
            }
          } else {
            if (Iznik.Session.maintenanceMode) {  // CC
              self.userMaintenance();
              return false;
            }
            require(['iznik/views/pages/user/landing'], function () {
              var page = new Iznik.Views.User.Pages.Landing()
              self.loadRoute({page: page})
            })
          }
        }

        self.listenToOnce(Iznik.Session, 'isLoggedIn', f)
        Iznik.Session.testLoggedIn([
          'me'
        ])
      }
    },

    userStories: function (groupid) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stories'], function () {
          var page = new Iznik.Views.User.Pages.Stories({
            groupid: groupid
          })
          self.loadRoute({page: page})
        })
      }
    },

    userStory: function (id) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stories'], function () {
          var page = new Iznik.Views.User.Pages.Stories.Single({
            id: id
          })
          self.loadRoute({page: page})
        })
      }
    },

    userChats: function () {
      if (!MODTOOLS) {
        var self = this
        require(['iznik/views/pages/chat'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.Chat.Page()
            self.loadRoute({page: page})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    userChat: function (chatid) {
      if (!MODTOOLS) {
        var self = this
        require(['iznik/views/pages/chat'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.Chat.Page({
              chatid: chatid
            })
            self.loadRoute({page: page})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    userChatExternal: function (chatid, msgid) {
      if (!MODTOOLS) {
        var self = this
        require(['iznik/views/pages/chat'], function () {
          var page = new Iznik.Views.Chat.External({
            chatid: chatid,
            msgid: msgid
          })
          self.loadRoute({page: page})
        })
      }
    },

    userFindWhereAmI: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/find'], function () {
          var page = new Iznik.Views.User.Pages.Find.WhereAmI()
          self.loadRoute({page: page})
        })
      }
    },

    userSearch: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/find'], function () {
          var page = new Iznik.Views.User.Pages.Find.Search({
            browse: true
          })
          self.loadRoute({page: page})
        })
      }
    },

    userSearched: function (query) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/find'], function () {
          var page = new Iznik.Views.User.Pages.Find.Search({
            search: query
          })

          try {
            Storage.set('lastsearch', query)
          } catch (e) {}

          self.loadRoute({page: page})
        })
      }
    },

    userGiveWhereAmI: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/give'], function () {
          var page = new Iznik.Views.User.Pages.Give.WhereAmI()
          self.loadRoute({page: page})
        })
      }
    },

    userGiveWhatIsIt: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/give'], function () {
          var page = new Iznik.Views.User.Pages.Give.WhatIsIt()
          self.loadRoute({page: page})
        })
      }
    },

    userGiveWhoAmI: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/give'], function () {
          var page = new Iznik.Views.User.Pages.Give.WhoAmI()
          self.loadRoute({page: page})
        })
      }
    },

    userFindWhatIsIt: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/find'], function () {
          var page = new Iznik.Views.User.Pages.Find.WhatIsIt()
          self.loadRoute({page: page})
        })
      }
    },

    userFindWhoAmI: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/find'], function () {
          var page = new Iznik.Views.User.Pages.Find.WhoAmI()
          self.loadRoute({page: page})
        })
      }
    },

    userGiveWhatNext: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/give'], function () {
          var page = new Iznik.Views.User.Pages.Give.WhatNext()
          self.loadRoute({page: page})
        })
      }
    },

    userFindWhatNext: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/find'], function () {
          var page = new Iznik.Views.User.Pages.Find.WhatNext()
          self.loadRoute({page: page})
        })
      }
    },

    userMyGroups: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/mygroups'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.User.Pages.MyGroups()
            self.loadRoute({page: page})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'newsfeed'
          ])
        })
      }
    },

    userConfirmMail: function (key) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/settings'], function () {
          $.ajax({
            type: 'POST',
            headers: {
              'X-HTTP-Method-Override': 'PATCH'
            },
            url: API + 'session',
            data: {
              key: key
            },
            success: function (ret) {
              var v

              if (ret.ret == 0) {
                v = new Iznik.Views.User.Pages.Settings.VerifySucceeded()
              } else {
                v = new Iznik.Views.User.Pages.Settings.VerifyFailed()
              }
              self.listenToOnce(v, 'modalCancelled modalClosed', function () {
                // Reload to force session refresh.
                // TODO lame.
                // CC window.location = '/'
                Router.mobileReload(); // CC
              })

              v.render()
            },
            error: function () {
              var v = new Iznik.Views.User.Pages.Settings.VerifyFailed()
              self.listenToOnce(v, 'modalCancelled modalClosed', function () {
                Router.navigate('/settings', true)
              })

              v.render()
            }
          })
        })
      }
    },

    userSettings: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/settings'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.User.Pages.Settings()
            self.loadRoute({page: page})
          })

          Iznik.Session.forceLogin()
        })
      }
    },

    userJoinGroup: function (id) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/explore'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.User.Pages.ExploreGroup({
              id: id,
              join: true
            })
            self.loadRoute({page: page})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    legacyUserGroups: function (loc) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/explore'], function () {
          // Legacy route.  If we have a name, we need to search.
          if (loc) {
            // This is the route for /location/loc
            var page = new Iznik.Views.User.Pages.Explore({
              search: loc
            })
            self.loadRoute({page: page})
          } else {
            // This is the route for /groups or /groups#loc.
            var hash = Backbone.history.getHash()

            if (hash) {
              var page = new Iznik.Views.User.Pages.Explore({
                search: hash
              })
              self.loadRoute({page: page})
            } else {
              Router.navigate('/explore', true)
            }
          }
        })
      }
    },

    userInvite: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/invite'], function () {
          var page = new Iznik.Views.User.Pages.Invite()
          self.loadRoute({page: page})
        })
      }
    },

    userNewsfeed: function () {
      if (!MODTOOLS) {
        var self = this

        self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
          require(['iznik/views/pages/user/newsfeed'], function () {
            var page = new Iznik.Views.User.Pages.Newsfeed()
            self.loadRoute({page: page})
          })
        })

        Iznik.Session.forceLogin([
          'me',
          'groups',
          'newsfeed'
        ])
      }
    },

    userNewsfeedSingle: function (id) {
      if (!MODTOOLS) {
        var self = this

        self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
          require(['iznik/views/pages/user/newsfeed'], function () {
            var page = new Iznik.Views.User.Pages.Newsfeed.Single({
              id: id
            })
            self.loadRoute({page: page})
          })
        })

        Iznik.Session.forceLogin([
          'me',
          'groups',
          'newsfeed'
        ])
      }
    },

    userInvited: function (id) {
      if (!MODTOOLS) {
        // Record result of invitation.
        var self = this
        $.ajax({
          url: API + 'invitation',
          type: 'POST',
          headers: {
            'X-HTTP-Method-Override': 'PATCH'
          },
          data: {
            id: id,
            outcome: 'Accepted'
          }, complete: function () {
            self.userHome()
          }
        })
      }
    },

    userExploreGroup: function (id, naked) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/explore'], function () {
          var page = new Iznik.Views.User.Pages.ExploreGroup({
            id: id,
            naked: naked
          })
          self.loadRoute({page: page})
        })
      }
    },

    userExploreRegion: function (region) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/explore'], function () {
          var page = new Iznik.Views.User.Pages.Explore({
            region: region
          })
          self.loadRoute({page: page})
        })
      }
    },

    userExplore: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/explore'], function () {
          var page = new Iznik.Views.User.Pages.Explore()
          self.loadRoute({page: page})
        })
      }
    },

    userLiveMap: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/livemap'], function () {
          var page = new Iznik.Views.User.Pages.LiveMap()
          self.loadRoute({page: page})
        })
      }
    },

    userRecentFreegles: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/recentfreegles'], function () {
          var page = new Iznik.Views.User.Pages.RecentFreegles()
          self.loadRoute({page: page})
        })
      }
    },

    userStatsHeatMap: function (area) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stats'], function () {
          var page = new Iznik.Views.User.Pages.Heatmap()
          self.loadRoute({page: page})
        })
      }
    },

    userAviva: function (area) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/pages'], function () {
          require(['iznik/views/supportus'], function () {
            var page = new Iznik.Views.Aviva()
            self.loadRoute({page: page})
          })
        })
      }
    },

    userStatsEbay: function (area) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stats'], function () {
          var page = new Iznik.Views.User.Pages.Ebay()
          self.loadRoute({page: page})
        })
      }
    },

    userStatsRegion: function (region) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stats'], function () {
          var page = new Iznik.Views.User.Pages.StatsGroup({
            region: region
          })
          self.loadRoute({page: page})
        })
      }
    },

    userStatsAuthorities: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stats'], function () {
          var page = new Iznik.Views.User.Pages.Authorities()
          self.loadRoute({page: page})
        })
      }
    },

    userStatsAuthority: function (authorityid) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stats'], function () {
          var page = new Iznik.Views.User.Pages.StatsAuthority({
            id: authorityid
          })
          self.loadRoute({page: page})
        })
      }
    },

    userStatsGroup: function (id) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/stats'], function () {
          var page = new Iznik.Views.User.Pages.StatsGroup({
            id: id
          })
          self.loadRoute({page: page})
        })
      }
    },

    legacyUserCommunityEvents: function (legacyid) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/models/group'], function () {
          // Map the legacy id to a real id.
          var group = new Iznik.Models.Group({
            id: legacyid
          })

          group.fetch().then(function () {
            self.userCommunityEvents(group.get('id'))
          })
        })
      }
    },

    userCommunityEvents: function (groupid, naked) {
      if (!MODTOOLS) {
        var self = this

        // We might be called in the legacy case with some random guff on the end of the url.
        if (groupid && typeof groupid == 'string') {
          groupid = groupid.substr(0, 1) == '&' ? null : parseInt(groupid)
        }

        require(['iznik/views/pages/user/communityevents'], function () {
          var page = new Iznik.Views.User.Pages.CommunityEvents({
            groupid: groupid,
            naked: naked
          })

          if (groupid) {
            // We can see events for a specific group when we're logged out.
            self.loadRoute({page: page})
          } else {
            // But for all groups, we need to log in.
            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
              self.loadRoute({page: page})
            })

            Iznik.Session.forceLogin([
              'me',
              'groups',
              'newsfeed'
            ])
          }
        })
      }
    },

    userCommunityEvent: function (id) {
      if (!MODTOOLS) {
        var self = this
        require(['iznik/views/pages/user/communityevents'], function () {
          var page = new Iznik.Views.User.Pages.CommunityEvent({
            id: id
          })
          self.loadRoute({page: page})
        })
      }
    },

    userVolunteerings: function (groupid, naked) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/volunteering'], function () {
          var page = new Iznik.Views.User.Pages.Volunteerings({
            groupid: groupid,
            naked: naked
          })

          if (groupid) {
            // We can see volunteer vacancies for a specific group when we're logged out.
            self.loadRoute({page: page})
          } else {
            // But for all groups, we need to log in.
            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
              self.loadRoute({page: page})
            })

            Iznik.Session.forceLogin([
              'me',
              'groups',
              'newsfeed'
            ])
          }
        })
      }
    },

    userVolunteering: function (id) {
      if (!MODTOOLS) {
        var self = this
        require(['iznik/views/pages/user/volunteering'], function () {
          var page = new Iznik.Views.User.Pages.Volunteering({
            id: id
          })
          self.loadRoute({page: page})
        })
      }
    },

    legacyUserMessage: function (groupid, messageid) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/explore'], function () {
          var page = new Iznik.Views.User.Pages.LegacyMessage({
            id: messageid,
            groupid: groupid
          })
          self.loadRoute({page: page})
        })
      }
    },

    legacyUserMessage2: function (messageid, groupid) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/explore'], function () {
          var page = new Iznik.Views.User.Pages.LegacyMessage({
            id: messageid,
            groupid: groupid
          })
          self.loadRoute({page: page})
        })
      }
    },

    userMessage: function (id) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/explore'], function () {
          var page = new Iznik.Views.User.Pages.Message({
            id: id
          })
          self.loadRoute({page: page})
        })
      }
    },

    userEdit: function (id) {
      if (!MODTOOLS) {
        var self = this

        // We convert the message back into a draft, and assuming that works, navigate to the appropriate
        // page.
        $.ajax({
          url: API + 'message',
          type: 'POST',
          data: {
            id: id,
            action: 'RejectToDraft'
          },
          success: function (ret) {
            if (ret.ret === 0) {
              try {
                Storage.set('draft', id)
                Storage.set('draftrepost', id)

                if (ret.messagetype == 'Offer') {
                  // Make them reconfirm the location
                  Router.navigate('/give/whereami', true)
                } else {
                  // TODO Should we be able to change the location?
                  Router.navigate('/find/whatisit', true)
                }
              } catch (e) {}
            }
          }
        })
      }
    },

    userUnsubscribe: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/unsubscribe'], function () {
          var page = new Iznik.Views.User.Pages.Unsubscribe()
          self.loadRoute({page: page})
        })
      }
    },

    newUser: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/new'], function () {
          var page = new Iznik.Views.User.Pages.New()
          self.loadRoute({page: page})
        })
      }
    },

    yahoologin: function (path) {
      var self = this

      // We have been redirected here after an attempt to sign in with Yahoo.  We now try again to login
      // on the server.  This time we should succeed.
      var returnto = Iznik.getURLParam('returnto')

      self.listenToOnce(Iznik.Session, 'yahoologincomplete', function (ret) {
        if (ret.ret == 0) {
          if (returnto) {
            window.location = returnto
          } else {
            self.userHome.call(self)
          }
        } else {
          // TODO
          window.location = '/'
        }
      })

      Iznik.Session.yahooLogin()
    },

    modtools: function () {
      if (MODTOOLS) {
        var self = this
        require(['iznik/views/dashboard', 'iznik/views/pages/user/settings', 'iznik/views/pages/modtools/landing'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.Landing()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    supporters: function () {
      if (MODTOOLS) {
        var page = new Iznik.Views.ModTools.Pages.Supporters()
        this.loadRoute({page: page})
      }
    },

    pendingMessages: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/messages_pending'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.PendingMessages()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    spamMessages: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/messages_spam'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.SpamMessages()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    editReviewMessages: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/messages_editreview'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.EditReviewMessages()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    approvedMessagesSearchMessages: function (search) {
      if (MODTOOLS) {
        this.approvedMessages(search, null)
      }
    },

    approvedMessagesSearchMembers: function (search) {
      if (MODTOOLS) {
        this.approvedMessages(null, search)
      }
    },

    approvedMessages: function (searchmess, searchmemb) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/messages_approved'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.ApprovedMessages({
              searchmess: searchmess,
              searchmemb: searchmemb
            })
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    pendingMembers: function (search) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/members_pending', 'iznik/views/pages/modtools/messages_pending'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.PendingMembers({
              search: search
            })
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    approvedMembers: function (search) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/members_approved'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.ApprovedMembers({
              search: search
            })
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    approvedMember: function (groupid, userid) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/members_approved'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.ApprovedMembers({
              search: userid,
              groupid: groupid,
            })
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    pendingEvents: function (search) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/events_pending'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.PendingEvents()
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    pendingVolunteering: function (search) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/volunteering_pending'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.PendingVolunteering()
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    socialActions: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/social'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.SocialActions()
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    admins: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/admins'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.Admins()
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    chatReview: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/chat_review'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.ChatReview()
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    chatReport: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/chat_report'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.ChatReport()
            self.loadRoute({
              page: page,
              modtools: true
            })
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    spamMembers: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/members_spam'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.SpamMembers()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    happinessMembers: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/members_happiness'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.HappinessMembers()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    storiesMembers: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/members_stories'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.StoriesMembers()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    storiesNewsletter: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/members_stories'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.StoriesMembers({
              newsletter: true
            })
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    spammerListPendingAdd: function (search) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/spammerlist'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.SpammerList({
              search: search,
              urlfragment: 'pendingadd',
              collection: 'PendingAdd',
              helpTemplate: 'modtools_spammerlist_help_pendingadd'
            })
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    spammerListPendingRemove: function (search) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/spammerlist'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.SpammerList({
              search: search,
              urlfragment: 'pendingremove',
              collection: 'PendingRemove',
              helpTemplate: 'modtools_spammerlist_help_pendingremove'
            })
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    spammerListConfirmed: function (search) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/spammerlist'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.SpammerList({
              search: search,
              urlfragment: 'confirmed',
              collection: 'Spammer',
              helpTemplate: 'modtools_spammerlist_help_confirmed'
            })
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    spammerListWhitelisted: function (search) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/spammerlist'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.SpammerList({
              search: search,
              urlfragment: 'whitelisted',
              collection: 'Whitelisted',
              helpTemplate: 'modtools_spammerlist_help_whitelisted'
            })
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups',
            'configs'
          ])
        })
      }
    },

    support: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/support'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            if (!Iznik.Session.isAdminOrSupport()) {
              // You're not supposed to be here, are you?
              Router.navigate('/', true)
            } else {
              var page = new Iznik.Views.ModTools.Pages.Support()
              self.loadRoute({page: page, modtools: true})
            }
          })

          Iznik.Session.forceLogin([
            'me'
          ])
        })
      }
    },

    shortlinks: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/shortlinks'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            if (!Iznik.Session.isFreegleMod()) {
              // You're not supposed to be here, are you?
              Router.navigate('/', true)
            } else {
              var page = new Iznik.Views.ModTools.Pages.Shortlinks()
              self.loadRoute({page: page, modtools: true})
            }
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    userShortlinks: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/shortlinks'], function () {
          var page = new Iznik.Views.User.Pages.Shortlinks();
          self.loadRoute({page: page, modtools: false})
        })
      }
    },

    userShortlink: function (id) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/shortlinks'], function () {
          var page = new Iznik.Views.User.Pages.Shortlink({
            id: id
          });
          self.loadRoute({page: page, modtools: false})
        })
      }
    },

    confirmMail: function (key) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/settings'], function () {
          $.ajax({
            type: 'POST',
            headers: {
              'X-HTTP-Method-Override': 'PATCH'
            },
            url: API + 'session',
            data: {
              key: key
            },
            success: function (ret) {
              var v

              if (ret.ret == 0) {
                v = new Iznik.Views.ModTools.Settings.VerifySucceeded()
              } else {
                v = new Iznik.Views.ModTools.Settings.VerifyFailed()
              }
              self.listenToOnce(v, 'modalCancelled modalClosed', function () {
                Router.navigate('/modtools/settings', true)
              })

              v.render()
            },
            error: function () {
              var v = new Iznik.Views.ModTools.Settings.VerifyFailed()
              self.listenToOnce(v, 'modalCancelled modalClosed', function () {
                Router.navigate('/modtools/settings', true)
              })

              v.render()
            }
          })
        })
      }
    },

    settings: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/settings'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.Settings()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin()
        })
      }
    },

    mobiledebug: function () {  // CC 
      var self = this;
      require(["iznik/views/pages/user/mobiledebug"], function () {
        var page = new Iznik.Views.User.Pages.MobileDebug();
        self.loadRoute({ page: page });
      });
    },

    alertViewed: function (alertid) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/alerts'], function () {
          var page = new Iznik.Views.User.Pages.Alert.Viewed({
            model: new Iznik.Model({
              id: alertid
            })
          })
          self.loadRoute({page: page, modtools: false})
        })
      }
    },

    mapSettings: function (groupid) {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/settings'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.MapSettings({
              groupid: groupid
            })
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    mapAll: function () {
      if (MODTOOLS) {
        var self = this

        require(['iznik/views/pages/modtools/settings'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.MapSettings()
            self.loadRoute({page: page, modtools: true})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    modLogs: function (logtype) {
      if (MODTOOLS) {
        var self = this
        require(['iznik/views/pages/modtools/logs'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.ModTools.Pages.Logs({
              logtype: logtype
            })
            page.modtools = true
            self.loadRoute({page: page})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    modChats: function () {
      if (MODTOOLS) {
        var self = this
        require(['iznik/views/pages/chat'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.Chat.Page()
            page.modtools = true
            self.loadRoute({page: page})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    modChat: function (chatid) {
      if (MODTOOLS) {
        var self = this
        require(['iznik/views/pages/chat'], function () {
          self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
            var page = new Iznik.Views.Chat.Page({
              chatid: chatid
            })
            page.modtools = true
            self.loadRoute({page: page})
          })

          Iznik.Session.forceLogin([
            'me',
            'groups'
          ])
        })
      }
    },

    userMobile: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.Mobile()
          self.loadRoute({page: page})
        })
      }
    },

    userAbout: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.About()
          self.loadRoute({page: page})
        })
      }
    },

    teams: function () {
      if (MODTOOLS) {
        var self = this

        self.listenToOnce(Iznik.Session, 'loggedIn', function () {
          require(['iznik/views/teams'], function () {
            var page = new Iznik.Views.ModTools.Pages.Teams()
            self.loadRoute({page: page})
          })
        })

        Iznik.Session.forceLogin([
          'me'
        ])
      }
    },

    userVolunteers: function () {
      if (!MODTOOLS) {
        var self = this

        self.listenToOnce(Iznik.Session, 'loggedIn', function () {
          require(['iznik/views/teams'], function () {
            var page = new Iznik.Views.ModTools.Pages.Teams()
            self.loadRoute({page: page})
          })
        })

        Iznik.Session.forceLogin([
          'me'
        ])
      }
    },

    userBoard: function () {
      if (!MODTOOLS) {
        var self = this

        self.listenToOnce(Iznik.Session, 'loggedIn', function () {
          require(['iznik/views/teams'], function () {
            var page = new Iznik.Views.ModTools.Pages.Teams()
            self.loadRoute({page: page})
          })
        })

        Iznik.Session.forceLogin([
          'me'
        ])
      }
    },

    userHandbook: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.Handbook()
          self.loadRoute({page: page})
        })
      }
    },

    userTerms: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.Terms()
          self.loadRoute({page: page})
        })
      }
    },

    userPrivacy: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.Privacy()
          self.loadRoute({page: page})
        })
      }
    },

    userDisclaimer: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.Disclaimer()
          self.loadRoute({page: page})
        })
      }
    },

    userDonate: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.Donate()
          self.loadRoute({page: page})
        })
      }
    },

    userWhy: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.Why()
          self.loadRoute({page: page})
        })
      }
    },

    userContact: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/landing'], function () {
          var page = new Iznik.Views.User.Pages.Landing.Contact()
          self.loadRoute({page: page})
        })
      }
    },

    userMaintenance: function () {  // CC
      var self = this;
      console.log("userMaintenance");
      require(["iznik/views/pages/user/landing"], function () {
        var page = new Iznik.Views.User.Pages.Landing.Maintenance();
        self.loadRoute({ page: page });
      });
    },

    userCouncils: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.Main()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsOverview: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.Overview()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsVolunteers: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.Volunteers()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsKeyLinks: function (section) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.KeyLinks({
            section: section
          })
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsWorkBest: function (section) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.WorkBest({
            section: section
          })
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsGraphics: function (section) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.Graphics({
            section: section
          })
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsPhotosVideos: function (section) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.PhotosVideos({
            section: section
          })
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsPosters: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.Posters()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsBanners: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.Banners()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsBusinessCards: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.BusinessCards()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsMedia: function (section) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.Media({
            section: section
          })
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsSocialMedia: function (section) {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.SocialMedia({
            section: section
          })
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsPressRelease: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.PressRelease()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsUserStories: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.UserStories()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsOtherCouncils: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.OtherCouncils()
          self.loadRoute({page: page})
        })
      }
    },

    userCouncilsBestPractice: function () {
      if (!MODTOOLS) {
        var self = this

        require(['iznik/views/pages/user/councils'], function () {
          var page = new Iznik.Views.User.Pages.Councils.BestPractice()
          self.loadRoute({page: page})
        })
      }
    },

    communityEventsPlugin: function (groupid) {
      if (!MODTOOLS) {
        this.userCommunityEvents(groupid, true)
      }
    },

    groupPlugin: function (groupid) {
      if (!MODTOOLS) {
        // Might be trailing guff in legacy routes.
        groupid = parseInt(groupid)
        this.userExploreGroup(groupid, true)
      }
    },

    myData: function () {
      var self = this

      self.listenToOnce(Iznik.Session, 'loggedIn', function () {
        require(['iznik/views/pages/mydata'], function () {
          var page = new Iznik.Views.MyData()
          self.loadRoute({page: page})
        })
      })

      Iznik.Session.forceLogin()
    },
  })

  // CC
  // Might need: $(document).ready(function() {
  // We're ready.  Get backbone up and running.
  var Router = new IznikRouter();

  try {
    var root = location.pathname.substring(0, location.pathname.lastIndexOf('/') + 1);	// CC
    root = decodeURI(root.replace(/%25/g, '%2525'));	// CC
    console.log("Backbone root", root);	// CC
    Backbone.history.start({
      root: root,	// CC
      pushState: true
    });
  } catch (e) {
    // We've got an uncaught exception.
    // TODO Log it to the server.
    window.alert("Top-level exception " + e);
    console.log("Top-level exception", e);
    console.trace();
  }

  function internal(evt, href) {
    if (href.charAt(0) == '/') {
      evt.preventDefault();
      evt.stopPropagation();

      var ret = Router.navigate(href, { trigger: true });

      if (ret === undefined && $link.hasClass('allow-reload')) {
        console.log("LINK 5");
        alert("LINK 5: " + href);
        Backbone.history.loadUrl(href);
      }
      //console.log("LINK 6");
    }
    // Could be #, #something or absolute URL: all OK
    //console.log("LINK 7: "+href);
  }

  $(document).on('click', 'a', function (evt) {
    var href = $(this).attr('href');
    //console.log("LINK 4: " + href);
    internal(evt, href);
  });

  window.Router = Router;

  /* // CC
  $(document).ready(function () {
    // We're ready.  Get backbone up and running.
    var Router = new IznikRouter()
    window.Storage = null

    try {
      try {
        // Set up storage.
        Storage = new Persist.Store('Iznik')

        // Make sure it works
        Storage.set('enabled', true)

        try {
          // The version may have been put in localStorage.
          Storage.set('version', localStorage.getItem('version'))
        } catch (e) {}

        Backbone.history.start({
          pushState: true
        })
      } catch (e) {
        // We don't.
        Router.navigate('/localstorage', true)
      }
    } catch (e) {
      // We've got an uncaught exception.
      // TODO Log it to the server.
      window.alert('Top-level exception ' + e)
      console.log('Top-level exception', e)
      console.trace()
    }

    // We can flag anchors as not to be handled via Backbone using data-realurl
    $(document).on('click', 'a:not([data-realurl]):not([data-toggle])', function (evt) {
      // Only trigger for our own anchors, except selectpicker which relies on #.
      // console.log("a click", $(this), $(this).parents('#bodyEnvelope').length);
      if (($(this).parents('#bodyEnvelope').length > 0 || $(this).parents('footer').length > 0) &&
        $(this).parents('.selectpicker').length == 0) {
        evt.preventDefault()
        evt.stopPropagation()
        var href = $(this).attr('href')
        var ret = Router.navigate(href, {trigger: true})

        if (ret === undefined && $(this).hasClass('allow-reload')) {
          Backbone.history.loadUrl(href)
        }
      }
    })

    window.Router = Router
  })*/
})

