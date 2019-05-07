define([
  'jquery',
  'underscore',
  'backbone',
  'moment',
  'iznik/base',
  'backform',
  'iznik/views/modal',
  'bootstrap-switch',
  'eonasdan-bootstrap-datetimepicker'
], function ($, _, Backbone, moment, Iznik, Backform) {
  Iznik.Views.ModTools.User = Iznik.View.extend({
    template: 'modtools_user_user',

    events: {
      'click .js-posts': 'posts',
      'click .js-offers': 'offers',
      'click .js-takens': 'takens',
      'click .js-wanteds': 'wanteds',
      'click .js-receiveds': 'receiveds',
      'click .js-modmails': 'modmails',
      'click .js-others': 'others',
      'click .js-logs': 'logs',
      'click .js-remove': 'remove',
      'click .js-ban': 'ban',
      'click .js-purge': 'purge',
      'click .js-addcomment': 'addComment',
      'click .js-spammer': 'spammer',
      'click .js-whitelist': 'whitelist',
      'click .js-unbounce': 'unbounce',
      'click .js-showprofile': 'showProfile'
    },

    showProfile: function () {
      var self = this
      console.log('Show profile', self.options)

      require(['iznik/views/user/user'], function () {
        var v = new Iznik.Views.UserInfo({
          model: new Iznik.Model(self.model),
          groupid: self.options.groupid
        })

        v.render()
      })
    },

    unbounce: function () {
      var self = this

      self.model.unbounce().then(function () {
        self.$('.js-bouncing').fadeOut('slow')
      })
    },

    showPosts: function (offers, wanteds, takens, receiveds, others) {
      var v = new Iznik.Views.ModTools.User.PostSummary({
        model: this.model,
        collection: this.historyColl,
        offers: offers,
        wanteds: wanteds,
        takens: takens,
        receiveds: receiveds,
        others: others
      })

      v.render()
    },

    posts: function () {
      this.showPosts(true, true, true, true, true)
    },

    offers: function () {
      this.showPosts(true, false, false, false, false)
    },

    wanteds: function () {
      this.showPosts(false, true, false, false, false)
    },

    takens: function () {
      this.showPosts(false, false, true, false, false)
    },

    receiveds: function () {
      this.showPosts(false, false, false, true, false)
    },

    others: function () {
      this.showPosts(false, false, false, false, true)
    },

    modmails: function () {
      var self = this
      var v = new Iznik.Views.ModTools.User.ModMails({
        model: self.model,
        modmailsonly: true
      })

      v.render()
    },

    whitelist: function () {
      var self = this

      var v = new Iznik.Views.ModTools.EnterReason({
        whitelist: true
      })

      self.listenToOnce(v, 'reason', function (reason) {
        $.ajax({
          url: API + 'spammers',
          type: 'POST',
          data: {
            userid: self.model.get('id'),
            reason: reason,
            collection: 'Whitelisted'
          }, success: function (ret) {
            // Now over to someone else to review this report - so remove from our list.
            self.clearSuspect()
          }
        })
      })

      v.render()
    },

    logs: function () {
      var self = this
      var v = new Iznik.Views.ModTools.User.Logs({
        model: self.model
      })

      v.render()
    },

    spammer: function () {
      var self = this
      var v = new Iznik.Views.ModTools.EnterReason()
      self.listenToOnce(v, 'reason', function (reason) {
        $.ajax({
          url: API + 'spammers',
          type: 'POST',
          data: {
            userid: self.model.get('id'),
            reason: reason,
            collection: 'PendingAdd'
          }, success: function (ret) {
            (new Iznik.Views.ModTools.User.Reported().render())
          }
        })
      })

      v.render()
    },

    remove: function () {
      // Remove membership
      var self = this

      console.log('IDs in remove', self.model.get('id'), self.model.get('userid'))

      var v = new Iznik.Views.Confirm({
        model: self.model
      })
      v.template = 'modtools_members_removeconfirm'

      self.listenToOnce(v, 'confirmed', function () {
        $.ajax({
          url: API + 'memberships',
          type: 'POST',
          headers: {
            'X-HTTP-Method-Override': 'DELETE'
          },
          data: {
            userid: self.model.get('id'),
            groupid: self.model.get('groupid')
          }, success: function (ret) {
            if (ret.ret == 0) {
              self.$el.fadeOut('slow')
              self.model.trigger('removed')
            }
          }
        })
      })

      v.render()
    },

    ban: function () {
      // Ban them - remove with appropriate flag.
      var self = this

      var v = new Iznik.Views.Confirm({
        model: self.model
      })
      v.template = 'modtools_members_banconfirm'

      self.listenToOnce(v, 'confirmed', function () {
        $.ajax({
          url: API + 'memberships',
          type: 'POST',
          headers: {
            'X-HTTP-Method-Override': 'DELETE'
          },
          data: {
            userid: self.model.get('id'),
            groupid: self.model.get('groupid'),
            ban: true
          }, success: function (ret) {
            if (ret.ret == 0) {
              self.$el.fadeOut('slow')
              self.model.trigger('removed')
            }
          }
        })
      })

      v.render()
    },

    purge: function () {
      var self = this
      var v = new Iznik.Views.Confirm({
        model: self.model
      })
      v.template = 'modtools_members_purgeconfirm'

      self.listenToOnce(v, 'confirmed', function () {
        $.ajax({
          url: API + 'user',
          type: 'POST',
          headers: {
            'X-HTTP-Method-Override': 'DELETE'
          },
          data: {
            id: self.model.get('userid')
          }, success: function (ret) {
            if (ret.ret == 0) {
              self.$el.fadeOut('slow')
              self.model.trigger('removed')
            }
          }
        })
      })

      v.render()
    },

    addComment: function () {
      var self = this

      var model = new Iznik.Models.ModTools.User.Comment({
        userid: this.model.get('id'),
        groupid: this.model.get('groupid')
      })

      var v = new Iznik.Views.ModTools.User.CommentModal({
        model: model
      })

      // When we close, update what's shown.
      this.listenToOnce(v, 'modalClosed', function () {
        self.model.fetch().then(function () {
          self.render()
        })
      })

      v.render()
    },

    render: function () {
      var p = Iznik.View.prototype.render.call(this)
      p.then(function (self) {
        self.historyColl = new Iznik.Collections.ModTools.MessageHistory()
        _.each(self.model.get('messagehistory'), function (message, index, list) {
          // Invent a unique ID which will show reposts of the same message, otherwise the collection
          // collapses them all to a single entry.
          message.id = message.id + '.' + message.arrival
          self.historyColl.add(new Iznik.Models.ModTools.User.MessageHistoryEntry(message))
        })

        self.$('.js-msgcount').html(self.historyColl.length)

        if (self.historyColl.length == 0) {
          self.$('.js-msgcount').closest('.btn').addClass('disabled')
        }

        var counts = {
          Offer: 0,
          Wanted: 0,
          Taken: 0,
          Received: 0,
          Other: 0
        }

        self.historyColl.each(function (message) {
          if (counts.hasOwnProperty(message.get('type'))) {
            counts[message.get('type')]++
          }
        })

        _.each(counts, function (value, key, list) {
          self.$('.js-' + key.toLowerCase() + 'count').html(value)
        })

        var modcount = self.model.get('modmails')
        self.$('.js-modmailcount').html(modcount)

        if (modcount > 0) {
          self.$('.js-modmailcount').closest('.badge').addClass('btn-danger')
          self.$('.js-modmailcount').addClass('white')
          self.$('.glyphicon-warning-sign').addClass('white')
        }

        var comments = self.model.get('comments')
        _.each(comments, function (comment) {
          if (comment.groupid) {
            var group = Iznik.Session.getGroup(comment.groupid)
            if (group) {
              comment.group = group.toJSON2()
            }
          }

          new Iznik.Views.ModTools.User.Comment({
            model: new Iznik.Models.ModTools.User.Comment(comment)
          }).render().then(function (v) {
            self.$('.js-comments').append(v.el)
          })
        })

        if (!comments || comments.length == 0) {
          self.$('.js-comments').hide()
        }

        var spammer = self.model.get('spammer')
        if (spammer) {
          var v = new Iznik.Views.ModTools.User.SpammerInfo({
            model: new Iznik.Model(spammer)
          })

          v.render().then(function (v) {
            self.$('.js-spammerinfo').append(v.el)
          })
        }

        if (Iznik.Session.isAdmin()) {
          self.$('.js-adminonly').removeClass('hidden')
        }

        if (Iznik.Session.isAdminOrSupport()) {
          self.$('.js-adminsupportonly').removeClass('hidden')
        }
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.User.PostSummary = Iznik.Views.Modal.extend({
    template: 'modtools_user_postsummary',

    render: function () {
      var p = Iznik.Views.Modal.prototype.render.call(this)
      p.then(function (self) {
        self.collection.each(function (message) {
          var type = message.get('type')
          var display = false

          switch (type) {
            case 'Offer':
              display = self.options.offers
              break
            case 'Wanted':
              display = self.options.wanteds
              break
            case 'Taken':
              display = self.options.takens
              break
            case 'Received':
              display = self.options.receiveds
              break
            case 'Other':
              display = self.options.others
              break
          }

          if (display) {
            var v = new Iznik.Views.ModTools.User.SummaryEntry({
              model: message
            })
            v.render().then(function (v) {
              self.$('.js-list').append(v.el)
            })
          }
        })

        self.open(null)
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.User.Merge = Iznik.Views.Modal.extend({
    template: 'modtools_user_merge',

    events: {
      'click .js-merge': 'merge'
    },

    merge: function () {
      var self = this
      var email1 = self.$('.js-email1').val()
      var email2 = self.$('.js-email2').val()
      var reason = self.$('.js-reason').val()

      if (email1.length && email2.length && reason.length) {
        $.ajax({
          url: API + 'user',
          type: 'POST',
          data: {
            'action': 'Merge',
            'email1': email1,
            'email2': email2,
            'reason': reason
          },
          success: function (ret) {
            if (ret.ret === 0) {
              self.close()
            } else {
              self.$('.js-error').html(ret.status)
              self.$('.js-errorholder').fadeIn('slow')
            }
          }
        })
      }
    },

    render: function () {
      var self = this

      var p = Iznik.Views.Modal.prototype.render.call(this)

      p.then(function () {
        $('.modal').draggable()
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.User.SummaryEntry = Iznik.View.extend({
    template: 'modtools_user_summaryentry',

    render: function () {
      var p = Iznik.View.prototype.render.call(this)
      p.then(function (self) {
        var mom = new moment(self.model.get('arrival'))
        self.$('.js-date').html(mom.format('llll'))
      })
      return (p)
    }
  })

  Iznik.Views.ModTools.User.Reported = Iznik.Views.Modal.extend({
    template: 'modtools_user_reported'
  })

  Iznik.Views.ModTools.User.Logs = Iznik.Views.Modal.extend({
    template: 'modtools_user_logs',

    context: null,

    events: {
      'click .js-more': 'more'
    },

    first: true,

    moreShown: false,
    more: function () {
      this.getChunk()
    },

    addLog: function (log) {
      var self = this

      var v = new Iznik.Views.ModTools.User.LogEntry({
        model: new Iznik.Model(log)
      })

      v.render().then(function (v) {
        self.$('.js-list').append(v.el)
      })
    },

    getChunk: function () {
      var self = this

      this.model.fetch({
        data: {
          logs: true,
          modmailsonly: self.options.modmailsonly,
          logcontext: this.logcontext
        },
        success: function (model, response, options) {
          self.logcontext = response.logcontext

          // TODO This can't be right.
          if ((response.hasOwnProperty('user') && response.user.logs.length > 0) ||
            (response.hasOwnProperty('member') && response.member.logs.length > 0)) {
            self.$('.js-more').show()
          }
        }
      }).then(function () {
        self.$('.js-loading').addClass('visNone')
        var logs = self.model.get('logs')

        _.each(logs, function (log) {
          self.addLog(log)
        })

        if (!self.moreShown) {
          self.moreShown = true
        }

        if (self.first && (_.isUndefined(logs) || logs.length == 0)) {
          self.$('.js-none').show()
        }

        self.first = false
      })
    },

    render: function () {
      var p = Iznik.Views.Modal.prototype.render.call(this)
      p.then(function (self) {
        self.open(null)
        self.getChunk()
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.User.LogEntry = Iznik.View.extend({
    template: 'modtools_user_logentry',

    render: function () {
      var self = this

      var p = Iznik.View.prototype.render.call(this)
      p.then(function (self) {
        var mom = new moment(self.model.get('timestamp'))
        self.$('.js-date').html(mom.format('DD-MMM-YY HH:mm'))
      })
      return (p)
    }
  })

  // Modmails are very similar to logs.
  Iznik.Views.ModTools.User.ModMails = Iznik.Views.ModTools.User.Logs.extend({
    template: 'modtools_user_modmails',

    addLog: function (log) {
      var self = this

      var v = new Iznik.Views.ModTools.User.ModMailEntry({
        model: new Iznik.Model(log)
      })

      v.render().then(function (v) {
        self.$('.js-list').append(v.el)
      })
    }
  })

  Iznik.Views.ModTools.User.ModMailEntry = Iznik.View.extend({
    template: 'modtools_user_logentry',

    render: function () {
      var p = Iznik.View.prototype.render.call(this)
      p.then(function (self) {
        var mom = new moment(self.model.get('timestamp'))
        self.$('.js-date').html(mom.format('DD-MMM-YY HH:mm'))

        // The log template will add logs, but highlighted.  We want to remove the highlighting for the modmail
        // display.
        self.$('div.nomargin.alert.alert-danger').removeClass('nomargin alert alert-danger')
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.Member = Iznik.View.extend({
    rarelyUsed: function () {
      this.$('.js-rarelyused').fadeOut('slow')
      this.$('.js-stdmsgs li').fadeIn('slow')
    },

    addOtherInfo: function () {
      var self = this
      var thisemail = self.model.get('email')

      require(['jquery-show-first'], function () {
        // Add any other emails
        self.$('.js-otheremails').empty()
        var promises = []

        _.each(self.model.get('otheremails'), function (email) {
          if (email.email != thisemail) {
            var mod = new Iznik.Model(email)
            var v = new Iznik.Views.ModTools.Message.OtherEmail({
              model: mod
            })
            var p = v.render()
            p.then(function (v) {
              self.$('.js-otheremails').append(v.el)
            })
            promises.push(p)
          }
        })

        Promise.all(promises).then(function () {
          // Restrict how many we show
          self.$('.js-otheremails').showFirst({
            controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
            count: 5
          })
        })

        // Add any other group memberships we need to display.
        self.$('.js-memberof').empty()
        var promises2 = []

        var groupids = [self.model.get('groupid')]
        _.each(self.model.get('memberof'), function (group) {
          // if (groupids.indexOf(group.id) == -1)
          {
            var mod = new Iznik.Model(group)
            var v = new Iznik.Views.ModTools.Member.Of({
              model: mod,
              user: self.model
            })
            var p = v.render()
            p.then(function (v) {
              self.$('.js-memberof').append(v.el)
            })
            promises2.push(p)

            groupids.push(group.id)
          }
        })

        Promise.all(promises2).then(function () {
          self.$('.js-memberof').showFirst({
            controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
            count: 5
          })
        })

        self.$('.js-applied').empty()
        var promises3 = []

        _.each(self.model.get('applied'), function (group) {
          if (groupids.indexOf(group.id) == -1) {
            // Don't both displaying applications to groups we've just listed as them being a member of.
            var mod = new Iznik.Model(group)
            var v = new Iznik.Views.ModTools.Member.Applied({
              model: mod
            })
            var p = v.render()
            p.then(function (v) {
              self.$('.js-applied').append(v.el)
            })
            promises3.push(p)
          }
        })

        Promise.all(promises3).then(function () {
          self.$('.js-applied').showFirst({
            controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
            count: 5
          })
        })
      })
    }
  })

  Iznik.Views.ModTools.Member.OtherEmail = Iznik.View.extend({
    template: 'modtools_member_otheremail'
  })

  Iznik.Views.ModTools.Member.Of = Iznik.View.Timeago.extend({
    template: 'modtools_member_of',

    events: {
      'click .js-remove': 'remove'
    },

    remove: function () {
      var self = this

      if (self.options.user.get('systemrole') == 'User') {
        var v = new Iznik.Views.Confirm({
          model: self.options.user
        })
        v.template = 'modtools_members_removeconfirm'

        self.listenToOnce(v, 'confirmed', function () {
          $.ajax({
            url: API + 'memberships',
            type: 'POST',
            headers: {
              'X-HTTP-Method-Override': 'DELETE'
            },
            data: {
              userid: self.options.user.get('userid'),
              groupid: self.options.user.get('groupid')
            }, success: function (ret) {
              if (ret.ret == 0) {
                self.$el.fadeOut('slow')
                self.options.user.trigger('removed')
              }
            }
          })
        })

        v.render()
      }
    },

    render: function () {
      var self = this
      var emails = this.options.user.get('otheremails')
      var email = _.findWhere(emails, {
        id: this.model.get('emailid')
      })

      if (email) {
        this.model.set('email', email.email)
      }

      var p = Iznik.View.Timeago.prototype.render.call(this)
      p.then(function (self) {
        if (Iznik.Session.isModeratorOf(self.model.get('groupid')), true) {
          self.$('.js-remove').removeClass('hidden')
        }
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.Member.Applied = Iznik.View.Timeago.extend({
    template: 'modtools_member_applied'
  })

  Iznik.Views.ModTools.User.Comment = Iznik.View.Timeago.extend({
    template: 'modtools_user_comment',

    events: {
      'click .js-editnote': 'edit',
      'click .js-deletenote': 'deleteMe'
    },

    edit: function () {
      var v = new Iznik.Views.ModTools.User.CommentModal({
        model: this.model
      })

      this.listenToOnce(v, 'modalClosed', this.render)

      v.render()
    },

    deleteMe: function () {
      this.model.destroy().then(this.remove())
    },

    render: function () {
      var p = Iznik.View.Timeago.prototype.render.call(this)
      p.then(function (self) {
        var hideedit = true
        var group = self.model.get('group')
        if (group && (group.role == 'Moderator' || group.role == 'Owner')) {
          // We are a mod on self group - we can modify it.
          hideedit = false
        }

        if (hideedit) {
          self.$('.js-editnote, .js-deletenote').hide()
        }
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.User.CommentModal = Iznik.Views.Modal.extend({
    template: 'modtools_user_commentmodal',

    events: {
      'click .js-save': 'save'
    },

    save: function () {
      var self = this

      self.model.save().then(function () {
        self.close()
      })
    },

    render2: function () {
      var self = this

      self.open(null)

      self.fields = [
        {
          name: 'user1',
          control: 'input',
          placeholder: 'Add a comment about this member here'
        },
        {
          name: 'user2',
          control: 'input',
          placeholder: '...and more information here'
        },
        {
          name: 'user3',
          control: 'input',
          placeholder: '...and here'

        },
        {
          name: 'user4',
          control: 'input',
          placeholder: 'You get the idea.'
        },
        {
          name: 'user5',
          control: 'input'
        },
        {
          name: 'user6',
          control: 'input'
        },
        {
          name: 'user7',
          control: 'input'
        },
        {
          name: 'user8',
          control: 'input'
        },
        {
          name: 'user9',
          control: 'input'
        },
        {
          name: 'user10',
          control: 'input'
        },
        {
          name: 'user11',
          control: 'input'
        }
      ]

      self.form = new Backform.Form({
        el: $('#js-form'),
        model: self.model,
        fields: self.fields
      })

      self.form.render()

      // Make it full width.
      self.$('label').remove()
      self.$('.col-sm-8').removeClass('col-sm-8').addClass('col-sm-12')

      // Layout messes up a bit.
      self.$('.form-group').addClass('clearfix')

      // Turn on spell-checking
      self.$('textarea, input:text').attr('spellcheck', true)
    },

    render: function () {
      var self = this

      var p = Iznik.Views.Modal.prototype.render.call(this)
      p.then(function (self) {
        // Focus on first input.  This is hard to do in bootstrap, especially, with fade, so just hack
        // with a timer.
        window.setTimeout(function () {
          $('#js-form input:first').focus()
        }, 2000)

        if (self.model.get('id')) {
          // We want to refetch the model to make sure we edit the most up to date settings.
          self.model.fetch().then(self.render2.call(self))
        } else {
          // We're adding one; just render it.
          self.render2()
        }
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.User.SpammerInfo = Iznik.View.Timeago.extend({
    template: 'modtools_user_spammerinfo'
  })

  Iznik.Views.ModTools.EnterReason = Iznik.Views.Modal.extend({
    events: {
      'click .js-cancel': 'close',
      'click .js-confirm': 'confirm'
    },

    confirm: function () {
      var self = this
      var reason = self.$('.js-reason').val()

      if (reason.length < 3) {
        self.$('.js-reason').focus()
      } else {
        self.trigger('reason', reason)
        self.close()
      }
    },

    render: function () {
      var self = this

      this.template = this.options.whitelist ? 'modtools_members_spam_reasonwhitelist' : 'modtools_members_spam_reason'

      this.open(this.template)

      return (this)
    }
  })

  Iznik.Views.ModTools.Member.Freegle = Iznik.View.extend({
    template: 'modtools_freegle_user',

    events: {
      'change .js-emailfrequency': 'changeFreq',
      'change .js-ourpostingstatus': 'changeOurPostingStatus',
      'change .js-role': 'changeRole',
    },

    changeFreq: function () {
      var self = this

      var data = {
        userid: self.model.get('userid'),
        groupid: self.model.get('groupid'),
        emailfrequency: self.$('.js-emailfrequency').val()
      }

      $.ajax({
        url: API + 'memberships',
        type: 'POST',
        headers: {
          'X-HTTP-Method-Override': 'PATCH'
        },
        data: data
      })
    },

    changeOurPostingStatus: function () {
      var self = this
      var data = {
        userid: self.model.get('userid'),
        groupid: self.model.get('groupid'),
        ourpostingstatus: self.$('.js-ourpostingstatus').val()
      }

      $.ajax({
        url: API + 'memberships',
        type: 'POST',
        headers: {
          'X-HTTP-Method-Override': 'PATCH'
        },
        data: data
      })
    },

    changeRole: function () {
      var self = this
      var data = {
        userid: self.model.get('userid'),
        groupid: self.model.get('groupid'),
        role: self.$('.js-role').val()
      }

      $.ajax({
        url: API + 'memberships',
        type: 'POST',
        headers: {
          'X-HTTP-Method-Override': 'PATCH'
        },
        data: data
      })
    },

    saveHoliday: function (e) {
      var till = null
      var self = this

      if (this.$('.js-switch').bootstrapSwitch('state')) {
        // Set the hour else midnight and under DST goes back a day.
        e.date.hour(5)
        till = e.date.toISOString()
      }

      this.$('.js-onholidaytill').datetimepicker('hide')

      $.ajax({
        url: API + 'user',
        type: 'POST',
        headers: {
          'X-HTTP-Method-Override': 'PATCH'
        },
        data: {
          id: self.model.get('userid'),
          groupid: self.model.get('groupid'),
          onholidaytill: till
        }
      })
    },

    eventsallowed: function (e, data) {
      console.log('Events toggle', this, data)
      var self = this

      self.$('.js-eventmails').bootstrapSwitch('state', !data, true)
      var state = self.$('.js-eventmails').bootstrapSwitch('state')

      var v = new Iznik.Views.Confirm({})

      self.listenToOnce(v, 'confirmed', function () {
        self.$('.js-eventmails').bootstrapSwitch('toggleState', true)
        var data = {
          userid: self.model.get('userid'),
          groupid: self.model.get('groupid'),
          eventsallowed: state ? 0 : 1
        }

        $.ajax({
          url: API + 'memberships',
          type: 'POST',
          headers: {
            'X-HTTP-Method-Override': 'PATCH'
          },
          data: data,
          success: function (ret) {
            if (ret.ret === 0) {
              self.$('.js-ok').removeClass('hidden')
            }
          }
        })
      })

      v.render()
    },

    volunteeringallowed: function (e, data) {
      console.log('Volunteering toggle', this, data)
      var self = this

      self.$('.js-volunteermails').bootstrapSwitch('toggleState', true)
      var state = self.$('.js-volunteermails').bootstrapSwitch('state')

      var v = new Iznik.Views.Confirm({})

      self.listenToOnce(v, 'confirmed', function () {
        self.$('.js-volunteermails').bootstrapSwitch('toggleState', true)
        var data = {
          userid: self.model.get('userid'),
          groupid: self.model.get('groupid'),
          volunteeringallowed: state ? 0 : 1
        }

        $.ajax({
          url: API + 'memberships',
          type: 'POST',
          headers: {
            'X-HTTP-Method-Override': 'PATCH'
          },
          data: data,
          success: function (ret) {
            if (ret.ret === 0) {
              self.$('.js-ok').removeClass('hidden')
            }
          }
        })
      })

      v.render()
    },

    onholiday: function (e, data) {
      console.log('On holiday toggle', this, data)
      var self = this

      self.$('.js-onholiday').bootstrapSwitch('state', !data, true)
      var state = self.$('.js-onholiday').bootstrapSwitch('state')

      var v = new Iznik.Views.Confirm({})

      self.listenToOnce(v, 'confirmed', function () {
        self.$('.js-onholiday').bootstrapSwitch('state', data, true)
        if (!state) {
          // Turn on.  Changing the date value will actually save it.
          this.$('.js-onholidaytill').show()
          _.delay(_.bind(function () {
            this.$('.js-onholidaytill:visible').focus()
          }, this), 500)
        } else {
          // Turn off and reset date.
          this.$('.js-onholidaytill').val(null)
          this.$('.js-onholidaytill').hide()
          this.saveHoliday()
        }
      })

      v.render()
    },

    render: function () {
      var p = Iznik.View.prototype.render.call(this)
      p.then(function (self) {
        // We don't want to show the email frequency for a group which is on Yahoo and where the
        // email membership is not one of ours.  In that case Yahoo would be responsible for
        // sending the email, not us.
        var theemail = self.model.get('email')
        var emails = self.model.get('otheremails')
        var show = true

        _.each(emails, function (email) {
          if (theemail == email.email && !email.ourdomain) {
            show = false
          }
        })

        if (show) {
          self.$('.js-emailfrequency').val(self.model.get('emailfrequency'))
        } else {
          self.$('.js-emailfrequency').val(0)
        }

        self.$('.js-ourpostingstatus').val(self.model.get('ourpostingstatus'))
        self.$('.js-role').val(self.model.get('role'))

        var mom = new moment(self.model.get('joined'))
        var now = new moment()

        self.$('.js-joined').html(mom.format('ll'))

        if (now.diff(mom, 'days') <= 31) {
          self.$('.js-joined').addClass('error')
        }

        var onholiday = self.model.get('onholidaytill')

        self.$('.js-switch').bootstrapSwitch({
          onText: 'Paused',
          offText: 'Mail&nbsp;On',
          state: onholiday != undefined
        })

        // We override the default click handler because we want to add a prompt.
        self.$('.js-switch').on('switchChange.bootstrapSwitch', _.bind(self.onholiday, self))

        _.defer(function () {
          self.$('select').selectpicker()
        })

        self.$('.js-onholidaytill').datetimepicker({
          format: 'ddd, DD MMMM',
          minDate: new moment(),
          maxDate: (new moment()).add(30, 'days')
        }).on('dp.change', _.bind(self.saveHoliday, self))

        if (onholiday && onholiday != undefined && onholiday != '1970-01-01T00:00:00Z') {
          self.$('.js-onholidaytill').show()
          self.$('.js-emailfrequency').hide()
          self.$('.js-onholidaytill').datetimepicker('date', new moment(onholiday))
        } else {
          self.$('.js-onholidaytill').hide()
          self.$('.js-emailfrequency').show()
        }

        self.$('.js-eventmails').bootstrapSwitch({
          onText: 'Events&nbsp;On',
          offText: 'Events&nbsp;Off',
          state: self.model.get('eventsallowed')
        })

        self.$('.js-eventmails').on('switchChange.bootstrapSwitch', _.bind(self.eventsallowed, self))

        self.$('.js-volunteermails').bootstrapSwitch({
          onText: 'Volunteer&nbsp;On',
          offText: 'Volunteer&nbsp;Off',
          state: self.model.get('volunteeringallowed')
        })

        self.$('.js-volunteermails').on('switchChange.bootstrapSwitch', _.bind(self.volunteeringallowed, self))
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.User.FreegleMembership = Iznik.Views.ModTools.Member.Freegle.extend({
    // This view finds the appropriate group in a user, then renders that membership.
    render: function () {
      var self = this
      var memberof = this.model.get('memberof')
      var membership = null
      var p = Iznik.resolvedPromise(self)
      var userid = self.model.get('id')

      _.each(memberof, function (member) {
        if (self.options.groupid == member.id) {
          // This is the membership we're after
          var mod = new Iznik.Model(member)
          mod.set('myrole', Iznik.Session.roleForGroup(self.options.groupid, true))
          mod.set('joined', member.added)
          // console.log("My role is", self.options.groupid, mod.get('myrole'));

          self.model = mod
          var group = Iznik.Session.getGroup(self.options.groupid)
          self.model.set('group', group.attributes)
          self.model.set('groupid', group.id)
          self.model.set('userid', userid)
          p = Iznik.Views.ModTools.Member.Freegle.prototype.render.call(self)
        }
      })

      return (p)
    }
  })

  Iznik.Views.UserInfo = Iznik.Views.Modal.extend({
    template: 'userinfo',

    events: {
      'click .js-dm': 'directMessage'
    },

    directMessage: function () {
      var self = this

      // If this is ModTools, then we want to open a User2Mod chat for the group.  For FD, a User2User.
      $.ajax({
        type: 'POST',
        headers: {
          'X-HTTP-Method-Override': 'PUT'
        },
        url: API + 'chat/rooms',
        data: {
          userid: self.model.get('id'),
          chattype: MODTOOLS ? 'User2Mod' : 'User2User',
          groupid: MODTOOLS ? self.options.groupid : null
        }, success: function (ret) {
          if (ret.ret == 0) {
            var chatid = ret.id

            require(['iznik/views/chat/chat'], function (ChatHolder) {
              ChatHolder().fetchAndRestore(chatid)
            })
          }
        }
      })

      // Need to close modal else can't do anything in the chat.
      self.close()
    },

    render: function () {
      var self = this
      var userid = self.model.get('id')
      var myid = Iznik.Session.get('me').id

      var p = Iznik.resolvedPromise()

      self.model = new Iznik.Models.ModTools.User({
        id: userid
      })

      p = self.model.fetch({
        data: {
          info: true
        }
      })

      p.then(function () {
        Iznik.Views.Modal.prototype.render.call(self).then(function () {
          if (MODTOOLS) {
            // This won't work as the route is FD-specific
            self.$('.js-fullprofile').hide()
          }

          var mom = new moment(self.model.get('added'))
          self.$('.js-since').html(mom.format('Do MMMM YYYY'))

          var info = self.model.get('info');

          if (info) {
            self.$('.js-replytime').html(Iznik.formatDuration(info.replytime))
          }

          // Cover image
          var cover = self.model.get('coverimage') ? self.model.get('coverimage') : '/images/wallpaper.png'
          self.$('.coverphoto').css('background-image', 'url(' + cover + ')')

          if (Iznik.Session.get('me').id != self.model.get('id')) {
            self.$('.js-dm').show()
          }

          self.ratings1 = new Iznik.Views.User.Ratings({
            model: self.model
          })

          self.ratings1.render()
          self.$('.js-ratings1').html(self.ratings1.$el)

          self.ratings2 = new Iznik.Views.User.Ratings({
            model: self.model
          })

          self.ratings2.render()
          self.$('.js-ratings2').html(self.ratings2.$el)

          self.$('.js-abouttext').html(Iznik.twem(self.$('.js-abouttext').html()))
        })
      })

      return (p)
    }
  })

  Iznik.Views.User.Ratings = Iznik.View.extend({
    template: 'user_ratings',

    tagName: 'span',

    className: 'padtopsm',

    events: {
      'click .js-up': 'up',
      'click .js-down': 'down'
    },

    rate: function (rating) {
      var self = this

      var m = new Iznik.Models.ModTools.User({
        id: self.model.get('id')
      })

      self.$el.addClass('faded')

      if (self.model.get('info').ratings.Mine === rating) {
        // Cancel this one.
        rating = null;
      }

      m.rate(rating).then(function () {
        self.model.fetch({
          data: {
            info: true
          }
        }).then(function () {
          self.render()
          self.$el.removeClass('faded')
        })
      })
    },

    up: function () {
      this.rate('Up')
    },

    down: function () {
      this.rate('Down')
    }
  })

  Iznik.Views.User.TellAboutMe = Iznik.Views.Modal.extend({
    template: 'user_tellaboutme',

    events: {
      'click .js-save': 'save'
    },

    save: function () {
      var self = this

      var msg = self.$('.js-aboutme').val()
      msg = twemoji.replace(msg, function (emoji) {
        return '\\\\u' + twemoji.convert.toCodePoint(emoji) + '\\\\u'
      })

      Iznik.Session.saveAboutMe(msg).then(function () {
        Iznik.Session.testLoggedIn([
          'me',
          'aboutme'
        ])
        self.close()
      })
    },

    render: function () {
      var self = this

      var p = new Promise(function (resolve, reject) {
        self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
          var p = Iznik.Views.Modal.prototype.render.call(this)

          p.then(function () {
            var aboutme = Iznik.Session.get('me').aboutme

            if (aboutme) {
              var msg = Iznik.twem(aboutme.text)
              self.$('.js-aboutme').val(msg)
            }

            resolve(self)
          })
        })

        Iznik.Session.testLoggedIn([
          'me',
          'aboutme'
        ])
      })

      return (p)
    }
  })

})