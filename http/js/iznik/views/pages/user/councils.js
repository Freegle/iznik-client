define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'iznik/views/chat/chat',
  // 'iznik/views/user/visualise',
  'iznik/views/teams',
  'iznik/models/donations',
  'iznik/views/pages/pages',
  'iznik/views/group/select',
  'iznik/views/supportus',
  'jquery.dd'
], function($, _, Backbone, Iznik, ChatHolder) {
  Iznik.Views.User.Pages.Councils = Iznik.Views.Page.extend({
    template: "user_councils_main",

    render: function() {
      var self = this;

      var p = Iznik.Views.Page.prototype.render.call(self);
      p.then(function() {
        var v = new Iznik.Views.User.Pages.Councils.Contents();
        v.render();
        $('#js-rightsidebar').html(v.$el);

        if (self.options.section) {
          $(window).scrollTo($('#js-' + self.options.section), 1000, {
            offset: {
              top: -60
            }
          })
        }
      });

      return(p);
    }
  });

  Iznik.Views.User.Pages.Councils.Main = Iznik.Views.Page.extend({
    template: "user_councils_main",

    render: function() {
      var self = this;

      var p = Iznik.Views.Page.prototype.render.call(self);
      p.then(function() {
        var v = new Iznik.Views.User.Pages.Councils.Contents();
        v.render().then(function() {
          self.$('.js-contents').html(v.$el);
          self.$('.col-sm-offset-2').removeClass('col-sm-offset-2');
        });
      });

      return(p);
    }
  });
  
  Iznik.Views.User.Pages.Councils.Overview = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_overview",
  });

  Iznik.Views.User.Pages.Councils.Volunteers = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_volunteers",
  });

  Iznik.Views.User.Pages.Councils.KeyLinks = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_keylinks",
  });

  Iznik.Views.User.Pages.Councils.WorkBest = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_workbest",
  });

  Iznik.Views.User.Pages.Councils.Graphics = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_graphics",
  });

  Iznik.Views.User.Pages.Councils.PhotosVideos = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_photosvideos",
  });

  Iznik.Views.User.Pages.Councils.Posters = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_posters",
  });

  Iznik.Views.User.Pages.Councils.Banners = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_banners",
  });

  Iznik.Views.User.Pages.Councils.BusinessCards = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_businesscards",
  });

  Iznik.Views.User.Pages.Councils.Media = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_media",
  });

  Iznik.Views.User.Pages.Councils.SocialMedia = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_socialmedia",
  });

  Iznik.Views.User.Pages.Councils.PressRelease = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_pressrelease",
  });

  Iznik.Views.User.Pages.Councils.UserStories = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_userstories",
  });

  Iznik.Views.User.Pages.Councils.OtherCouncils = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_othercouncils",
  });

  Iznik.Views.User.Pages.Councils.BestPractice = Iznik.Views.User.Pages.Councils.extend({
    template: "user_councils_bestpractice",
  });

  Iznik.Views.User.Pages.Councils.Contents = Iznik.View.extend({
    template: 'user_councils_contents'
  });
});