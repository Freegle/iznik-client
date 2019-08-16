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
      console.log("Newloc", newloc)
      Router.navigate('/jobs/' + newloc, true)
    },

    render: function() {
      var self = this;
      var me = Iznik.Session.get('me');
      var pc = me && me.settings && me.settings.mylocation && me.settings.mylocation.name ? me.settings.mylocation.name : null
      pc = pc ? pc.substring(0, pc.indexOf(' ')) : null;
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

      var ret = Iznik.Views.Page.prototype.render.call(this)

      return(ret);
    }
  });

  Iznik.Views.User.Job = Iznik.View.extend({
    template: "user_jobs_one",
    events: {
      'click': 'open'
    },
    open: function() {
      window.open(this.model.get('url'))
    }
  })
});