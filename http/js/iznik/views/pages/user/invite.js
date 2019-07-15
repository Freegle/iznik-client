define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'iznik/models/message',
  'iznik/models/user/search',
  'iznik/models/invitation',
  'iznik/views/group/communityevents',
  'iznik/views/pages/pages',
  'iznik/views/user/noticeboard',
], function ($, _, Backbone, Iznik) {
  Iznik.Views.User.Pages.Invite = Iznik.Views.Page.extend({
    template: 'user_invite_main',

    noback: true,

    events: {
      'click .js-invite': 'doInvite',
      'click .js-putupposter': 'getLocation',
      'click .js-cards': 'cards'
    },

    cards: function() {
        (new Iznik.Views.User.BusinessCards({
          noPoster: true
        })).render();
    },

    doInvite: function () {
      var self = this
      var email = self.$('.js-inviteemail').val()

      if (Iznik.isValidEmailAddress(email)) {
        $.ajax({
          url: API + 'invitation',
          type: 'POST',
          headers: {
            'X-HTTP-Method-Override': 'PUT'
          },
          data: {
            email: email
          },
          complete: function () {
            self.$('.js-inviteemail').val('')
            self.$('.js-showinvite').slideUp('slow');
            (new Iznik.Views.User.Invited()).render()
            self.invitations.fetch()
            self.$('.js-invitewrapper').show()
          }
        })
      }
    },

    noticeboardId: null,

    getLocation: function () {
      console.log("Get location")
      var self = this
      self.wait = new Iznik.Views.PleaseWait()
      self.wait.render()

      navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this))
    },

    gotLocation: function (position) {
      var self = this

      $.ajax({
        type: 'POST',
        url: API + 'noticeboard',
        data: {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        }, success: function (ret) {
          if (self.wait) {
            self.wait.close();
          }
          if (ret.ret == 0 && ret.id) {
            self.noticeboardId = ret.id
            var v = new Iznik.Views.User.Noticeboard.Added({
              model: new Iznik.Model({
                id: self.noticeboardId,
                lat: position.coords.latitude,
                lng: position.coords.longitude
              })
            })

            v.render();
          }
        }, error: function() {
          if (self.wait) {
            self.wait.close();
          }
        }
      })
    },


    render: function () {
      var self = this

      var p = Iznik.Views.Page.prototype.render.call(this, {
        noSupporters: true
      })

      p.then(function (self) {
        // List invitations.
        self.invitations = new Iznik.Collections.Invitations()

        self.collectionView = new Backbone.CollectionView({
          el: self.$('.js-list'),
          modelView: Iznik.Views.User.Invitation,
          collection: self.invitations,
          processKeyEvents: false
        })

        self.collectionView.render()
        self.invitations.fetch().then(function () {
          if (self.invitations.length > 0) {
            self.$('.js-invitewrapper').fadeIn('slow')
          }
        })
      })

      return (p)
    }
  })

  Iznik.Views.User.Invited = Iznik.Views.Modal.extend({
    template: 'user_home_invited'
  })

  Iznik.Views.User.Invitation = Iznik.View.Timeago.extend({
    template: 'user_invite_one',
    tagName: 'li'
  })
})