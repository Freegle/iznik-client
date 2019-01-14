define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'moment',
  'iznik/views/user/user',
  'iznik/views/pages/pages'
], function ($, _, Backbone, Iznik, moment) {
  Iznik.Views.User.Pages.Profile = Iznik.Views.Page.extend({
    template: 'user_profile_main',

    events: {},

    render: function () {
      var self = this

      console.log("Fetch", self.model)
      var p = self.model.fetch({
        data: {
          info: true
        }
      });

      p.then(function() {
        console.log("Fetched");
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
        })
      })

      return (p)
    },
  })
})
