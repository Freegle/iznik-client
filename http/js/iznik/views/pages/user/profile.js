define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'moment',
  'iznik/views/user/user',
  'iznik/views/user/message',
  'iznik/views/pages/pages'
], function ($, _, Backbone, Iznik, moment) {
  Iznik.Views.User.Pages.Profile = Iznik.Views.Page.extend({
    template: 'user_profile_main',

    events: {},

    render: function () {
      var self = this

      var p = self.model.fetch({
        data: {
          info: true
        }
      });

      p.then(function() {
        Iznik.Views.Page.prototype.render.call(self).then(function () {
          var mom = new moment(self.model.get('added'))
          self.$('.js-since').html(mom.format('Do MMMM YYYY'))

          self.$('.js-replytime').html(Iznik.formatDuration(self.model.get('info').replytime));

          // Cover image
          var cover = self.model.get('coverimage') ? self.model.get('coverimage') : '/images/wallpaper.png'
          self.$('.coverphoto').css('background-image', 'url(' + cover + ')');

          if (Iznik.Session.get('me').id != self.model.get('id')) {
            self.$('.js-dm').show();
          }

          self.ratings1 = new Iznik.Views.User.Ratings({
            model: self.model
          });

          self.ratings1.template = 'user_profile_ratings'

          self.ratings1.render();
          self.$('.js-ratings1').html(self.ratings1.$el);

          self.ratings2 = new Iznik.Views.User.Ratings({
            model: self.model
          });

          self.ratings2.template = 'user_profile_ratings'

          self.ratings2.render();
          self.$('.js-ratings2').html(self.ratings2.$el);

          self.$('.js-abouttext').html(Iznik.twem(self.$('.js-abouttext').html()));

          var info = self.model.get('info');

          self.collection = new Iznik.Collections.Message(null, {
            modtools: false,
            collection: 'Approved'
          });

          if (info.openoffers) {
            self.ocv = new Backbone.CollectionView({
              el: self.$('.js-offerlist'),
              modelView: Iznik.Views.User.Message.Replyable,
              modelViewOptions: {
                collection: self.collection,
                page: self
              },
              collection: self.collection,
              visibleModelsFilter: _.bind(self.offerFilter, self),
              processKeyEvents: false
            });

            self.ocv.render();
          }

          if (info.openwanteds) {
            self.wcv = new Backbone.CollectionView({
              el: self.$('.js-wantedlist'),
              modelView: Iznik.Views.User.Message.Replyable,
              modelViewOptions: {
                collection: self.collection,
                page: self
              },
              collection: self.collection,
              visibleModelsFilter: _.bind(self.wantedFilter, self),
              processKeyEvents: false
            });

            self.wcv.render();
          }

          self.collection.fetch({
            data: {
              fromuser: self.model.get('id'),
              limit: 1000,
              age: info.openage
            }
          });
        })
      })

      return (p)
    },

    offerFilter: function(model) {
      return (model.get('outcomes').length === 0 && model.get('type') == 'Offer');
    },

    wantedFilter: function(model) {
      return (model.get('outcomes').length === 0 && model.get('type') == 'Wanted');
    },
  })
})
