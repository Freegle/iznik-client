define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'iznik/views/pages/pages',
  'iznik/views/pages/user/pages'
], function($, _, Backbone, Iznik) {
  Iznik.Views.User.Pages.Jobs = Iznik.Views.Page.extend({
    template: "user_jobs_main",

    events: {
      'click .js-search': 'search'
    },

    search: function() {
      var newloc = this.$('.js-pc').val()
      Router.navigate('/jobs/' + newloc, true)
    },

    render: function() {
      var self = this;
      console.log("Load jobs", self.options)

      // Load the AdView scripts.
      var newScript = document.createElement("script");
      newScript.src = "https://adview.online/js/pub/tracking.js?publisher=2053&channel=&source=feed";
      newScript.onload = function() {
        console.log("Loaded script")
        init(); // window.onload isn't called so we do it manually.
        var me = Iznik.Session.get('me');
        var pc = me && me.city ? me.city : null
        pc = self.options.postcode ? self.options.postcode : pc

        if (pc) {
          self.model = new Iznik.Model({
            postcode: pc
          })

          $.ajax({
            url: '/adview.php',
            data: {
              location: pc
            }, success: function(ret) {
              var jobs = ret.hasOwnProperty('data') ? ret.data : null;

              if (jobs) {
                _.each(jobs, function(job) {
                  var v = new Iznik.Views.User.Job({
                    model: new Iznik.Model(job)
                  })
                  v.render()
                  self.$('.js-jobs').append(v.$el)
                })
              }
            }
          });
        }

        Iznik.Views.Page.prototype.render.call(self)
      }

      newScript.onerror = function(e) {
        self.model = new Iznik.Model({
          adblock: true
        });
        Iznik.Views.Page.prototype.render.call(self)
      }

      document.head.appendChild(newScript);

      return(Iznik.resolvedPromise(this));
    }
  });

  Iznik.Views.User.Job = Iznik.View.extend({
    template: "user_jobs_one",
  })
});