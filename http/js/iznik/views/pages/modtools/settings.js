import 'bootstrap-fileinput/js/plugins/piexif.min.js'
import 'bootstrap-fileinput'

var tpl = require('iznik/templateloader')
var template = tpl.template
var templateFetch = tpl.templateFetch

define([
  'jquery',
  'underscore',
  'backbone',
  'Sortable',
  'wicket-gmap3',
  'moment',
  'iznik/base',
  'backform',
  'maplabel',
  'iznik/modtools',
  'iznik/views/pages/pages',
  'iznik/views/pages/modtools/messages',
  'iznik/models/group'
], function ($, _, Backbone, Sortable, Wkt, moment, Iznik) {
  Iznik.Views.ModTools.Pages.Settings = Iznik.Views.Page.extend({
    modtools: true,

    template: 'modtools_settings_main',

    specialSettings: [
      'onhere',
      'publish',
      'onyahoo',
      'welcomemail',
      'region',
      'mentored'
    ],

    events: {
      'change .js-configselect': 'configSelect',
      'click .js-addbulkop': 'addBulkOp',
      'click .js-addstdmsg': 'addStdMsg',
      'click .js-addconfig': 'addConfig',
      'click .js-deleteconfig': 'deleteConfig',
      'click .js-copyconfig': 'copyConfig',
      'click .js-addgroup': 'addGroup',
      'click .js-addlicense': 'addLicense',
      'click .js-hideall': 'hideAll',
      'click .js-mapsettings': 'mapSettings',
      'click .js-editdesc': 'editDesc',
    },

    addGroup: function () {
      var v = new Iznik.Views.ModTools.Settings.AddGroup()
      v.render()
    },

    addLicense: function () {
      var self = this
      var group = Iznik.Session.getGroup(self.selected)
      var v = new Iznik.Views.ModTools.Settings.AddLicense({
        model: group
      })

      // If we license, update the display.
      self.listenToOnce(v, 'modalCancelled modalClosed', function () {
        self.settingsGroup()
      })
      v.render()
    },

    hideAll: function () {
      var self = this
      Iznik.Session.get('groups').each(function (group) {
        var membership = new Iznik.Models.Membership({
          groupid: group.get('id'),
          userid: Iznik.Session.get('me').id
        })

        membership.fetch().then(function () {
          var mod = new Iznik.Model(membership.get('settings'))
          mod.set('active', 0)
          mod.set('pushnotify', 0)
          var newdata = mod.toJSON()
          membership.save({
            'settings': newdata
          }, {
            patch: true
          })
        })
      })
    },

    deleteConfig: function () {
      var self = this
      var v = new Iznik.Views.Confirm({
        model: self.modConfigModel
      })
      v.template = 'modtools_settings_delconfconfirm'

      self.listenToOnce(v, 'confirmed', function () {
        var configid = self.$('.js-configselect').val()
        self.modConfigModel.destroy().then(_.bind(function () {
          this.refreshAndRender()
        }), self)
      })

      v.render()
    },

    addConfig: function () {
      var self = this
      var name = this.$('.js-addconfigname').val()

      if (name.length > 0) {
        // Create a new config and then reload.  Not very backboney.
        $.ajax({
          type: 'POST',
          url: API + 'modconfig',
          data: {
            name: name
          },
          success: function (ret) {
            if (ret.ret == 0) {
              $('.js-configselect').selectPersist('set', ret.id)
              self.refreshAndRender()
            }
          }
        })
      }
    },

    refreshAndRender: function () {
      var self = this
      self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
        if (loggedIn) {
          self.render()
        }
      })

      Iznik.Session.testLoggedIn(['all'])
    },

    copyConfig: function () {
      var self = this
      var name = this.$('.js-copyconfigname').val()
      var configid = self.$('.js-configselect').val()

      if (name.length > 0) {
        // Create a new config copied from the currently selected one, and then reload.  Not very backboney.
        $.ajax({
          type: 'POST',
          url: API + 'modconfig',
          data: {
            id: configid,
            name: name
          },
          success: function (ret) {
            if (ret.ret == 0) {
              $('.js-configselect').selectPersist('set', ret.id)
              self.refreshAndRender()
            }
          }
        })
      }
    },

    updateDescription: function () {
      this.$('.js-description').val(this.group.get('description'))
    },

    settingsGroup: function () {
      var self = this
      console.log('Settings groups')

      self.wait = new Iznik.Views.PleaseWait({
        timeout: 1
      })
      self.wait.render()

      self.waitDOM(self, function () {
        if (self.selected > 0) {
          self.group = new Iznik.Models.Group({
            id: self.selected
          })

          self.$('.js-twitterauth').attr('href', '/twitter/twitter_request.php?groupid=' + self.selected)
          self.$('.js-facebookauthpage').attr('href', '/facebook/facebook_request.php?groupid=' + self.selected + '&type=Page')

          self.group.fetch({
            data: {
              polygon: true,
              affiliationconfirmedby: true
            }
          }).then(function () {
            self.$('.js-modsemail').html(self.group.get('modsemail'))
            self.$('.js-postemail').html(self.group.get('groupemail'))
            self.$('.js-cga').html(self.group.get('cga'))
            self.$('.js-dpa').html(self.group.get('dpa'))

            // Get the shortlinks.
            $.ajax({
              url: API + 'shortlink',
              type: 'GET',
              data: {
                groupid : self.selected
              },
              success: function(ret) {
                self.$('.js-shortlinks').empty();
                console.log("Got", ret)
                if (ret.ret === 0) {
                  _.each(ret.shortlinks, function (link) {
                    console.log("Got link", link);
                    self.$('.js-shortlinks').append('<li>freegle.in/' + link.name + '</li>');
                  })
                }
              }
            })

            // Because we switch the form based on our group select we need to remove old events to avoid saving new
            // changes to the previous group.
            if (self.myGroupForm) {
              self.myGroupForm.undelegateEvents()
              self.$('#mygroupform').empty()
            }

            if (self.groupForm) {
              self.groupForm.undelegateEvents()
              self.$('#groupform').empty()
            }

            if (self.groupAppearanceForm) {
              self.groupAppearanceForm.undelegateEvents()
              self.$('#groupappearanceform').empty()
            }

            if (self.group.get('type') === 'Freegle') {
              var m = new moment(self.group.get('affiliationconfirmed'));

              self.$('.js-affiliationconfirmed').html(m.format('MMMM Do YYYY'))
              self.$('.js-affiliatinconfirmedwrapper').show();
            }

            if (self.group.get('affiliationconfirmedby')) {
              self.$('.js-affiliationconfirmedby').html(' by ' + self.group.get('affiliationconfirmedby').displayname);
            }

            // Add license info
            var text
            if (self.group.get('licenserequired')) {
              if (!self.group.get('licensed')) {
                var now = new moment(self.group.get('trial'))
                text = '<div class="alert alert-warning">This group is using a trial license for 30 days from ' + now.format('ll') + '.</div>'
              } else {
                var mom = new moment(self.group.get('licenseduntil'))
                text = 'This group is licensed until ' + mom.format('ll') + '.'
              }

              self.$('.js-addlicense').show()
            } else {
              text = 'This group doesn\'t need a license.'
              self.$('.js-addlicense').hide()
            }

            self.$('.js-licenceinfo').html(text)
            self.$('.timeago').timeago()

            // Our settings for the group are held in the membership, so fire off a request for that.
            var membership = new Iznik.Models.Membership({
              groupid: self.selected,
              userid: Iznik.Session.get('me').id
            })

            membership.fetch().then(function () {
              self.myGroupModel = new Iznik.Model(membership.get('settings'))
              var configoptions = []
              var configs = Iznik.Session.get('configs')
              configs.each(function (config) {
                configoptions.push({
                  label: config.get('name'),
                  value: config.get('id')
                })
              })
              self.myGroupFields = [
                {
                  name: 'configid',
                  label: 'ModConfig to use for this Group',
                  control: 'select',
                  options: configoptions
                },
                {
                  name: 'pushnotify',
                  label: 'Push/app notifications?',
                  control: 'radio',
                  extraClasses: ['row'],
                  options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}]
                },
                {
                  name: 'active',
                  label: 'Are you actively moderating this group?',
                  control: 'radio',
                  extraClasses: ['row'],
                  options: [{label: 'Active', value: 1}, {label: 'Backup', value: 0}]
                },
                {
                  name: 'showmembers',
                  label: 'Show members in All Groups?',
                  control: 'radio',
                  extraClasses: ['row'],
                  options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}]
                },
                {
                  control: 'button',
                  label: 'Save changes',
                  type: 'submit',
                  extraClasses: ['btn-success topspace botspace']
                }
              ]

              self.myGroupForm = new Backform.Form({
                el: $('#mygroupform'),
                model: self.myGroupModel,
                fields: self.myGroupFields,
                events: {
                  'submit': function (e) {
                    // Send a PATCH to the server for settings.
                    e.preventDefault()
                    var newdata = self.myGroupModel.toJSON()
                    membership.save({
                      'settings': newdata
                    }, {
                      patch: true,
                      success: _.bind(self.success, self),
                      error: self.error
                    })
                    return (false)
                  }
                }
              })

              self.myGroupForm.render()
            })

            // The global group settings.
            self.groupModel = new Iznik.Model(self.group.get('settings'))

            // Settings not inside the settings field.
            _.each(self.specialSettings, function (attr) {
              self.groupModel.set(attr, self.group.get(attr))
            })

            if (!self.groupModel.get('map')) {
              self.groupModel.set('map', {
                'zoom': 12
              })
            }

            var role = self.group.get('myrole')

            self.groupFields = [
              {
                name: 'region',
                label: 'Region',
                control: 'select',
                options: [
                  {label: 'East', value: 'East'},
                  {label: 'London', value: 'London'},
                  {label: 'Midlands West', value: 'West Midlands'},
                  {label: 'Midlands East', value: 'East Midlands'},
                  {label: 'North East', value: 'North East'},
                  {label: 'North West', value: 'North West'},
                  {label: 'Northern Ireland', value: 'Northern Ireland'},
                  {label: 'South East', value: 'South East'},
                  {label: 'South West', value: 'South West'},
                  {label: 'Wales', value: 'Wales'},
                  {label: 'Yorkshire and the Humber', value: 'Yorkshire and the Humber'},
                  {label: 'Scotland', value: 'Scotland'}
                ],
              },
              {
                name: 'publish',
                label: 'Visible on website?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: '(Freegle only) Is this group available for people to find and use?',
                disabled: role != 'Owner' && !Iznik.Session.isAdminOrSupport()
              },
              {
                name: 'onhere',
                label: 'Enable Freegle Direct?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: '(Freegle only) Enable Freegle Direct.  Users will be able to join your group via Freegle Direct as well as on Yahoo or via TrashNothing.'
              },
              {
                name: 'onyahoo',
                label: 'Hosted on Yahoo?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: '(Freegle only) If Yes, this group exists on Yahoo Groups too.  If No, this group is only hosted here.',
                disabled: role != 'Owner' && !Iznik.Session.isAdminOrSupport()
              },
              {
                name: 'mentored',
                label: 'Mentor Caretakers?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: '(Freegle only) If Yes, this group is being run by Mentor Caretakers.',
                disabled: !Iznik.Session.isAdminOrSupport()
              },
              {
                name: 'communityevents',
                label: 'Allow community events?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: '(Freegle only) Whether members can post local community events on this group.'
              },
              {
                name: 'volunteering',
                label: 'Allow volunteer requests?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: '(Freegle only) Whether members can post requests for volunteers on this group.'
              },
              {
                name: 'stories',
                label: 'Allow stories?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: '(Freegle only) Whether members are prompted to tell us their story.'
              },
              {
                name: 'showchat',
                label: 'Show chat window for mods?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'This lets groups mods chat to each other on here.'
              },
              {
                name: 'approvemembers',
                label: '(Freegle native groups only) New member approval required?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'Normally members can join immediately, and any posts they make will be moderated.  Some groups prefer to approve new members manually, which gives more control over the group but loses postential members.'
              },
              {
                name: 'moderated',
                label: '(Freegle native groups only) Group is fully-moderated?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'When this setting is No (for most groups), all new members are Moderated and members can be changed to Group Settings (meaning unmoderated) once they have made a valid post.  When this setting is Yes, all posts must be moderated no matter what setting the user has.'
              },
              {
                name: 'allowedits.moderated',
                label: '(Freegle native groups only) Moderated members can edit?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'When this setting is Yes (for most groups), moderated members can edit their own posts; edits go live immediately but are retrospectively reviewed from Messages->Edits.  When this setting is No, moderated members cannot edit.'
              },
              {
                name: 'allowedits.group',
                label: '(Freegle native groups only) Members on Group Settings can edit?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'When this setting is Yes (for most groups), members on Group Settings can edit their own posts; edits go live immediately.  When this setting is No, members on Group Settings cannot edit.'
              },
              {
                name: 'autoapprove.members',
                label: 'Auto-approve pending members?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'Yahoo doesn\'t let you change from member approval to not approving them - use this to work around that.'
              },
              {
                name: 'duplicates.check',
                label: 'Flag duplicate messages?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}]
              },
              {
                name: 'spammers.check',
                label: 'Check for spammer members?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}]
              },
              {
                name: 'spammers.remove',
                label: 'Auto-remove spammer members?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}]
              },
              {
                name: 'spammers.messagereview',
                label: 'Check for spam messages to group?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'Check group messages for spam and put in Messages->Spam?'
              },
              {
                name: 'keywords.offer',
                label: 'OFFER keyword',
                control: 'input'
              },
              {
                name: 'keywords.taken',
                label: 'TAKEN keyword',
                control: 'input'
              },
              {
                name: 'keywords.wanted',
                label: 'WANTED keyword',
                control: 'input'
              },
              {
                name: 'keywords.received',
                label: 'RECEIVED keyword',
                control: 'input'
              },
              {
                name: 'duplicates.offer',
                label: 'OFFER duplicate period',
                control: 'input',
                type: 'number'
              },
              {
                name: 'duplicates.taken',
                label: 'TAKEN duplicate period',
                control: 'input',
                type: 'number'
              },
              {
                name: 'duplicates.wanted',
                label: 'WANTED duplicate period',
                control: 'input',
                type: 'number'
              },
              {
                name: 'duplicates.received',
                label: 'RECEIVED duplicate period',
                control: 'input',
                type: 'number'
              },
              {
                name: 'reposts.chaseups',
                label: '(Freegle only) Chaseup after',
                control: 'input',
                type: 'number',
                helpMessage: 'Ask what\'s happening with the item this number of days after the last reply (0 to disable)'
              },
              {
                name: 'relevant',
                label: '(Freegle only) Send relevant messages to members?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'Email specific messages to members based on their searches and posting history.  Members can turn this on/off themselves, so you would only turn this off if you want to override their decision.'
              },
              {
                name: 'chitchat',
                label: '(Freegle only) Send occasional digests of chitchat to members?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'We can send an occasional mail to members of recent activity from other members on ChitChat (like the old cafe groups).  This encourages them to take part.  Members can turn this off themselves, so you would only turn this off if you want to override their decision.'
              },
              {
                name: 'newsletter',
                label: '(Freegle only) Send newsletters to members?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'Email occasional newsletters to members.  Members can turn this on/off themselves, so you would only turn this off if you want to override their decision.'
              },
              {
                name: 'businesscards',
                label: '(Freegle only) Offer business cards to members?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}],
                helpMessage: 'When members mark a post as completed, we can offer them some business cards to promote Freegle.'
              },
              {
                name: 'reposts.max',
                label: '(Freegle only) Max auto-reposts',
                control: 'input',
                type: 'number',
                helpMessage: '0 to disable'
              },
              {
                name: 'reposts.offer',
                label: '(Freegle only) OFFER auto-repost (days)',
                control: 'input',
                type: 'number',
                helpMessage: 'Also controls when the user can manually repost using the button on FD.'
              },
              {
                name: 'reposts.wanted',
                label: '(Freegle only) WANTED auto-repost (days)',
                control: 'input',
                type: 'number',
                helpMessage: 'Also controls when the user can manually repost using the button on FD.'
              },
              {
                name: 'map.zoom',
                label: 'Default zoom for maps',
                control: 'input',
                type: 'number'
              },
              {
                name: 'includearea',
                label: 'Include area name in locations?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}]
              },
              {
                name: 'includepc',
                label: 'Include postcode in locations?',
                control: 'radio',
                options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}]
              },
              {
                control: 'button',
                label: 'Save changes',
                type: 'submit',
                extraClasses: ['btn-success topspace botspace']
              }
            ]

            self.groupForm = new Backform.Form({
              el: $('#groupform'),
              model: self.groupModel,
              fields: self.groupFields,
              events: {
                'submit': function (e) {
                  e.preventDefault()
                  var newdata = self.groupModel.toJSON()
                  var data = {
                    'settings': newdata
                  }

                  _.each(self.specialSettings, function (attr) {
                    data[attr] = self.groupModel.get(attr)
                  })

                  self.group.save(data, {
                    patch: true,
                    success: _.bind(self.success, self),
                    error: self.error
                  })
                  return (false)
                }
              }
            })

            self.groupForm.render()

            // The appearance.
            var profile = self.group.get('profile')
            self.$('.js-profile').attr('src', profile ? profile : 'https://placehold.it/200x200')

            self.photoUpload = new Iznik.View.PhotoUpload({
              target: self.$('.js-profileupload'),
              uploadData: {
                imgtype: 'Group'
              },
              browseIcon: '<span class="glyphicon glyphicon-plus" />&nbsp;',
              browseLabel: 'Upload image',
              browseClass: 'btn btn-primary nowrap',
              errorContainer: '#js-uploaderror'
            })

            self.listenTo(self.photoUpload, 'uploadEnd', function (ret) {
              self.group.set('profile', ret.id)
              self.group.save({
                id: self.group.get('id'),
                profile: ret.id
              }, {
                patch: true
              })
              self.$('.js-profile').attr('src', ret.path)
              self.$('.file-preview').hide()
            })

            self.photoUpload.render();

            self.groupAppearanceForm = new Backform.Form({
              el: $('#groupappearanceform'),
              model: self.group,
              fields: [
                {
                  name: 'tagline',
                  label: 'Tagline for your group',
                  control: 'input',
                  helpMessage: 'This should be short and snappy.  Include some local reference that people in your area will feel connected to.',
                },
                {
                  name: 'showonyahoo',
                  label: 'Show Yahoo Group to members?',
                  control: 'select',
                  options: [{
                    label: 'Show links to Yahoo group too',
                    value: 1
                  }, {label: 'Don\'t show links to Yahoo group', value: 0}]
                },
                {
                  name: 'welcomemail',
                  label: 'Welcome mail to send to new members',
                  control: 'textarea',
                  placeholder: 'This is text only - no rich text/HTML.'
                },
                {
                  control: 'button',
                  label: 'Save changes',
                  type: 'submit',
                  extraClasses: ['btn-success topspace botspace']
                }
              ],
              events: {
                'submit': function (e) {
                  e.preventDefault()

                  var data = {
                    'tagline': self.group.get('tagline'),
                    'showonyahoo': self.group.get('showonyahoo')
                  }

                  _.each(self.specialSettings, function (attr) {
                    data[attr] = self.group.get(attr)
                  })

                  self.group.save(data, {
                    patch: true,
                    success: _.bind(self.success, self),
                    error: self.error
                  })
                  return (false)
                }
              }
            })

            self.groupAppearanceForm.render()
            $('#groupappearanceform input[name=tagline]').attr('maxlength', 120)

            self.updateDescription()

            // Add Twitter info.  Won't show for groups it shouldn't.
            var twitter = self.group.get('twitter')

            self.$('.js-twitter').hide()

            if (twitter && twitter.name) {
              self.$('.js-twittername').html(twitter.name)
              self.$('.js-twitterurl').attr('href', 'https://twitter.com/' + twitter.name)

              if (!twitter.valid) {
                self.$('.js-twitternotlinked').show()
              } else if (twitter.locked) {
                self.$('.js-twitterlocked').show()
              } else {
                var mom = new moment(twitter.authdate)
                self.$('.js-twitterauthdate').html(mom.format('ll'))
                self.$('.js-twittervalid').show()
              }
            } else {
              self.$('.js-twitternotlinked').show()
            }

            self.$('.js-facebook').hide()

            // Add Facebook info.  Won't show for groups it shouldn't.
            var facebook = self.group.get('facebook')

            if (facebook && facebook.length > 0) {
              self.facebookColl = new Iznik.Collection(facebook)

              self.facebookCV = new Backbone.CollectionView({
                el: self.$('.js-facebooklist'),
                modelView: Iznik.Views.ModTools.Settings.GroupFacebook,
                collection: self.facebookColl,
                modelViewOptions: {
                  group: self.group
                },
                processKeyEvents: false
              })

              self.facebookCV.render()
            } else {
              self.$('.js-facebooknotlinked').show()
            }

            // Layout messes up a bit for radio buttons.
            self.groupForm.$(':radio').closest('.form-group').addClass('clearfix')

            if (self.group.get('type') == 'Freegle') {
              self.$('.js-freegleonly').show()
            } else {
              self.$('.js-freegleonly').hide()
            }

            self.wait.close()
          })
        }
        else {
          self.wait.close()
        }
      })
    },

    addStdMsg: function () {
      var self = this

      // Having no id in the model means we will do a POST when we save it, and therefore create it on the server.
      var model = new Iznik.Models.ModConfig.StdMessage({
        configid: self.$('.js-configselect').val()
      })
      var v = new Iznik.Views.ModTools.Settings.StdMessage({
        model: model
      })

      // When we close, update what's shown.
      this.listenToOnce(v, 'modalClosed', this.configSelect)

      v.render()
    },

    addBulkOp: function () {
      var self = this

      // Having no id in the model means we will do a POST when we save it, and therefore create it on the server.
      var model = new Iznik.Models.ModConfig.BulkOp({
        configid: self.$('.js-configselect').val()
      })
      var v = new Iznik.Views.ModTools.BulkOp({
        model: model
      })

      // When we close, update what's shown.
      this.listenToOnce(v, 'modalClosed', this.configSelect)

      v.render()
    },

    locked: function (model) {
      // Whether we can make changes to this config.
      if (!this.modConfigModel) {
        return (false)
      }
      var createdby = this.modConfigModel.get('createdby')
      var isProtected = this.modConfigModel.get('protected')

      return (isProtected && Iznik.Session.get('me').id != createdby)
    },

    configSelect: function () {
      var self = this

      // Because we switch the form based on our config select we need to remove old events to avoid saving new
      // changes to the previous config.
      if (self.modConfigFormGeneral) {
        self.modConfigFormGeneral.undelegateEvents()
      }

      var selected = self.$('.js-configselect').val()

      if (selected > 0) {
        self.modConfigModel = new Iznik.Models.ModConfig({
          id: selected
        })

        self.modConfigModel.fetch().then(function () {
          // 0 values stripped.
          var prot = self.modConfigModel.get('protected')
          self.modConfigModel.set('protected', prot ? prot : 0)

          self.modConfigFieldsGeneral = [
            {
              name: 'name',
              label: 'ModConfig name',
              control: 'input',
              helpMessage: 'If you want to change the name of the ModConfig, edit it in here.'
            },
            {
              name: 'fromname',
              label: '"From:" name in messages',
              control: 'select',
              options: [{label: 'My name', value: 'My display name (above)'}, {
                label: '$groupname Moderator',
                value: 'Groupname Moderator'
              }]
            },
            {
              name: 'coloursubj',
              label: 'Colour-code subjects?',
              control: 'select',
              options: [{label: 'Yes', value: 1}, {label: 'No', value: 0}]
            },
            {
              name: 'subjreg',
              label: 'Regular expression for colour-coding',
              disabled: _.bind(self.locked, self),
              control: 'input',
              helpMessage: 'Regular expressions can be difficult; test changes at http://www.phpliveregex.com'
            },
            {
              name: 'subjlen',
              label: 'Subject length warning',
              control: 'input',
              disabled: _.bind(self.locked, self),
              type: 'number'
            },
            {
              name: 'network',
              label: 'Network name for $network substitution string',
              control: 'input'
            },
            {
              name: 'protected',
              label: 'Locked to only allow changes by creator?',
              control: 'select',
              options: [
                {label: 'Locked', value: 1},
                {label: 'Unlocked', value: 0}
              ]
            },
            {
              control: 'button',
              label: 'Save changes',
              type: 'submit',
              extraClasses: ['btn-success topspace botspace']
            }
          ]

          self.modConfigFormGeneral = new Backform.Form({
            el: $('#modconfiggeneral'),
            model: self.modConfigModel,
            fields: self.modConfigFieldsGeneral,
            events: {
              'submit': function (e) {
                e.preventDefault()
                var newdata = self.modConfigModel.toJSON()
                var attrs = self.modConfigModel.changedAttributes()
                if (attrs) {
                  self.modConfigModel.save(attrs, {
                    patch: true,
                    success: _.bind(self.success, self),
                    error: self.error
                  })
                }
                return (false)
              }
            }
          })

          var cansee = 'of magic pixies.'
          switch (self.modConfigModel.get('cansee')) {
            case 'Created':
              cansee = 'you created it.'
              break
            case 'Default':
              cansee = 'it\'s a global default configuration.'
              break
            case 'Shared':
              cansee = 'it\'s used by ' + self.modConfigModel.get('sharedby').displayname + ' on ' +
                self.modConfigModel.get('sharedon').namedisplay + ', where you are also a moderator.'
              break
          }

          self.modConfigFormGeneral.render()
          self.$('.js-cansee').html('You can see this ModConfig because ' + cansee)

          var locked = self.locked()

          // Add cc options
          _.defer(function () {
            _.each(['reject', 'followup', 'rejmemb', 'follmemb'], function (tag) {
              function createForm (tag) {
                var form = new Backform.Form({
                  el: $('.js-cc' + tag + 'form'),
                  model: self.modConfigModel,
                  fields: [
                    {
                      name: 'cc' + tag + 'to',
                      label: 'BCC to',
                      disabled: _.bind(_.bind(self.locked, self), self),
                      control: 'select',
                      options: [
                        {label: 'Nobody', value: 'Nobody'},
                        {label: 'Me', value: 'Me'},
                        {label: 'Specific address', value: 'Specific'}
                      ]
                    },
                    {
                      name: 'cc' + tag + 'addr',
                      label: 'Specific address',
                      disabled: _.bind(_.bind(self.locked, self), self),
                      placeholder: 'Please enter the specific email address',
                      type: 'email',
                      control: 'input'
                    },
                    {
                      control: 'button',
                      disabled: _.bind(self.locked, self),
                      label: 'Save changes',
                      type: 'submit',
                      extraClasses: ['btn-success topspace botspace']
                    }
                  ],
                  events: {
                    'submit': function (e) {
                      e.preventDefault()
                      var newdata = self.modConfigModel.toJSON()
                      var attrs = self.modConfigModel.changedAttributes()

                      if (attrs) {
                        self.modConfigModel.save(attrs, {
                          patch: true,
                          success: _.bind(self.success, self),
                          error: self.error
                        })
                      } else {
                        self.success()
                      }

                      return (false)
                    }
                  }
                })

                form.render()

                // Disabled doesn't get set correctly
                $('.js-cc' + tag + 'form select, .js-cc' + tag + 'form button').prop('disabled', self.locked())
                //console.log("Disable", $('.js-cc' + tag + 'form select, .js-cc' + tag + 'form button'));

                // We want to dynamically disable, which backform doesn't.
                function handleChange (self, tag) {
                  return (function (e) {
                    var val = self.modConfigModel.get('cc' + tag + 'to')
                    var inp = self.$('input[name=\'cc' + tag + 'addr\']')
                    inp.prop('disabled', val != 'Specific' || self.locked())
                  })
                }

                self.listenTo(self.modConfigModel, 'change:cc' + tag + 'to', handleChange(self, tag))

                var targ = self.$('input[name=\'cc' + tag + 'addr\']')
                var disabled = self.$('select[name=\'cc' + tag + 'to\']').val().indexOf('Specific') == -1
                targ.prop('disabled', disabled || self.locked())
              }

              createForm(tag)
            })
          })

          // Add buttons for the standard messages in the various places.
          var sortmsgs = Iznik.orderedMessages(self.modConfigModel.get('stdmsgs'), self.modConfigModel.get('messageorder'))
          self.$('.js-stdmsgspending, .js-stdmsgsapproved, .js-stdmsgspendingmembers, .js-stdmsgsmembers').empty()

          _.each(sortmsgs, function (stdmsg) {
            // Find the right place to add the button.
            var container = null
            switch (stdmsg.action) {
              case 'Approve':
              case 'Reject':
              case 'Delete':
              case 'Leave':
              case 'Edit':
                container = '.js-stdmsgspending'
                break
              case 'Leave Approved Message':
              case 'Delete Approved Message':
                container = '.js-stdmsgsapproved'
                break
              case 'Approve Member':
              case 'Reject Member':
              case 'Leave Member':
                container = '.js-stdmsgspendingmembers'
                break
              case 'Delete Approved Member':
                container = '.js-stdmsgsmembers'
              case 'Leave Approved Member':
                container = '.js-stdmsgsmembers'
                break
            }

            stdmsg.protected = locked

            var v = new Iznik.Views.ModTools.StdMessage.SettingsButton({
              model: new Iznik.Models.ModConfig.StdMessage(stdmsg),
              config: self.modConfigModel
            })

            self.listenTo(v, 'buttonChange', self.configSelect)

            v.render().then(function (v) {
              $(v.el).data('buttonid', stdmsg.id)
              self.$(container).append(v.el)
            })
          })

          // Make the buttons sortable.
          self.$('.js-sortable').each(function (index, value) {
            Sortable.create(value, {
              onEnd: function (evt) {
                // We've dragged a button.  Find the New Order.
                var order = []
                self.$('.js-stdbutton').each(function (index, button) {
                  var id = $(button).data('buttonid')
                  order.push(id)
                })

                // We have the New Order.  Undivided joy.
                var neworder = JSON.stringify(order)
                self.modConfigModel.set('messageorder', neworder)
                self.modConfigModel.save({
                  'messageorder': neworder
                }, {patch: true})
              }
            })
          })

          // Add the bulkops
          self.$('.js-bulkops').empty()

          _.each(self.modConfigModel.get('bulkops'), function (bulkop) {
            bulkop.protected = locked

            var v = new Iznik.Views.ModTools.BulkOp.Button({
              model: new Iznik.Models.ModConfig.BulkOp(bulkop),
              config: self.modConfigModel
            })

            self.listenTo(v, 'buttonChange', self.configSelect)

            v.render().then(function (v) {
              $(v.el).data('buttonid', bulkop.id)
              self.$('.js-bulkops').append(v.el)
            })
          })

          if (locked) {
            // We can't change anything, except to select another config, copy or add
            self.$('.js-notconfigselect input,.js-notconfigselect select,.js-notconfigselect button, .js-addbulkop').prop('disabled', true).addClass('disabled')
            self.$('.js-copyconfigname, .js-copyconfig, .js-addconfigname, .js-addconfig').prop('disabled', false).removeClass('disabled')
            self.$('.js-locked').show()
          } else {
            self.$('.js-notconfigselect input,.js-notconfigselect select,.js-notconfigselect button, .js-addbulkop').prop('disabled', false).removeClass('disabled')
            self.$('.js-locked').hide()
          }

          var inuses = self.modConfigModel.get('using');
          self.$('.js-inuse').hide();

          if (inuses) {
            var inuselist = '';
            _.each(inuses, function(inuse) {
              if (inuselist != '') {
                inuselist += ', ';
              }

              var name = inuse.fullname ? inuse.fullname : (inuse.firstname + ' ' + inuse.lastname);
              inuselist += name;
            });

            self.$('.js-inuselist').html(inuselist);
            self.$('.js-inuse').show();

            self.$('.js-deleteconfig').disable();
          }

          // Layout messes up a bit for radio buttons.
          self.$('input').closest('.form-group').addClass('clearfix')
        })
      }
    },

    success: function (model, response, options) {
      if (response.ret == 0) {
        (new Iznik.Views.ModTools.Settings.Saved()).render()
      } else {
        this.error(model, response, options)
      }
    },

    error: function (model, response, options) {
      console.log('Error', model, response, options)

      if (response.ret == 10) {
        (new Iznik.Views.ModTools.Settings.VerifyRequired({
          model: new Iznik.Model(response)
        })).render()
      } else {
        (new Iznik.Views.ModTools.Settings.SaveFailed({
          model: new Iznik.Model(response)
        })).render()
      }
    },

    mapSettings: function () {
      Router.navigate('/modtools/settings/' + this.selected + '/map', true)
    },

    editDesc: function () {
      var self = this
      var v = new Iznik.Views.ModTools.Settings.GroupDescription({
        model: this.group
      })

      self.listenToOnce(v, 'modalClosed', function () {
        self.group.fetch().then(_.bind(self.updateDescription, self))
      })

      v.render()
    },

    render: function () {
      var p = Iznik.Views.Page.prototype.render.call(this)
      p.then(function (self) {
        // Fetch the session to pick up any changes in the list of configs etc.
        self.listenToOnce(Iznik.Session, 'isLoggedIn', function () {
          self.groupSelect = new Iznik.Views.Group.Select({
            systemWide: false,
            all: false,
            mod: true,
            choose: true,
            id: 'settingsGroupSelect'
          })

          self.listenTo(self.groupSelect, 'selected', function (selected) {
            self.selected = selected
            self.settingsGroup()
          })

          // Render after the listen to as they are called during render.
          self.groupSelect.render().then(function (v) {
            self.$('.js-groupselect').html(v.el)
          })

          // Personal settings
          var me = Iznik.Session.get('me')
          var settings = Iznik.presdef('settings', me, null)
          settings = (settings == null || settings.length == 0) ? {
            'playbeep': 1
          } : settings

          self.personalModel = new Iznik.Model({
            id: me.id,
            displayname: me.displayname,
            fullname: me.fullname,
            email: me.email,
            settings: settings
          })

          var personalFields = [
            {
              name: 'displayname',
              label: 'Display Name',
              control: 'input',
              helpMessage: 'This is your name as displayed publicly to other users, including in the $myname substitution string.'
            },
            {
              name: 'email',
              label: 'Email',
              type: 'email',
              placeholder: 'Please enter an email address',
              control: 'input',
              helpMessage: 'Anything we mail to you, we\'ll mail to this email address.'
            },
            {
              name: 'settings.playbeep',
              label: 'Beep',
              control: 'select',
              options: [{label: 'Off', value: 0}, {label: 'Play beep for new work', value: 1}],
              helpMessage: 'Play beep when new ModTools work arrives.'
            },
            {
              name: 'settings.showmod',
              label: 'Show me as a volunteer?',
              control: 'radio',
              options: [{label: 'Hide me', value: 0}, {label: 'Show me', value: 1}],
              helpMessage: 'We can show members who the volunteers on a group are, to make it seem more friendly.  You can choose whether we show you.'
            },
            {
              name: 'settings.modnotifs',
              label: 'Moderation Notifications (Active)',
              control: 'select',
              options: [
                {label: 'After 24 hours', value: 24},
                {label: 'After 12 hours', value: 12},
                {label: 'After 4 hours', value: 4},
                {label: 'After 2 hours', value: 2},
                {label: 'After 1 hour', value: 1},
                {label: 'Immediately', value: 0},
                {label: 'Never', value: -1}
              ],
              helpMessage: 'For groups that you\'re an active mod on, we will mail you when there is moderation work to do which has been outstanding for more than a certain time.  You can control the frequency or disable this here.  We only mail you between 8am and 10pm, for groups you\'re an active mod on, and we don\'t mail you for pending messages/members on Yahoo Groups - if you want that, you can turn on Yahoo\'s notifications.'
            },
            {
              name: 'settings.backupmodnotifs',
              label: 'Moderation Notifications (Backup)',
              control: 'select',
              options: [
                {label: 'After 24 hours', value: 24},
                {label: 'After 12 hours', value: 12},
                {label: 'After 4 hours', value: 4},
                {label: 'After 2 hours', value: 2},
                {label: 'After 1 hour', value: 1},
                {label: 'Immediately', value: 0},
                {label: 'Never', value: -1}
              ],
              helpMessage: 'This is for groups where you\'re a backup mod.  You\'d usually set this to a higher value than the previous setting so that the active mods will get notified first.'
            },
            {
              name: 'settings.modnotifnewsfeed',
              label: 'Email me when members post in the Newsfeed? (Freegle only)',
              control: 'radio',
              options: [{label: 'Mail me', value: 1}, {label: 'Don\'t mail me', value: 0}],
              helpMessage: 'Your members may post in the Newsfeed, perhaps to introduce themselves, or perhaps because they have problems.  Replying to these posts helps them and makes Freegle friendlier.  We only mail you about groups you are an active mod on.'
            },
            {
              control: 'button',
              label: 'Save changes',
              type: 'submit',
              extraClasses: ['topspace btn-success']
            }
          ]

          var personalForm = new Backform.Form({
            el: $('#personalform'),
            model: self.personalModel,
            fields: personalFields,
            events: {
              'submit': function (e) {
                e.preventDefault()
                var newdata = self.personalModel.toJSON()
                Iznik.Session.save(newdata, {
                  patch: true,
                  success: _.bind(self.success, self),
                  error: self.error
                })
                return (false)
              }
            }
          })

          personalForm.render()

          var configs = Iznik.Session.get('configs')
          self.$('.js-configselect').empty()
          configs.each(function (config) {
            self.$('.js-configselect').append('<option value=' + config.get('id') + '>' +
              $('<div />').text(config.get('name')).html() + '</option>')
          })

          self.$('.js-configselect').selectpicker()
          self.$('.js-configselect').selectPersist()

          self.configSelect()
        })

        Iznik.Session.testLoggedIn(['all'])
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.StdMessage.SettingsButton = Iznik.Views.ModTools.StdMessage.Button.extend({
    // We override the events, so we get the same visual display but when we click do an edit of the settings.

    events: {
      'click .js-approve': 'edit',
      'click .js-reject': 'edit',
      'click .js-delete': 'edit',
      'click .js-hold': 'edit',
      'click .js-release': 'edit',
      'click .js-edit': 'edit',
      'click .js-leave': 'edit'
    },

    edit: function () {
      var self = this
      var v = new Iznik.Views.ModTools.Settings.StdMessage({
        model: this.model
      })

      // If we close a modal we might need to refresh.
      this.listenToOnce(v, 'modalClosed', function () {
        self.trigger('buttonChange')
      })
      v.render()
    }
  })

  // We use a custom control for action so that we can add groups into what would otherwise be a long list.
  //
  // Defer for our template expansion to work which requires DOM elements.
  Iznik.Views.ModTools.Settings.ActionSelect = Backform.InputControl.extend({
    defaults: {
      type: 'actionselect'
    },

    events: {
      'change .js-action': 'getValueFromDOM'
    },

    getValueFromDOM: function (e) {
      var self = this
      var val = this.$('.js-action').val()

      // Might want to enable/disable the Edit Text option.
      if (val == 'Edit') {
        $('select[name="edittext"]').prop('disabled', null)
      } else {
        $('select[name="edittext"]').prop('disabled', true)
      }

      return this.formatter.toRaw(val, this.model)
    },

    render: function () {
      // Since this isn't one of our views we must fetch the template manually.
      this.template = template('modtools_settings_action')
      Backform.InputControl.prototype.render.apply(this, arguments)

      return (this)
    }
  })

  Iznik.Views.ModTools.Settings.StdMessage = Iznik.Views.Modal.extend({
    template: 'modtools_settings_stdmsg',

    shaded: true,

    events: {
      'click .js-save': 'save',
      'click .js-delete': 'delete'
    },

    save: function () {
      var self = this

      // The model doesn't seem to be updated correctly via Backform.
      self.model.set('action', self.$('.js-action').val())

      self.model.save().then(function () {
        self.close()
      })
    },

    delete: function () {
      var self = this

      self.model.destroy().then(function () {
        self.close()
      })
    },

    render: function () {
      var self = this
      var p = Iznik.Views.Modal.prototype.render.call(this)

      // Because this isn't our view, and therefore has a sync render, we need to fetch the template first.
      var q = templateFetch('modtools_settings_action')

      Promise.all([p, q]).then(function () {
        // We want to refetch the model to make sure we edit the most up to date settings.
        self.model.fetch().then(function () {
          self.fields = [
            {
              name: 'title',
              label: 'Title',
              control: 'input'
            },
            {
              name: 'action',
              label: 'Action',
              control: Iznik.Views.ModTools.Settings.ActionSelect
            },
            {
              name: 'edittext',
              label: 'Edit Text (only for Edits)',
              options: [{label: 'Unchanged', value: 'Unchanged'}, {
                label: 'Correct Case',
                value: 'Correct Case'
              }],
              disabled: function (model) {
                return (model.get('action') != 'Edit')
              },
              control: Backform.SelectControl.extend({
                initialize: function () {
                  Backform.InputControl.prototype.initialize.apply(this, arguments)
                  this.listenTo(this.model, 'change:action', this.render)
                }
              })
            },
            {
              name: 'autosend',
              label: 'Autosend',
              control: 'select',
              options: [{label: 'Edit before send', value: 0}, {label: 'Send immediately', value: 1}]
            },
            {
              name: 'rarelyused',
              label: 'How often do you use this?',
              control: 'select',
              options: [{label: 'Frequently', value: 0}, {label: 'Rarely', value: 1}]
            },
            {
              name: 'newmodstatus',
              label: 'Change Moderation Status?',
              control: 'select',
              options: [
                {label: 'Unchanged', value: 'UNCHANGED'},
                {label: 'Moderated', value: 'MODERATED'},
                {label: 'Group Settings', value: 'DEFAULT'},
                {label: 'Can\'t Post', value: 'PROHIBITED'},
                {label: 'Unmoderated', value: 'UNMODERATED'},
              ]
            },
            {
              name: 'newdelstatus',
              label: 'Change Delivery Settings?',
              control: 'select',
              options: [
                {label: 'Unchanged', value: 'UNCHANGED'},
                {label: 'Daily Digest', value: 'DIGEST'},
                {label: 'Web Only', value: 'NONE'},
                {label: 'Individual Emails', value: 'SINGLE'},
                {label: 'Special Notices', value: 'ANNOUNCEMENT'}
              ]
            },
            {
              name: 'subjpref',
              label: 'Subject Prefix',
              control: 'input'
            },
            {
              name: 'subjsuff',
              label: 'Subject Suffix',
              control: 'input'
            },
            {
              name: 'insert',
              label: 'Insert text',
              control: 'select',
              options: [
                {label: 'Top', value: 'Top'},
                {label: 'Bottom', value: 'Bottom'}
              ]
            },
            {
              name: 'body',
              label: 'Message Body',
              control: 'textarea',
              extraClasses: ['js-textarea']
            }
          ]

          self.form = new Backform.Form({
            el: $('#js-form'),
            model: self.model,
            fields: self.fields
          })

          self.form.render()

          self.$('.js-action').val(self.model.get('action'))

          // Layout messes up a bit.
          self.$('.form-group').addClass('clearfix')
          self.$('.js-textarea').attr('rows', 10)

          // Turn on spell-checking
          self.$('textarea, input:text').attr('spellcheck', true)
        })

        self.open(null)
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.Settings.Saved = Iznik.Views.Modal.extend({
    template: 'modtools_settings_saved',
    render: function () {
      var p = Iznik.Views.Modal.prototype.render.call(this)
      p.then(function (self) {
        _.delay(_.bind(self.close, self), 10000)
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.Settings.SaveFailed = Iznik.Views.Modal.extend({
    template: 'modtools_settings_savefailed'
  })

  Iznik.Views.ModTools.Settings.VerifyRequired = Iznik.Views.Modal.extend({
    template: 'modtools_settings_verifyrequired'
  })

  Iznik.Views.ModTools.Settings.VerifyFailed = Iznik.Views.Modal.extend({
    template: 'modtools_settings_verifyfailed'
  })

  Iznik.Views.ModTools.Settings.VerifySucceeded = Iznik.Views.Modal.extend({
    template: 'modtools_settings_verifysucceeded'
  })

  Iznik.Views.ModTools.Settings.AddGroup = Iznik.Views.Modal.extend({
    template: 'modtools_settings_addgroup',

    events: {
      'click .js-add': 'add'
    },

    createFailed: function () {
      var v = new Iznik.Views.ModTools.Settings.CreateFailed()
      v.render()
    },

    add: function () {
      var self = this

      var data = {
        action: 'Create',
        name: self.diff[self.$('.js-grouplist').val()],
        grouptype: self.$('.js-type').val(),
        lat: self.$('.js-addlat').val(),
        lng: self.$('.js-addlng').val(),
        corearea: self.$('.js-addcore').val(),
        catchmentarea: self.$('.js-addcatchment').val()
      }

      if (data.name && data.grouptype && data.lat && data.lng && data.corearea) {
        $.ajax({
          type: 'POST',
          url: API + 'group',
          data: data, success: function (ret) {
            if (ret.ret == 0) {
              var v = new Iznik.Views.ModTools.Settings.CreateSucceeded()
              v.render()

              // Trigger another list to force the invite and hence the add.
              IznikPlugin.listYahooGroups()
            } else {
              self.createFailed()
            }
          }, error: self.createFailed
        })
      }
    },

    render: function () {
      var p = Iznik.Views.Modal.prototype.render.call(this)
      p.then(function (self) {
        // Get the list of groups from Yahoo.
        if (IznikPlugin.yahooGroups.length == 0) {
          self.$('.js-noplugin').removeClass('hidden')
          self.$('.js-add').addClass('disabled')
        } else {
          // Find the groups which aren't on ModTools.
          var groups = []
          Iznik.Session.get('groups').each(function (group) {
            groups.push(group.get('nameshort').toLowerCase())
          })

          self.diff = _.difference(IznikPlugin.yahooGroups, groups)
          _.each(self.diff, function (group, ind) {
            self.$('.js-grouplist').append('<option value="' + ind + '" />')
            self.$('.js-grouplist option:last').html(group)
          })
          self.$('.js-plugin').removeClass('hidden')
        }
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.Settings.CreateSucceeded = Iznik.Views.Modal.extend({
    template: 'modtools_settings_createsucceeded'
  })

  Iznik.Views.ModTools.Settings.CreateFailed = Iznik.Views.Modal.extend({
    template: 'modtools_settings_createfailed'
  })

  Iznik.Views.ModTools.BulkOp = Iznik.Views.Modal.extend({
    template: 'modtools_settings_bulkop',

    events: {
      'click .js-save': 'save',
      'click .js-delete': 'delete',
      'change .js-criterion': 'criterion'
    },

    criterion: function () {
      var disabled = this.$('.js-criterion').val().indexOf('BouncingFor') == -1
      this.$('.js-bouncingfor').prop('disabled', disabled)
    },

    save: function () {
      var self = this

      self.model.save().then(function () {
        self.close()
      })
    },

    delete: function () {
      var self = this

      self.model.destroy().then(function () {
        self.close()
      })
    },

    render: function () {
      var p = Iznik.Views.Modal.prototype.render.call(this)
      p.then(function (self) {
        // We want to refetch the model to make sure we edit the most up to date settings.
        self.model.fetch().then(function () {
          self.fields = [
            {
              name: 'title',
              label: 'Title',
              control: 'input'
            },
            {
              name: 'runevery',
              label: 'Frequency',
              control: 'select',
              options: [
                {label: 'Never', value: 0},
                {label: 'Hourly', value: 1},
                {label: 'Daily', value: 24},
                {label: 'Weekly', value: 168},
                {label: 'Monthly', value: 744}
              ]
            },
            {
              name: 'action',
              label: 'Action',
              control: 'select',
              options: [
                {label: 'Yahoo Unbounce', value: 'Unbounce'},
                {label: 'Yahoo Remove from Group', value: 'Remove'},
                {label: 'Yahoo Change to Group Settings', value: 'ToGroup'},
                {label: 'Yahoo Change to Special Notices', value: 'ToSpecialNotices'}
              ]
            },
            {
              name: 'set',
              label: 'Apply To',
              control: 'select',
              options: [
                {label: 'Members', value: 'Members'}
              ]
            },
            {
              name: 'criterion',
              label: 'Filter',
              control: 'select',
              options: [
                {label: 'Bouncing', value: 'Bouncing'},
                {label: 'Bouncing For', value: 'BouncingFor'},
                {label: 'All', value: 'All'},
                {label: 'Web Only', value: 'WebOnly'}
              ],
              extraClasses: ['js-criterion']
            },
            {
              name: 'bouncingfor',
              label: 'Bouncing For (days)',
              control: 'input',
              type: 'number',
              extraClasses: ['js-bouncingfor']
            }
          ]

          self.form = new Backform.Form({
            el: $('#js-form'),
            model: self.model,
            fields: self.fields
          })

          self.form.render()
          self.criterion()

          self.$('.js-action').val(self.model.get('action'))

          // Layout messes up a bit.
          self.$('.form-group').addClass('clearfix')
          self.$('.js-textarea').attr('rows', 10)

          // Turn on spell-checking
          self.$('textarea, input:text').attr('spellcheck', true)
        })

        self.open(null)
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.BulkOp.Button = Iznik.View.extend({
    template: 'modtools_settings_bulkopbutton',

    tagName: 'li',

    events: {
      'click .js-edit': 'edit'
    },

    edit: function () {
      var self = this

      var v = new Iznik.Views.ModTools.BulkOp({
        model: this.model
      })

      // If we close a modal we might need to refresh.
      this.listenToOnce(v, 'modalClosed', function () {
        self.trigger('buttonChange')
      })
      v.render()
    }
  })

  Iznik.Views.ModTools.Settings.AddLicense = Iznik.Views.Modal.extend({
    template: 'modtools_settings_addlicense',

    events: {
      'click .js-add': 'add',
      'click .js-close': 'close',
      'click .js-cancel': 'cancel'
    },

    licenseFailed: function () {
      var v = new Iznik.Views.ModTools.Settings.LicenseFailed()
      v.render()
    },

    add: function () {
      var self = this

      $.ajax({
        type: 'POST',
        url: API + 'group',
        data: {
          action: 'AddLicense',
          id: self.model.get('id'),
          voucher: self.$('.js-voucher').val().trim()
        }, success: function (ret) {
          if (ret.ret == 0) {
            var v = new Iznik.Views.ModTools.Settings.LicenseSucceeded()
            v.render()
          } else {
            self.licenseFailed()
          }
        }, error: self.licenseFailed
      })
    }
  })

  Iznik.Views.ModTools.Settings.LicenseSucceeded = Iznik.Views.Modal.extend({
    template: 'modtools_settings_licensesucceeded'
  })

  Iznik.Views.ModTools.Settings.LicenseFailed = Iznik.Views.Modal.extend({
    template: 'modtools_settings_licensefailed'
  })

  Iznik.Views.ModTools.Pages.MapSettings = Iznik.Views.Page.extend({
    modtools: true,

    selected: null,

    editing: false,

    template: 'modtools_settings_map',

    events: {
      'click .js-save': 'save',
      'click .js-delete': 'exclude',
      'click #js-shade': 'shade',
      'change #js-showcore': 'changeCore',
      'change #js-showdpa': 'changeDPA',
      'keyup .js-wkt': 'paste',
      'click .js-discard': 'discard',
      'click .js-postcodetest': 'postcodeTest',
      'click .js-postcodeshow': 'postcodeShow'
    },

    cores: [],
    dpas: [],
    mapLabels: [],
    showCore: true,
    showDPA: true,

    changeCore: function () {
      this.showCore = !this.showCore
      this.addGroups()
    },

    changeDPA: function () {
      this.showDPA = !this.showDPA
      this.addGroups()
    },

    discard: function () {
      this.editing = false
      this.getAreas()
    },

    paste: function () {
      this.mapWKT(this.$('.js-wkt').val(), null)
    },

    postcodeShow: function () {
      var self = this

      var circle = {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: 'red',
        fillOpacity: .4,
        scale: 4.5,
        strokeColor: 'white',
        strokeWeight: 1
      }

      self.wait = new Iznik.Views.PleaseWait()
      self.wait.render()

      $.ajax({
        type: 'GET',
        url: API + 'locations',
        data: {
          typeahead: self.$('.js-postcodetoshow').val().trim() + ' ',
          limit: 1000
        }, success: function (ret) {
          self.wait.close()
          if (ret.ret == 0 && ret.locations.length > 0) {
            _.each(ret.locations, function (location) {
              new google.maps.Marker({
                position: {
                  lat: location.lat,
                  lng: location.lng
                },
                icon: circle,
                map: self.map
              })
            })
          }
        }
      })
    },

    postcodeTest: function () {
      var self = this

      $.ajax({
        type: 'GET',
        url: API + 'locations',
        data: {
          typeahead: self.$('.js-postcode').val().trim()
        }, success: function (ret) {
          if (ret.ret == 0 && ret.locations.length > 0) {
            $('.js-postcodegroup').html('Group ' + ret.locations[0].groupsnear[0].nameshort)
            $('.js-postcodearea').html('Area ' + ret.locations[0].area.name)
          } else {
            $('.js-postcodegroup').html('Can\'t find nearby group')
          }
        }
      })
    },

    shade: function () {
      var self = this
      this.shaded = this.shaded ? false : true
      _.each(this.features, function (feature) {
        feature.setOptions({fillOpacity: self.shaded ? 0 : 0.6})
      })
    },

    save: function () {
      var self = this
      var wkt = self.$('.js-wkt').val()
      var name = self.$('.js-name').val()

      var v = new Iznik.Views.PleaseWait({
        timeout: 100
      })
      v.render()

      if (self.selected) {
        // Existing location - patch it.
        var id = self.selected.get('id')
        self.selected.set('polygon', wkt)

        var changes = {
          id: id,
          polygon: wkt
        }
        self.selected.save(changes, {
          patch: true
        }).then(function () {
          v.close()
          self.getAreas()
        })
      } else {
        // New location - create it.
        $.ajax({
          url: API + 'locations',
          type: 'POST',
          headers: {
            'X-HTTP-Method-Override': 'PUT'
          },
          data: {
            name: name,
            polygon: wkt
          }, complete: function () {
            v.close()
            self.getAreas()
          }
        })
      }
    },

    exclude: function () {
      var self = this

      if (self.selected) {
        $.ajax({
          url: API + '/locations/' + self.selected.get('id'),
          type: 'POST',
          data: {
            action: 'Exclude',
            byname: false,
            groupid: self.options.groupid
          }, complete: function () {
            self.getAreas()
          }
        })
      }
    },

    features: [],

    clearMap: function () {
      var i

      this.editing = false

      this.$('.js-wkt').val('')

      for (var i in this.features) {
        if (this.features.hasOwnProperty(i)) {
          this.features[i].setMap(null)
        }
      }

      this.features.length = 0
    },

    getAreas: function () {
      var self = this

      // No longer got one selected.
      self.selected = null
      self.$('.js-wkt').val('')
      self.$('.js-name').val('')
      self.$('.js-name').prop('readonly', false)
      self.$('.js-discard').addClass('disabled')

      var v = new Iznik.Views.PleaseWait({
        timeout: 100
      })
      v.render()

      var bounds = self.map.getBounds()

      self.areas.fetch({
        data: {
          swlat: bounds.getSouthWest().lat(),
          swlng: bounds.getSouthWest().lng(),
          nelat: bounds.getNorthEast().lat(),
          nelng: bounds.getNorthEast().lng()
        }
      }).then(function () {
        v.close()
      })
    },

    updateWKT: function (obj) {
      var self = this
      var wkt = new self.Wkt.Wkt()
      wkt.fromObject(obj)
      this.$('.js-wkt').val(wkt.write())
    },

    changeHandler: function (self, area, obj, edit) {
      return (function (n) {
        // console.log("changeHandler", self, area, obj, edit);
        if (edit) {
          self.editing = edit
          self.$('.js-discard').removeClass('disabled')
        }

        self.selected = area
        self.$('.js-id').val('')
        self.$('.js-wkt').val('')

        if (area) {
          self.$('.js-name').val(area.get('name'))
          self.$('.js-wkt').val(area.get('polygon'))
          self.$('.js-id').html(area.get('id'))
        }

        // We can only edit the name on a new area.
        self.$('.js-name').prop('readonly', area != null)

        self.updateWKT.call(self, obj)

        // Set the border colour so it's obvious which one we're on.
        _.each(self.features, function (feature) {
          feature.setOptions({strokeColor: '#990000'})
        })
        obj.setOptions({strokeColor: 'blue'})

        // Set the area so that it's editable.  We default to non-editable because performance is terrible
        // for editable areas.
        obj.setOptions({editable: true})
      })
    },

    mapWKT: function (wktstr, area, colour) {
      var self = this
      var wkt = new self.Wkt.Wkt()

      try { // Catch any malformed WKT strings
        wkt.read(wktstr)
      } catch (e1) {
        try {
          self.Wkt.read(wktstr.replace('\n', '').replace('\r', '').replace('\t', ''))
        } catch (e2) {
          if (e2.name === 'WKTError') {
            console.error('Ignore invalid WKT', wktstr)
            return
          }
        }
      }

      var obj = null

      try {
        obj = wkt.toObject(this.map.defaults) // Make an object
      } catch (e) {
        console.log('WKT error', wktstr, obj)
      }

      if (obj && !self.Wkt.isArray(obj) && wkt.type !== 'point' && typeof obj.getPath == 'function') {
        // New vertex is inserted
        google.maps.event.addListener(obj.getPath(), 'insert_at', self.changeHandler(self, area, obj, true))

        // Existing vertex is removed (insertion is undone)
        google.maps.event.addListener(obj.getPath(), 'remove_at', self.changeHandler(self, area, obj, true))

        // Existing vertex is moved (set elsewhere)
        google.maps.event.addListener(obj.getPath(), 'set_at', self.changeHandler(self, area, obj, true))

        // Click to show info
        google.maps.event.addListener(obj, 'click', self.changeHandler(self, area, obj, false))

        area.set('obj', obj)
        self.features.push(obj)

        if (area) {
          var mapLabel = new MapLabel({
            text: area.get('name'),
            position: new google.maps.LatLng(area.get('lat'), area.get('lng')),
            map: self.map,
            fontSize: 20,
            fontColor: colour ? colour : 'red',
            align: 'right'
          })

          area.set('label', mapLabel)
          self.mapLabels.push(mapLabel)
        }

        var bounds = new google.maps.LatLngBounds()

        if (self.Wkt.isArray(obj)) { // Distinguish multigeometries (Arrays) from objects
          for (var i in obj) {
            if (obj.hasOwnProperty(i) && !self.Wkt.isArray(obj[i])) {
              obj[i].setMap(self.map)
              this.features.push(obj[i])

              if (self.Wkt.type === 'point' || self.Wkt.type === 'multipoint')
                bounds.extend(obj[i].getPosition())
              else
                obj[i].getPath().forEach(function (element, index) {bounds.extend(element)})
            }
          }

          self.features = self.features.concat(obj)
        } else {
          obj.setMap(this.map) // Add it to the map
          self.features.push(obj)

          if (self.Wkt.type === 'point' || self.Wkt.type === 'multipoint')
            bounds.extend(obj.getPosition())
          else
            obj.getPath().forEach(function (element, index) {bounds.extend(element)})
        }
      }

      return obj
    },

    render: function () {
      var p = Iznik.Views.Page.prototype.render.call(this)
      p.then(function (self) {
        var v = new Iznik.Views.Help.Box()
        v.template = 'modtools_settings_maphelp'
        v.render().then(function (v) {
          self.$('.js-help').html(v.el)
        })

        if (!self.options.groupid) {
          self.$('.js-pergroup').hide()
        }

        require(['wicket'], function (Wkt) {
          self.Wkt = Wkt

          _.defer(function () {
            var group = null
            var p = Iznik.resolvedPromise(self)
            if (self.options.groupid) {
              group = new Iznik.Models.Group({
                id: self.options.groupid
              })

              p = group.fetch()
            }

            p.then(function () {
              var centre = !group ? new google.maps.LatLng(53.9450, -2.5209) : new google.maps.LatLng(group.get('lat'), group.get('lng'))

              var options = {
                center: centre,
                zoom: self.options.groupid ? 14 : 5,
                defaults: {
                  icon: '/images/red_dot.png',
                  shadow: '/images/dot_shadow.png',
                  strokeColor: '#990000',
                  fillColor: '#EEFFCC',
                  fillOpacity: 0.6
                },
                disableDefaultUI: true,
                mapTypeControl: false,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                mapTypeControlOptions: {
                  position: google.maps.ControlPosition.TOP_LEFT,
                  style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                },
                panControl: false,
                streetViewControl: false,
                zoomControl: true,
                minZoom: self.options.groupid ? 11 : 0,
                zoomControlOptions: {
                  position: google.maps.ControlPosition.LEFT_TOP,
                  style: google.maps.ZoomControlStyle.SMALL
                }
              }

              // Set square.
              $('#map').css('height', $('#map').innerWidth())

              self.map = new google.maps.Map(document.getElementById('map'), options)

              if (self.options.groupid) {
                self.map.drawingManager = new google.maps.drawing.DrawingManager({
                  drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_RIGHT,
                    drawingModes: [
                      google.maps.drawing.OverlayType.POLYGON
                    ]
                  },
                  markerOptions: self.map.defaults,
                  polygonOptions: self.map.defaults,
                  polylineOptions: self.map.defaults,
                  rectangleOptions: self.map.defaults
                })
                self.map.drawingManager.setMap(self.map)

                google.maps.event.addListener(self.map.drawingManager, 'overlaycomplete', function (event) {
                  var wkt

                  // Set the drawing mode to "pan" (the hand) so users can immediately edit
                  this.setDrawingMode(null)

                  // Polygon drawn
                  var obj = event.overlay
                  var area = self.selected

                  if (event.type === google.maps.drawing.OverlayType.POLYGON || event.type === google.maps.drawing.OverlayType.POLYLINE) {
                    // New vertex is inserted
                    google.maps.event.addListener(obj.getPath(), 'insert_at', self.changeHandler(self, area, obj, true))

                    // Existing vertex is removed (insertion is undone)
                    google.maps.event.addListener(obj.getPath(), 'remove_at', self.changeHandler(self, area, obj, true))

                    // Existing vertex is moved (set elsewhere)
                    google.maps.event.addListener(obj.getPath(), 'set_at', self.changeHandler(self, area, obj, true))

                    // Click to show info
                    google.maps.event.addListener(obj, 'click', self.changeHandler(self, area, obj, false))
                  }

                  self.features.push(event.overlay)
                  self.changeHandler(self, area, obj, false)()
                })
              }

              // Searchbox
              var input = document.getElementById('pac-input')
              self.searchBox = new google.maps.places.SearchBox(input)
              self.map.controls[google.maps.ControlPosition.TOP_CENTER].push(input)

              self.map.addListener('bounds_changed', function () {
                self.searchBox.setBounds(self.map.getBounds())
              })

              self.searchBox.addListener('places_changed', function () {
                // Put the map here.
                var places = self.searchBox.getPlaces()

                if (places.length == 0) {
                  return
                }

                var bounds = new google.maps.LatLngBounds()
                places.forEach(function (place) {
                  if (place.geometry.viewport) {
                    // Only geocodes have viewport.
                    bounds.union(place.geometry.viewport)
                  } else {
                    bounds.extend(place.geometry.location)
                  }
                })

                self.map.fitBounds(bounds)
              })

              if (self.options.groupid) {
                // We show areas for the current map.
                self.areas = new Iznik.Collections.Locations()
                self.listenTo(self.areas, 'add', function (area) {
                  var poly = area.get('polygon')
                  var lat = area.get('lat')
                  var lng = area.get('lng')

                  if (poly || lat || lng) {
                    if (poly) {
                      if (poly.indexOf('MULTIPOLYGON') !== -1) {
                        // These break WKT.
                        console.log('Skip MULTIPOLYGON', area.get('name'))
                      } else {
                        self.mapWKT(poly, area)
                      }
                    } else {
                      var wkt = 'POINT(' + lng + ' ' + lat + ')'
                      self.mapWKT(wkt, area)
                    }
                  }
                })

                self.listenTo(self.areas, 'remove', function (area) {
                  var obj = area.get('obj')
                  if (!_.isUndefined(obj)) {
                    var oldlen = self.features.length
                    self.features = _.without(self.features, obj)
                    console.log('Removed', obj, oldlen, self.features.length)
                    obj.setMap(null)
                  }
                })

                google.maps.event.addListener(self.map, 'idle', _.bind(function () {
                  if (!self.editing) {
                    self.getAreas()
                  }
                }, self))
              } else {
                // We just want all the groups.
                google.maps.event.addDomListener(self.map, 'idle', function () {
                  if (!self.fetched) {
                    // Get all the groups.
                    self.allGroups = new Iznik.Collections.Group()
                    self.allGroups.fetch({
                      data: {
                        grouptype: 'Freegle',
                        official: true
                      }
                    }).then(function () {
                      self.fetched = true
                      self.addGroups()
                    })
                  }
                })

              }
            })
          })
        })
      })

      return (p)
    },

    addGroups: function () {
      var self = this

      _.each(self.cores, function (core) {
        if (core) {
          core.setMap(null)
        }
      })
      _.each(self.dpas, function (dpa) {
        if (dpa) {
          dpa.setMap(null)
        }
      })
      _.each(self.mapLabels, function (label) {
        if (label) {
          label.setMap(null)
        }
      })
      self.cores = []
      self.dpas = []
      self.mapLabels = []

      // Add a polygon for each
      self.allGroups.each(function (group) {
        var poly = group.get('poly')
        var polyofficial = group.get('polyofficial')
        var diff = polyofficial && polyofficial != poly

        if (diff) {
          group.set('name', group.get('nameshort') + ' Default Posting Area')
        } else {
          group.set('name', group.get('nameshort'))
        }

        if (self.showDPA) {
          self.cores.push(self.mapWKT(poly, group))
        }

        if (self.showCore) {
          var group2 = group.clone()
          group2.set('name', group.get('nameshort') + ' Core Group Area')
          self.dpas.push(self.mapWKT(polyofficial, group2, 'blue'))
        }
      })
    }
  })

  Iznik.Views.ModTools.Settings.MissingProfile = Iznik.View.extend({
    template: 'modtools_settings_missingprofile',

    render: function () {
      var self = this
      var p
      var missingProfile = []
      var groups = Iznik.Session.get('groups')
      groups.each(function (group) {
        var role = group.get('role')
        if (group.get('type') == 'Freegle' && (role == 'Moderator' || role == 'Owner') &&
          (!group.get('profile') || !group.get('tagline'))) {
          missingProfile.push(group.get('namedisplay'))
        }
      })

      if (missingProfile.length > 0) {
        var p = Iznik.View.prototype.render.call(this)
        require(['jquery-show-first'], function () {
          p.then(function (self) {
            _.each(missingProfile, function (missing) {
              self.$('.js-grouplist').append('<div>' + missing + '</div>')
            })
            self.$('.js-grouplist').showFirst({
              controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
              count: 5
            })

            self.$('.js-profile').fadeIn('slow')
          })
        })
      } else {
        p = Iznik.resolvedPromise(this)
      }

      return (p)
    }
  })

  Iznik.Views.ModTools.Settings.MissingTwitter = Iznik.View.extend({
    template: 'modtools_settings_missingtwitter',

    render: function () {
      var self = this
      var p
      var missingTwitter = []
      var groups = Iznik.Session.get('groups')
      groups.each(function (group) {
        var role = group.get('role')
        if (group.get('type') == 'Freegle' && (role == 'Moderator' || role == 'Owner')) {
          if (!group.get('twitter')) {
            missingTwitter.push(group.get('namedisplay') + ' - not linked')
          } else if (!group.get('twitter').valid) {
            missingTwitter.push(group.get('namedisplay') + ' - token invalid')
          } else if (group.get('twitter').locked) {
            missingTwitter.push(group.get('namedisplay') + ' - account locked')
          }
        }
      })

      if (missingTwitter.length > 0) {
        var p = Iznik.View.prototype.render.call(this)
        require(['jquery-show-first'], function () {
          p.then(function (self) {
            _.each(missingTwitter, function (missing) {
              self.$('.js-grouplist').append('<div>' + missing + '</div>')
            })
            self.$('.js-grouplist').showFirst({
              controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
              count: 5
            })

            self.$('.js-profile').fadeIn('slow')
          })
        })
      } else {
        p = Iznik.resolvedPromise(this)
      }

      return (p)
    }
  })

  Iznik.Views.ModTools.Settings.MissingFacebook = Iznik.View.extend({
    template: 'modtools_settings_missingfacebook',

    render: function () {
      var self = this
      var p
      var missingFacebook = []
      var groups = Iznik.Session.get('groups')
      groups.each(function (group) {
        var role = group.get('role')
        if (group.get('type') == 'Freegle' && (role == 'Moderator' || role == 'Owner')) {
          var facebooks = group.get('facebook')
          var gotpage = false
          _.each(facebooks, function (facebook) {
            if (facebook.type == 'Page') {
              if (!facebook.valid) {
                missingFacebook.push(facebook.name + ' - token invalid')
              } else {
                gotpage = true
              }
            }
          })

          if (!gotpage) {
            missingFacebook.push(group.get('namedisplay') + ' - not linked')
          }
        }
      })

      if (missingFacebook.length > 0) {
        var p = Iznik.View.prototype.render.call(this)
        require(['jquery-show-first'], function () {
          p.then(function (self) {
            _.each(missingFacebook, function (missing) {
              self.$('.js-grouplist').append('<div>' + missing + '</div>')
            })
            self.$('.js-grouplist').showFirst({
              controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
              count: 5
            })

            self.$('.js-profile').fadeIn('slow')
          })
        })
      } else {
        p = Iznik.resolvedPromise(this)
      }

      return (p)
    }
  })

  Iznik.Views.ModTools.Settings.GroupDescription = Iznik.Views.Modal.extend({
    template: 'modtools_settings_description',

    events: {
      'click .js-save': 'save'
    },

    save: function () {
      var self = this
      var desc = tinyMCE.activeEditor.getContent({format: 'html'})

      $.ajax({
        url: API + 'group',
        type: 'POST',
        headers: {
          'X-HTTP-Method-Override': 'PATCH'
        },
        data: {
          id: self.model.get('id'),
          description: desc
        }, success: function () {
          self.close()
        }
      })
    },

    removeEditors: function () {
      function removeTinyMCEInstance (editor) {
        var oldLength = tinymce.editors.length
        tinymce.remove(editor)
        if (oldLength == tinymce.editors.length) {
          tinymce.editors.remove(editor)
        }
      }

      for (var i = tinymce.editors.length - 1; i > -1; i--) {
        removeTinyMCEInstance(tinymce.editors[i])
      }
    },

    render: function () {
      var self = this
      self.removeEditors()

      // Magic from http://stackoverflow.com/questions/18111582/tinymce-4-links-plugin-modal-in-not-editable to
      // make the links work in modal.
      $(document).on('focusin', function (e) {
        if ($(e.target).closest('.mce-window').length || $(e.target).closest('.moxman-window').length) {
          e.stopImmediatePropagation()
        }
      })

      var p = Iznik.Views.Modal.prototype.render.call(this)
      p.then(function (self) {
        self.waitDOM(self, function () {
          self.$('#groupdescription').val(self.model.get('description'))

          tinyMCE.init({
            selector: '#groupdescription',
            plugins: 'link textcolor code',
            height: 300,
            menubar: false,
            elementpath: false,
            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright |  bullist numlist link | forecolor styleselect formatselect fontselect fontsizeselect | cut copy paste | code'
          })
        })
      })

      return (p)
    }
  })

  Iznik.Views.ModTools.Settings.GroupFacebook = Iznik.View.extend({
    tagName: 'li',

    template: 'modtools_settings_groupfacebook',

    events: {
      'click .js-delete': 'deleteIt'
    },

    deleteIt: function () {
      var self = this

      self.options.group.removeFacebookGroup(self.model.get('uid')).then(function () {
        self.$el.fadeOut('slow')
      })
    },

    render: function () {
      var self = this

      var p = Iznik.View.prototype.render.call(this)
      p.then(function () {
        var facebook = self.model.attributes
        self.$('.js-facebookname').html(facebook.name)
        self.$('.js-facebookurl').attr('href', 'https://facebook.com/' + facebook.id)

        if (!facebook.valid) {
          self.$('.js-facebooknotlinked').show()
        } else {
          var mom = new moment(facebook.authdate)
          self.$('.js-facebookauthdate').html(mom.format('ll'))
          self.$('.js-facebookvalid').show()
        }
      })
    }
  })
})