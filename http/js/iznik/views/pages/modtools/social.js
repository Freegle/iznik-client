define([
  'jquery',
  'underscore',
  'backbone',
  'moment',
  'iznik/base',
  'typeahead',
  'jquery.validate.min',
  'jquery.validate.additional-methods',
  'iznik/customvalidate',
  'iznik/modtools',
  'iznik/models/social',
  'iznik/views/pages/pages',
  'iznik/views/infinite',
  'iznik/views/supportus',
  'iznik/views/postaladdress'
], function ($, _, Backbone, moment, Iznik) {
  Iznik.Views.ModTools.Pages.SocialActions = Iznik.Views.Infinite.extend({
    modtools: true,

    template: 'modtools_socialactions_main',

    retField: 'socialactions',

    events: {
      'click .js-businesscards': 'businessCards'
    },

    businessCards: function () {
      var v = new Iznik.Views.User.BusinessCards()
      v.render()
    },

    render: function () {
      var self = this
      var p = Iznik.Views.Infinite.prototype.render.call(this)

      p.then(function (self) {
        require(['iznik/facebook'], function (FBLoad) {
          self.listenToOnce(FBLoad(), 'fbloaded', function () {
            if (!FBLoad().isDisabled()) {
              self.$('.js-facebookonly').show()
            }
          })

          FBLoad().render()
        })

        var v = new Iznik.Views.Help.Box()
        v.template = 'modtools_socialactions_help'
        v.render().then(function (v) {
          self.$('.js-help').html(v.el)
        })

        var w = new Iznik.Views.ModTools.Settings.MissingFacebook()
        w.render().then(function () {
          self.$('.js-missingfacebook').html(w.el)
        })

        self.lastFetched = null
        self.context = null

        self.collection = new Iznik.Collections.SocialActions()

        self.collectionView = new Backbone.CollectionView({
          el: self.$('.js-list'),
          modelView: Iznik.Views.ModTools.SocialAction,
          collection: self.collection,
          processKeyEvents: false
        })

        self.collectionView.render()
        self.fetch()

        self.requests = new Iznik.Collections.Requests()

        self.requestCollectionView = new Backbone.CollectionView({
          el: self.$('.js-requestlist'),
          modelView: Iznik.Views.ModTools.SocialAction.Request,
          collection: self.requests,
          processKeyEvents: false
        })

        self.requestCollectionView.render()
        self.requests.fetch()

        if (Iznik.Session.hasPermission('BusinessCardsAdmin')) {
          self.outstanding = new Iznik.Collections.Requests()

          self.outstandingCollectionView = new Backbone.CollectionView({
            el: self.$('.js-outstandinglist'),
            modelView: Iznik.Views.ModTools.SocialAction.Outstanding,
            collection: self.outstanding,
            processKeyEvents: false
          })

          self.outstandingCollectionView.render()
          self.outstanding.fetch({
            data: {
              outstanding: true
            }
          })

          self.recent = new Iznik.Collections.Requests.Recent()

          self.recentCollectionView = new Backbone.CollectionView({
            el: self.$('.js-recentlist'),
            modelView: Iznik.Views.ModTools.SocialAction.Recent,
            collection: self.recent,
            processKeyEvents: false
          })

          self.recentCollectionView.render()
          self.recent.fetch({
            data: {
              recent: true
            }
          }).then(function () {
            if (self.recent.length) {
              self.$('.js-recentwrapper').fadeIn('slow')
            }
          })
        }
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.SocialAction = Iznik.View.extend({
    tagName: 'li',

    template: 'modtools_socialactions_one',

    render: function () {
      var self = this
      var p = Iznik.View.prototype.render.call(this)
      p.then(function (self) {
        // Show buttons for the remaining Facebook groups/pages that haven't shared this.
        //
        // We have a list of those inside each group in our session.
        self.$('.js-buttons').empty()
        var sharelist = []
        var uids = self.model.get('uids')
        var groups = Iznik.Session.get('groups')

        _.each(uids, function (uid) {
          groups.each(function (group) {
            if (group.get('type') == 'Freegle') {
              var facebooks = group.get('facebook')

              if (facebooks) {
                _.each(facebooks, function (facebook) {
                  if (facebook.uid == uid) {
                    // This is the one we would want to share on.
                    sharelist.push(new Iznik.Model(facebook))
                  }
                })
              }
            }
          })
        })

        self.shares = new Iznik.Collection(sharelist)
        self.shares.comparator = 'name'
        self.shares.sort()

        self.shares.each(function (share) {
          // Page shares happen on the server.  Group ones don't so need a Facebook session.
          if (share.get('type') == 'Page' || (share.get('type') == 'Group' && Iznik.Session.hasFacebook())) {
            var v = new Iznik.Views.ModTools.SocialAction.FacebookGroupShare({
              model: share,
              actionid: self.model.get('id'),
              action: self.model
            })

            v.render().then(function () {
              self.$('.js-buttons').append(v.$el)
            })
          }
        })

        var v = new Iznik.Views.ModTools.SocialAction.FacebookPageHide({
          actionid: self.model.get('id'),
          action: self.model,
          hideWhenDone: self.$el,
          shares: self.shares
        })

        v.render().then(function () {
          self.$('.js-buttons').append(v.$el)
        })
      })

      return (this)
    }
  })

  Iznik.Views.ModTools.SocialAction.FacebookGroupShare = Iznik.View.extend({
    template: 'modtools_socialactions_facebookshare',

    tagName: 'li',

    events: {
      'click .js-share': 'share'
    },

    share: function () {
      var self = this

      $.ajax({
        url: API + 'socialactions',
        type: 'POST',
        data: {
          id: self.options.actionid,
          uid: self.model.get('uid'),
          action: 'Do'
        }
      })

      self.$el.fadeOut('slow')
    }
  })

  Iznik.Views.ModTools.SocialAction.FacebookPageHide = Iznik.View.extend({
    template: 'modtools_socialactions_facebookhide',

    tagName: 'li',

    events: {
      'click .js-hide': 'hide'
    },

    hide: function () {
      var self = this

      self.options.shares.each(function (share) {
        $.ajax({
          url: API + 'socialactions',
          type: 'POST',
          data: {
            id: self.options.actionid,
            uid: share.get('uid'),
            action: 'Hide'
          }
        })
      })

      self.options.hideWhenDone.fadeOut('slow')
    }
  })

  Iznik.Views.ModTools.SocialAction.Request = Iznik.View.Timeago.extend({
    template: 'modtools_socialactions_request',

    tagName: 'li',

    events: {
      'click .js-delete': 'deleteIt'
    },

    deleteIt: function () {
      var self = this
      this.model.destroy().then(self.$el.fadeOut('slow'))
    }
  })

  Iznik.Views.ModTools.SocialAction.Outstanding = Iznik.View.Timeago.extend({
    template: 'modtools_socialactions_outstanding',

    tagName: 'li',

    events: {
      'click .js-delete': 'deleteIt',
      'click .js-sent': 'sent'
    },

    sent: function () {
      var self = this
      this.model.completed().then(self.$el.fadeOut('slow'))
    },

    deleteIt: function () {
      var self = this
      this.model.destroy().then(self.$el.fadeOut('slow'))
    }
  })

  Iznik.Views.ModTools.SocialAction.Recent = Iznik.View.extend({
    tagName: 'li',

    template: 'modtools_socialactions_recent'
  })
})