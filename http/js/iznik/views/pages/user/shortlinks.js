define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'moment',
  'googlemaps-js-rich-marker',
  'iznik/models/shortlinks',
  'iznik/views/pages/pages',
  'iznik/views/dashboard',
  'typeahead'
], function ($, _, Backbone, Iznik, moment, r) {
  var RichMarker = r.default.RichMarker;

  Iznik.Views.User.Pages.Shortlinks = Iznik.Views.Page.extend({
    template: 'user_shortlinks_main',

    events: {
      'click .js-create': 'create'
    },

    groupid: null,

    create: function() {
      var self = this;

      var name = self.$('.js-name').val();
      console.log("Create", self.groupid, name);

      if (self.groupid && /[a-zA-Z0-9]/.test(name)) {
         $.ajax({
           url: API + 'shortlink',
           type: 'POST',
           data: {
             name: name,
             groupid: self.groupid
           }, success: function(ret) {
              if (ret.ret === 0) {
                self.$('.js-success').fadeIn('slow')
              } else {
                self.$('.js-error').fadeIn('slow')
              }
           }, error: function() {
             self.$('.js-error').fadeIn('slow')
           }
         })
      }
    },

    visible: function(model) {
      return(model.get('type') === 'Group');
    },

    substringMatcher: function (strs) {
      return function findMatches (q, cb) {
        var matches, substringRegex

        // an array that will be populated with substring matches
        matches = []

        // regex used to determine if a string contains the substring `q`
        var substrRegex = new RegExp(q, 'i')

        // iterate through the pool of strings and for any string that
        // contains the substring `q`, add it to the `matches` array
        $.each(strs, function (i, str) {
          if (substrRegex.test(str)) {
            matches.push(str)
          }
        })

        cb(matches)
      }
    },

    groupNames: [],

    render: function () {
      var self = this;

      var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
        // Group search uses a typehead.
        $.ajax({
          type: 'GET',
          url: API + 'groups',
          data: {
            grouptype: 'Freegle'
          }, success: function (ret) {
            self.groups = ret.groups
            self.groupNames = []
            _.each(self.groups, function (group) {
              self.groupNames.push(group.nameshort)
            })

            self.typeahead = self.$('.js-searchgroupinp').typeahead({
              minLength: 2,
              hint: false,
              highlight: true
            }, {
              name: 'groups',
              source: self.substringMatcher(self.groupNames)
            })

            self.$('.js-searchgroupinp').bind('typeahead:select', function (ev, suggestion) {
              _.each(self.groups, function(group) {
                if (group.nameshort.toLowerCase() == suggestion.toLowerCase()) {
                  self.groupid = group.id
                }
              })
            })
          }
        })

        self.allLinks = new Iznik.Collections.Shortlink();

        self.allLinks.comparator = function(a, b) {
          var aname = a.get('nameshort');
          var bname = b.get('nameshort');
          var ret = 0;

          if (aname && bname) {
            ret = Iznik.strcmp(aname.toLowerCase(), bname.toLowerCase())
          }

          return(ret);
        }

        self.collectionView = new Backbone.CollectionView({
          el: self.$('.js-linklist'),
          modelView: Iznik.Views.User.Pages.Shortlinks.Group,
          modelViewOptions: {
            collection: self.allLinks,
            page: self
          },
          collection: self.allLinks,
          processKeyEvents: false,
          visibleModelsFilter: _.bind(self.visible, self),
        });

        self.collectionView.render();

        self.allLinks.fetch().then(function() {
          self.$('.js-loading').hide();
        });
      });

      return (p);
    },
  });

  Iznik.Views.User.Pages.Shortlinks.Group = Iznik.View.extend({
    className: 'li',

    template: 'user_shortlinks_group'
  });

  Iznik.Views.User.Pages.Shortlink = Iznik.Views.Page.extend({
    template: 'user_shortlinks_single',

    render: function () {
      var self = this;

      self.model = new Iznik.Models.Shortlink({
        id: self.options.id
      });

      var p = self.model.fetch();

      p.then(function() {
        Iznik.Views.Page.prototype.render.call(self).then(function () {
          var history = self.model.get('clickhistory');
          if (history.length === 1) {
            // The group doesn't work right unless we have two entries.
            var m = new moment(history[0].date);
            m = m.subtract(1, 'days');
            history.unshift({
              date: m.toISOString(),
              count: 0
            });
          }

          var coll = new Iznik.Collections.DateCounts(history);
          var graph = new Iznik.Views.DateGraph({
            target: self.$('.js-clickhistory').get()[0],
            data: coll,
            title: 'Clicks'
          });

          graph.render();
        });
      })

      return (p);
    },
  });
});
