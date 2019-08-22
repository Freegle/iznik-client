define([
  'jquery',
  'underscore',
  'backbone',
  'moment',
  'iznik/base',
  'iznik/modtools',
  'iznik/models/admin',
  'iznik/views/pages/pages'
], function ($, _, Backbone, moment, Iznik) {
  Iznik.Views.ModTools.Pages.Admins = Iznik.Views.Page.extend({
    modtools: true,

    template: 'modtools_admins_main',

    events: {
      'click .js-send': 'send',
      'change .js-groupselect': 'checkSend'
    },

    checkSend: function () {
      var groupid = this.groupSelect.get()

      if (groupid > 0 || (Iznik.Session.isAdminOrSupport() && groupid == -2)) {
        this.$('.js-send').removeClass('disabled')
      } else {
        this.$('.js-send').addClass('disabled')
      }
    },

    send: function (e) {
      e.preventDefault()
      e.stopPropagation()

      var groupid = this.groupSelect.get();
      var admin = new Iznik.Models.Admin({
        groupid: groupid > 0 ? groupid : null,
        subject: this.$('#js-subject').val(),
        text: this.$('#js-text').val()
      })

      if (admin.get('subject') && admin.get('text')) {
        admin.save().then(function () {
          (new Iznik.Views.ModTools.Pages.Admins.Sent()).render()
        })
      }
    },

    fetchPrevious: function () {
      var self = this
      var groupid = self.groupSelect.get();

      if (groupid > 0) {
        self.collection.fetch({
          data: {
            groupid: groupid
          },
          remove: true
        }).then(function () {
          self.$('.js-admincount').html('(' + self.collection.length + ')')
        })
      }
    },

    selectChange: function() {
      var self = this;

      var groupid = self.groupSelect.get();
      console.log("Group", groupid);

      if (groupid > 0) {
        self.$('.js-suggested').hide();
      } else {
        self.$('.js-suggested').fadeIn('slow')
      }

      self.fetchPrevious();
    },

    render: function () {
      var self = this

      var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
        self.collection = new Iznik.Collections.Admin()
        self.pendingcollection = new Iznik.Collections.Admin()

        self.collectionView = new Backbone.CollectionView({
          el: $('#adminlist'),
          modelView: Iznik.Views.ModTools.Pages.Admins.Previous,
          collection: self.collection,
          processKeyEvents: false,
        })

        self.collectionView.render()

        self.pendingCollectionView = new Backbone.CollectionView({
          el: $('#pendingadminlist'),
          modelView: Iznik.Views.ModTools.Pages.Admins.Pending,
          collection: self.pendingcollection,
          processKeyEvents: false
        })

        self.pendingCollectionView.render()
        self.pendingcollection.fetch().then(function() {
          if (self.pendingcollection.length === 0) {
            self.$('.js-nopending').show();
          }
        });

        self.groupSelect = new Iznik.Views.Group.Select({
          systemWide: Iznik.Session.isAdminOrSupport(), // Admin/Support can create suggested admins
          all: false,
          mod: true,
          choose: true
        })

        self.listenTo(self.groupSelect, 'change', self.selectChange)

        self.listenToOnce(self.groupSelect, 'completed', function () {
          self.fetchPrevious()
        })

        self.groupSelect.render().then(function () {
          self.$('.js-groupselect').html(self.groupSelect.el)
        })
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.Pages.Admins.Pending = Iznik.View.Timeago.extend({
    template: 'modtools_admins_pending',
    tagName: 'li',
    className: 'panel panel-default',

    events: {
      'click .js-save': 'saveChanges',
      'click .js-delete': 'deleteIt',
      'click .js-approve': 'approveIt'
    },

    saveChanges: function() {
      var self = this;
      var subject = self.$('.js-subject').val();
      var body = self.$('.js-body').val();
      self.model.save({
        id: self.model.get('id'),
        subject: subject,
        text: body
      }, {
        patch: true
      });
    },

    approveIt: function() {
      var self = this;
      var subject = self.$('.js-subject').val();
      var body = self.$('.js-body').val();
      self.model.save({
        id: self.model.get('id'),
        pending: 0
      }, {
        patch: true
      }).then(function () {
        self.$el.fadeOut('slow');
      });
    },

    deleteIt: function() {
      var self = this;
      self.model.delete().then(function () {
        self.$el.fadeOut('slow');
      });
    },

    render: function() {
      var self = this;

      if (self.model.get('groupid')) {
        var group = Iznik.Session.getGroup(self.model.get('groupid'));
        self.model.set('group', group.attributes);
      }

      var p = Iznik.View.Timeago.prototype.render.call(self);

      return(p);
    }
  })

  Iznik.Views.ModTools.Pages.Admins.Previous = Iznik.View.Timeago.extend({
    template: 'modtools_admins_previous',
    tagName: 'li',
    className: 'panel panel-default'
  })

  Iznik.Views.ModTools.Pages.Admins.Sent = Iznik.Views.Modal.extend({
    template: 'modtools_admins_sent'
  })
})