define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'iznik/models/yahoo/user',
  'iznik/modtools',
  'iznik/views/pages/pages',
  'iznik/views/pages/modtools/messages',
  'iznik/views/infinite',
  'typeahead',
  'iznik/views/group/select'
], function ($, _, Backbone, Iznik, IznikYahooUsers) {
  Iznik.Views.ModTools.Pages.PendingMessages = Iznik.Views.Infinite.extend({
    modtools: true,

    template: 'modtools_messages_pending_main',

    retField: 'messages',

    countsChanged: function () {
      this.groupSelect.render()
    },

    render: function () {
      var p = Iznik.Views.Infinite.prototype.render.call(this)
      p.then(function (self) {
        var v = new Iznik.Views.Help.Box()
        v.template = 'modtools_messages_pending_help'
        v.render().then(function (v) {
          self.$('.js-help').html(v.el)
        })

        self.groupSelect = new Iznik.Views.Group.Select({
          systemWide: false,
          all: true,
          mod: true,
          counts: ['pending', 'pendingother'],
          id: 'pendingGroupSelect'
        })

        self.collection = new Iznik.Collections.Message(null, {
          modtools: true,
          groupid: self.selected,
          group: Iznik.Session.get('groups').get(self.selected),
          collection: 'Pending'
        })

        // CollectionView handles adding/removing/sorting for us.
        self.collectionView = new Backbone.CollectionView({
          el: self.$('.js-list'),
          modelView: Iznik.Views.ModTools.Message.Pending,
          modelViewOptions: {
            collection: self.collection,
            page: self
          },
          collection: self.collection,
          processKeyEvents: false
        })

        self.collectionView.render()

        self.listenTo(self.groupSelect, 'selected', function (selected) {
          self.selected = selected

          // We haven't fetched anything for this group yet.
          self.lastFetched = null
          self.context = null

          self.fetch({
            groupid: self.selected > 0 ? self.selected : null
          })
        })

        // Render after the listen to as they are called during render.
        self.groupSelect.render().then(function (v) {
          self.$('.js-groupselect').html(v.el)
        })

        // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
        // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
        self.listenTo(Iznik.Session, 'pendingcountschanged', _.bind(self.countsChanged, self))
        self.listenTo(Iznik.Session, 'pendingcountsotherchanged', _.bind(self.countsChanged, self))

        // Nag if we have Freegle groups still on Yahoo.
        var groups = Iznik.Session.get('groups')
        var onyahoo = []
        groups.each(function (group) {
          if (group.get('type') == 'Freegle' && group.get('onyahoo') && group.get('onmap')) {
            onyahoo.push(group.get('namedisplay'))
          }
        })
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.Message.Pending = Iznik.Views.ModTools.Message.extend({
    tagName: 'li',

    template: 'modtools_messages_pending_message',

    collectionType: 'Pending',

    events: {
      'click .js-viewsource': 'viewSource',
      'click .js-excludelocation': 'excludeLocation',
      'click .js-rarelyused': 'rarelyUsed',
      'click .js-savesubj': 'saveSubject',
      'click .js-saveplatsubj': 'savePlatSubject',
      'click .js-editnotstd': 'editNotStd',
      'click .js-spam': 'spam'
    },

    editNotStd: function () {
      var self = this

      var v = new Iznik.Views.ModTools.StdMessage.Edit({
        model: this.model
      })

      this.listenToOnce(this.model, 'editsucceeded', function () {
        self.model.fetch().then(function () {
          self.render()
        })
      })

      v.render()
    },

    rendering: null,

    render: function () {
      var self = this

      self.model.set('mapicon', '/images/mapmarker.gif')

      // Get a zoom level for the map.
      _.each(self.model.get('groups'), function (group) {
        self.model.set('mapzoom', group.settings.hasOwnProperty('map') ? group.settings.map.zoom : 9)
      })

      if (!self.rendering) {
        self.rendering = new Promise(function (resolve, reject) {
          var p = Iznik.Views.ModTools.Message.prototype.render.call(self)
          p.then(function (self) {
            // For platform messages we don't need to suggest an alternative subject, and the edit
            // box isn't free-form.
            if (self.model.get('sourceheader') == 'Platform' &&
              self.model.get('item') &&
              self.model.get('location') &&
              self.model.get('postcode')) {
              self.$('.js-type').val(self.model.get('type'))
              self.$('.js-item').val(self.model.get('item').name)
              self.$('.js-location').val(self.model.get('location').name)
              self.$('.js-postcode').html(self.model.get('postcode').name)

              if (self.model.get('area')) {
                // Some groups disable areas.
                self.$('.js-area').html(self.model.get('area').name)
              }

              self.$('.js-location').typeahead({
                minLength: 2,
                hint: false,
                highlight: true
              }, {
                name: 'postcodes',
                source: _.bind(self.postcodeSource, self)
              })
            } else {
              // Set the suggested subject here to avoid escaping issues.  Highlight it if it's different
              var sugg = self.model.get('suggestedsubject')
              if (sugg && sugg.toLocaleLowerCase() != self.model.get('subject').toLocaleLowerCase()) {
                self.$('.js-subject').closest('.input-group').addClass('subjectdifference')
              } else {
                self.$('.js-subject').closest('.input-group').removeClass('subjectdifference')
              }

              self.$('.js-subject').val(sugg ? sugg : self.model.get('subject'))
            }

            _.each(self.model.get('groups'), function (group) {
              var mod = new Iznik.Model(group)

              // This only handles a message on one group.
              if (group.onyahoo) {
                self.$('.js-maybepluginonly').addClass('js-pluginonly')
              } else {
                self.$('.js-maybepluginonly').removeClass('js-pluginonly').show()
              }

              // Add in the message, because we need some values from that
              mod.set('message', self.model.toJSON())

              var v = new Iznik.Views.ModTools.Message.Pending.Group({
                model: mod
              })
              v.render().then(function (v) {
                self.$('.js-grouplist').append(v.el)
              })

              var mod = new Iznik.Models.ModTools.User(self.model.get('fromuser'))
              mod.set('groupid', group.id)

              var v = new Iznik.Views.ModTools.User({
                model: mod,
                groupid: group.id
              })

              v.render().then(function (v) {
                self.$('.js-user').html(v.el)
              })

              if (group.type == 'Freegle') {
                // The FD settings.

                var v = new Iznik.Views.ModTools.User.FreegleMembership({
                  model: new Iznik.Model(self.model.get('fromuser')),
                  groupid: group.id
                })

                v.render().then(function (v) {
                  self.$('.js-freegleinfo').append(v.el)
                })
              }

              if (group.onyahoo) {
                // The Yahoo part of the user
                var fromemail = self.model.get('envelopefrom') ? self.model.get('envelopefrom') : self.model.get('fromaddr')
                var mod = IznikYahooUsers.findUser({
                  email: fromemail,
                  group: group.nameshort,
                  groupid: group.id
                })

                mod.fetch().then(function () {
                  var v = new Iznik.Views.ModTools.Yahoo.User({
                    model: mod
                  })
                  v.render().then(function (v) {
                    self.$('.js-yahoo').html(v.el)
                  })
                })
              }

              self.addOtherInfo()

              self.$('.js-outcometime').timeago()

              // Add any attachments.
              self.$('.js-attlist').empty()
              var photos = self.model.get('attachments')

              var v = new Iznik.Views.User.Message.Photos({
                collection: new Iznik.Collection(photos),
                message: self.model,
                showAll: true
              })

              v.render().then(function () {
                self.$('.js-attlist').append(v.el)
              })

              // Add the default standard actions.
              var configs = Iznik.Session.get('configs')
              var sessgroup = Iznik.Session.get('groups').get(group.id)
              var config = configs && sessgroup ? configs.get(sessgroup.get('configid')) : undefined  // CC

              if (!_.isUndefined(config) &&
                config.get('subjlen') &&
                self.model.get('suggestedsubject') &&
                (self.model.get('suggestedsubject').length > config.get('subjlen'))) {
                // This subject is too long, and we want to flag that.
                self.$('.js-subject').closest('.input-group').addClass('subjectdifference')
              }

              if (self.model.get('heldby')) {
                // Message is held - just show Release button.
                new Iznik.Views.ModTools.StdMessage.Button({
                  model: new Iznik.Model({
                    title: 'Release',
                    action: 'Release',
                    message: self.model,
                    config: config
                  })
                }).render().then(function (v) {
                  self.$('.js-stdmsgs').append(v.el)
                })
              } else {
                // Message is not held - we see all buttons.
                new Iznik.Views.ModTools.StdMessage.Button({
                  model: new Iznik.Model({
                    title: 'Approve',
                    action: 'Approve',
                    message: self.model,
                    config: config
                  })
                }).render().then(function (v) {
                  self.$('.js-stdmsgs').append(v.el)
                })

                new Iznik.Views.ModTools.StdMessage.Button({
                  model: new Iznik.Model({
                    title: 'Reject',
                    action: 'Reject',
                    message: self.model,
                    config: config
                  })
                }).render().then(function (v) {
                  self.$('.js-stdmsgs').append(v.el)
                })

                new Iznik.Views.ModTools.StdMessage.Button({
                  model: new Iznik.Model({
                    title: 'Delete',
                    action: 'Delete',
                    message: self.model,
                    config: config
                  })
                }).render().then(function (v) {
                  self.$('.js-stdmsgs').append(v.el)
                })

                new Iznik.Views.ModTools.StdMessage.Button({
                  model: new Iznik.Model({
                    title: 'Hold',
                    action: 'Hold',
                    message: self.model,
                    config: config
                  })
                }).render().then(function (v) {
                  self.$('.js-stdmsgs').append(v.el)
                })

                new Iznik.Views.ModTools.StdMessage.Button({
                  model: new Iznik.Model({
                    title: 'Spam',
                    action: 'Spam',
                    message: self.model
                  })
                }).render().then(function (v) {
                  self.$('.js-stdmsgs').append(v.el)
                })
              }

              if (config) {
                self.checkMessage(config)
                self.showRelated()

                // Add the other standard messages, in the order requested.
                var sortmsgs = Iznik.orderedMessages(config.get('stdmsgs'), config.get('messageorder'))
                var anyrare = false

                _.each(sortmsgs, function (stdmsg) {
                  if (_.contains(['Approve', 'Reject', 'Delete', 'Leave', 'Edit'], stdmsg.action)) {
                    stdmsg.message = self.model
                    var v = new Iznik.Views.ModTools.StdMessage.Button({
                      model: new Iznik.Models.ModConfig.StdMessage(stdmsg),
                      config: config
                    })

                    if (stdmsg.rarelyused) {
                      anyrare = true
                    }

                    v.render().then(function (v) {
                      self.$('.js-stdmsgs').append(v.el)

                      if (stdmsg.rarelyused) {
                        $(v.el).hide()
                      }
                    })
                  }
                })

                if (!anyrare) {
                  self.$('.js-rarelyholder').hide()
                }
              }

              // If the message is held or released, we re-render, showing the appropriate buttons.
              self.listenToOnce(self.model, 'change:heldby', self.render)
            })

            self.$('.timeago').timeago()
            self.$el.fadeIn('slow')

            self.listenToOnce(self.model, 'approved rejected deleted', function () {
              self.$el.fadeOut('slow')
            })

            resolve()
            self.rendering = null
          })
        })
      }

      return (self.rendering)
    }
  })

  Iznik.Views.ModTools.Message.Pending.Group = Iznik.View.Timeago.extend({
    template: 'modtools_messages_pending_group'
  })

  Iznik.Views.ModTools.StdMessage.Pending.Approve = Iznik.Views.ModTools.StdMessage.Modal.extend({
    template: 'modtools_messages_pending_approve',

    events: {
      'click .js-send': 'send'
    },

    send: function () {
      this.model.approve(
        this.options.stdmsg.get('subjpref') ? this.$('.js-subject').val() : null,
        this.options.stdmsg.get('subjpref') ? this.$('.js-text').val() : null,
        this.options.stdmsg ? this.options.stdmsg.get('id') : null
      )
    },

    render: function () {
      return (this.expand())
    }
  })

  Iznik.Views.ModTools.StdMessage.Pending.Reject = Iznik.Views.ModTools.StdMessage.Modal.extend({
    template: 'modtools_messages_pending_reject',

    events: {
      'click .js-send': 'send'
    },

    send: function () {
      var subj = this.$('.js-subject').val()
      var body = this.$('.js-text').val();

      if (subj.length > 0 && body.length) {
        this.model.reject(
          subj,
          body,
          this.options.stdmsg ? this.options.stdmsg.get('id') : null
        )
      } else {
        this.$('.js-subject').focus()
      }
    },

    render: function () {
      return (this.expand())
    }
  })

  Iznik.Views.ModTools.Message.NotOnYahoo = Iznik.Views.Modal.extend({
    template: 'modtools_messages_pending_notonyahoo'
  })
})
