define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/help',
    'bootstrap-switch',
    'bootstrap-datepicker'
], function($, _, Backbone, Iznik) {
    // We extend WhereAmI to get the location-choosing code.
    Iznik.Views.User.Pages.Settings = Iznik.Views.User.Pages.WhereAmI.extend({
        template: "user_settings_main",
        
        getLocation: function() {
            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        events: {
            'switchChange.bootstrapSwitch .js-onholiday': 'onholiday',
            'switchChange.bootstrapSwitch .js-emailswitch': 'notifSwitch',
            'switchChange.bootstrapSwitch .js-emailmineswitch': 'notifSwitch',
            'switchChange.bootstrapSwitch .js-pushswitch': 'notifSwitch',
            'switchChange.bootstrapSwitch .js-appswitch': 'notifSwitch',
            'switchChange.bootstrapSwitch .js-facebookswitch': 'notifSwitch',
            'switchChange.bootstrapSwitch .js-relevant': 'relevantSwitch',
            'switchChange.bootstrapSwitch .js-newsletter': 'newsletterSwitch',
            'switchChange.bootstrapSwitch #useprofile': 'useProfileSwitch',
            'switchChange.bootstrapSwitch .js-notificationmails': 'notificationSwitch',
            'changeDate .js-onholidaytill': 'onholidaytill',
            'keyup .js-name': 'nameChange',
            'click .js-savename': 'nameChange',
            'click .js-savepostcode': 'locChange',
            'keyup .js-email': 'emailChange',
            'click .js-saveemail': 'emailChange',
            'keyup .js-password': 'passwordChange',
            'click .js-savepassword': 'passwordChange',
            'click .js-showpassword': 'showPassword',
            'click .js-hidepassword': 'hidePassword',
            'click .js-profile': 'showProfile'
        },

        showProfile: function() {
            var self = this;

            require([ 'iznik/views/user/user' ], function() {
                var v = new Iznik.Views.UserInfo({
                    model: new Iznik.Model(Iznik.Session.get('me'))
                });

                v.render();
            });
        },

        showPassword: function() {
            this.$('.js-password').attr('type', 'text');
            this.$('.js-showpassword').hide();
            this.$('.js-hidepassword').show();
        },

        hidePassword: function() {
            this.$('.js-password').attr('type', 'password');
            this.$('.js-hidepassword').hide();
            this.$('.js-showpassword').show();
        },

        onholidaytill: function() {
            var me = Iznik.Session.get('me');
            var till = this.$('.js-onholidaytill').datepicker('getUTCDates');
            till = (new Date(Date.parse(till)).toISOString());
            this.$('.js-onholidaytill').datepicker('hide');

            Iznik.Session.save({
                id: me.id,
                onholidaytill: till
            }, {
                patch: true
            });
        },

        onholiday: function() {
            var me = Iznik.Session.get('me');
            var till = me.onholidaytill ? new Date(me.onholidaytill) : new Date();
            // console.log("On holiday till", till, me);
            if (this.$('.js-holidayswitch').bootstrapSwitch('state')) {
                this.$('.js-onholidaytill').show();
                this.$('.js-until').show();
                this.$('.js-onholidaytill').datepicker('update', till);
            } else {
                this.$('.js-onholidaytill').val('1970-01-01T00:00:00Z');
                this.$('.js-onholidaytill').hide();
                this.$('.js-until').hide();
                // console.log("Not on holiday - clear");

                Iznik.Session.save({
                    id: me.id,
                    onholidaytill: null
                }, {
                    patch: true
                });
            }
        },

        showHideMine: function() {
            if (!this.$('.js-emailswitch').bootstrapSwitch('state')) {
                this.$('.js-mineholder').hide();
            } else {
                this.$('.js-mineholder').show();
            }
        },

        relevantSwitch: function() {
            var me = Iznik.Session.get('me');
            var relevant = this.$('.js-relevant').bootstrapSwitch('state');

            Iznik.Session.save({
                id: me.id,
                relevantallowed: relevant
            }, {
                patch: true
            });
        },

        notificationSwitch: function() {
            var me = Iznik.Session.get('me');
            var notif = this.$('.js-notificationmails').bootstrapSwitch('state');

            var me = Iznik.Session.get('me');
            me.settings.notificationmails = notif;

            Iznik.Session.save({
                id: me.id,
                settings: me.settings
            }, {
                patch: true
            }).then(function() {
                Iznik.Session.fetch();
            });
        },

        useProfileSwitch: function() {
            var me = Iznik.Session.get('me');
            var profile = this.$('#useprofile').bootstrapSwitch('state');

            var me = Iznik.Session.get('me');
            me.settings.useprofile = profile;

            Iznik.Session.save({
                id: me.id,
                settings: me.settings
            }, {
                patch: true
            }).then(function() {
                Iznik.Session.fetch().then(function() {
                    self.$('.js-profileimg').attr('src', Iznik.Session.get('me').profile.url);
                })
            });
        },

        newsletterSwitch: function() {
            var me = Iznik.Session.get('me');
            var newsletter = this.$('.js-newsletter').bootstrapSwitch('state');

            Iznik.Session.save({
                id: me.id,
                newslettersallowed: newsletter
            }, {
                patch: true
            });
        },

        notifSwitch: function() {
            var me = Iznik.Session.get('me');
            var notifs = {};
            notifs.email = this.$('.js-emailswitch').bootstrapSwitch('state');
            notifs.emailmine = this.$('.js-emailmineswitch').bootstrapSwitch('state');
            notifs.app = this.$('.js-appswitch').bootstrapSwitch('state');
            notifs.push = this.$('.js-pushswitch').bootstrapSwitch('state');
            notifs.facebook = this.$('.js-facebookswitch').bootstrapSwitch('state');

            this.showHideMine();

            me.settings.notifications = notifs;

            Iznik.Session.save({
                id: me.id,
                settings: me.settings
            }, {
                patch: true
            });
        },

        startSave: function(el) {
            $(el).find('.glyphicon-floppy-save').removeClass('glyphicon-floppy-save').addClass('glyphicon-refresh rotate');
        },

        endSave: function(el) {
            $(el).find('.glyphicon-refresh').removeClass('glyphicon-refresh rotate').addClass('glyphicon-floppy-saved');
        },

        nameChange: function(e) {
            var self = this;
            self.$('.js-name').removeClass('error-border');
            if (e.type == 'click' || e.which === 13) {
                var name = this.$('.js-name').val();
                if (name.length > 0) {
                    self.startSave(self.$('.js-savename'));
                    var me = Iznik.Session.get('me');
                    me.displayname = name;
                    Iznik.Session.set('me', me);
                    Iznik.Session.save({
                        id: me.id,
                        displayname: name
                    }, {
                        patch: true,
                        success: function(model, response, options) {
                            if (response.ret == 0) {
                                self.endSave(self.$('.js-savename'));
                            }
                        }
                    });
                } else {
                    self.$('.js-name').addClass('error-border');
                    self.$('.js-name').focus();
                }
            }
        },

        emailChange: function(e) {
            var self = this;
            self.$('.js-bouncing').hide();
            self.$('.js-email').removeClass('error-border');
            if (e.type == 'click' || e.which === 13) {
                self.$('.js-verifyemail').hide();
                var email= this.$('.js-email').val();
                if (email.length > 0 && isValidEmailAddress(email)) {
                    self.startSave(self.$('.js-saveemail'));
                    var me = Iznik.Session.get('me');
                    me.email = email;
                    Iznik.Session.set('me', me);
                    Iznik.Session.save({
                        id: me.id,
                        email: email
                    }, {
                        patch: true,
                        success: function(model, response, options) {
                            if (response.ret == 0) {
                                self.endSave(self.$('.js-saveemail'));
                            } else if (response.ret == 10) {
                                self.endSave(self.$('.js-saveemail'));
                                self.$('.js-verifyemail').fadeIn('slow');
                            }
                        }
                    });
                } else {
                    self.$('.js-email').addClass('error-border');
                    self.$('.js-email').focus();
                }
            }
        },

        passwordChange: function(e) {
            var self = this;
            self.$('.js-password').removeClass('error-border');
            if (e.type == 'click' || e.which === 13) {
                var password = this.$('.js-password').val();
                if (password.length > 0) {
                    self.startSave(self.$('.js-savepassword'));
                    var me = Iznik.Session.get('me');
                    Iznik.Session.set('me', me);
                    Iznik.Session.save({
                        id: me.id,
                        password: password
                    }, {
                        patch: true,
                        success: function(model, response, options) {
                            if (response.ret == 0) {
                                self.endSave(self.$('.js-savepassword'));
                            }
                        }
                    });
                } else {
                    self.$('.js-password').addClass('error-border');
                    self.$('.js-password').focus();
                }
            }
        },

        render: function () {
            var self = this;

            var settings = Iznik.Session.get('me').settings;
            console.log("Settings", settings);

            var p = Iznik.Views.User.Pages.WhereAmI.prototype.render.call(this, {
                model: new Iznik.Model(settings)
            });

            p.then(function() {
                var v = new Iznik.Views.Help.Box();
                v.template = 'user_settings_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                });

                self.$('abbr.timeago').timeago();
                self.$('.datepicker').datepicker({
                    format: 'D, dd MM yyyy',
                    startDate: '0d',
                    endDate: '+30d'
                });

                var me = Iznik.Session.get('me');
                self.$('.js-name').val(me.displayname);

                // Profile
                self.$("#useprofile").bootstrapSwitch({
                    onText: 'Shown',
                    offText: 'Hidden',
                    state: settings.hasOwnProperty('useprofile') && settings.useprofile
                });

                self.$('.js-profileimg').attr('src', me.profile.url);

                // File upload
                self.$('.js-profileupload').fileinput({
                    uploadExtraData: {
                        imgtype: 'User',
                        msgid: Iznik.Session.get('me').id,
                        user: 1
                    },
                    showUpload: false,
                    allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                    uploadUrl: API + 'image',
                    resizeImage: true,
                    maxImageWidth: 200,
                    browseLabel: 'Upload photo',
                    browseClass: 'btn btn-primary nowrap',
                    browseIcon: '<span class="glyphicon glyphicon-camera" />&nbsp;',
                    showCaption: false,
                    showRemove: false,
                    showCancel: false,
                    showPreview: true,
                    showUploadedThumbs: false,
                    dropZoneEnabled: false,
                    buttonLabelClass: '',
                    fileActionSettings: {
                        showZoom: false,
                        showRemove: false,
                        showUpload: false
                    },
                    layoutTemplates: {
                        footer: '<div class="file-thumbnail-footer">\n' +
                        '    {actions}\n' +
                        '</div>'
                    }
                });

                // Upload as soon as we have it.
                self.$('.js-profileupload').on('fileimagesresized', function (event) {
                    // Upload as soon as we have it.
                    self.$('.js-profileimg').attr('src', '/images/userloader.gif');

                    $('.file-preview, .kv-upload-progress').hide();
                    self.$('.js-profileupload').fileinput('upload');
                });

                // Watch for all uploaded
                self.$('.js-profileupload').on('fileuploaded', function(event, data) {
                    self.$('.js-profileimg').attr('src', data.response.path);
                    Iznik.Session.fetch();
                });

                self.$('.js-email').val(me.email);

                if (me.bouncing) {
                    self.$('.js-bouncing').fadeIn('slow');
                }

                // console.log("On holiday?", me.onholidaytill, me.onholidaytill != undefined);
                self.$(".js-holidayswitch").bootstrapSwitch({
                    onText: 'Mails&nbsp;Paused',
                    offText: 'Mails&nbsp;On',
                    state: me.onholidaytill != undefined
                });
                self.onholiday();

                self.$(".js-relevant").bootstrapSwitch({
                    onText: 'Send them',
                    offText: 'No thanks',
                    state: me.relevantallowed ? true : false
                });

                self.$(".js-notificationmails").bootstrapSwitch({
                    onText: 'Send them',
                    offText: 'No thanks',
                    state: me.settings.notificationmails ? true : false
                });

                self.$(".js-newsletter").bootstrapSwitch({
                    onText: 'Send them',
                    offText: 'No thanks',
                    state: me.newslettersallowed ? true : false
                });

                var notifs = me.settings.notifications;

                if (_.isUndefined(notifs)) {
                    notifs = {
                        email: true,
                        push: true,
                        facebook: true
                    }
                }

                self.$(".js-emailswitch").bootstrapSwitch({
                    onText: 'Emails&nbsp;On',
                    offText: 'Emails&nbsp;Off',
                    state: notifs.hasOwnProperty('email') ? notifs.email : true
                });

                self.$(".js-emailmineswitch").bootstrapSwitch({
                    onText: 'Yes&nbsp;Please',
                    offText: 'No&nbsp;Thanks',
                    state: notifs.hasOwnProperty('emailmine') ? notifs.emailmine : false
                });

                self.showHideMine();

                if (me.hasOwnProperty('notifications')) {
                    self.$(".js-pushswitch").bootstrapSwitch({
                        onText: 'Browser&nbsp;Popups&nbsp;On',
                        offText: 'Browser&nbsp;Popups&nbsp;Off',
                        state: notifs.hasOwnProperty('push') ? notifs.push: true
                    });

                    self.$('.js-pushon').show();
                }

                if (me.hasOwnProperty('notifications')) {
                    self.$(".js-appswitch").bootstrapSwitch({
                        onText: 'App&nbsp;Notifications&nbsp;On',
                        offText: 'App&nbsp;Notifications&nbsp;Off',
                        state: notifs.hasOwnProperty('app') ? notifs.app: true
                    });

                    self.$('.js-appon').show();
                }

                var facebook = Iznik.Session.hasFacebook();
                
                if (facebook) {
                    self.$(".js-facebookswitch").bootstrapSwitch({
                        onText: 'Facebook&nbsp;Notifications&nbsp;On',
                        offText: 'Facebook&nbsp;Notifications&nbsp;Off',
                        state: notifs.hasOwnProperty('facebook') ? notifs.facebook: true
                    });

                    self.$('.js-facebookon').show();
                }

                self.groupscoll = Iznik.Session.get('groups');
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-mailgroups'),
                    modelView: Iznik.Views.User.Settings.Group,
                    collection: self.groupscoll,
                    visibleModelsFilter: function(group) {
                        // Only show Freegle groups in the UI.
                        return(group.get('type') == 'Freegle')
                    },
                    processKeyEvents: false
                });

                self.collectionView.render();

                self.delegateEvents();
            });

            return (p);
        }
    });

    Iznik.Views.User.Settings.Group = Iznik.View.extend({
        tagName: 'li',

        template: "user_settings_group",

        events: {
            'change .js-frequency': 'changeFreq',
            'change .js-events': 'changeEvents',
            'change .js-volunteering': 'changeVolunteering',
            'click .js-leave': 'leave'
        },

        leave: function() {
            var self = this;
            var v = new Iznik.Views.Confirm({
                model: self.model
            });
            v.template = 'user_settings_leave';

            self.listenToOnce(v, 'confirmed', function() {
                $.ajax({
                    url: API + 'memberships',
                    type: 'DELETE',
                    data: {
                        userid: Iznik.Session.get('me').id,
                        groupid: self.model.get('id')
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            // Refresh the session to pick up the loss of our group.
                            self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                                self.$el.fadeOut('slow');
                                self.model.trigger('removed');
                            });
                            
                            Iznik.Session.testLoggedIn();
                        }
                    }
                });
            });

            v.render();
        },
        
        changeFreq: function(e) {
            var self = this;
            var me = Iznik.Session.get('me');
            var data = {
                userid: me.id,
                groupid: self.model.get('id'),
                emailfrequency: $(e.target).val()
            };

            $.ajax({
                url: API + 'memberships',
                type: 'PATCH',
                data: data,
                success: function(ret) {
                    if (ret.ret === 0) {
                        self.$('.js-ok').removeClass('hidden');
                    }
                }
            });
        },

        changeEvents: function(e) {
            var self = this;
            var me = Iznik.Session.get('me');
            var data = {
                userid: me.id,
                groupid: self.model.get('id'),
                eventsallowed: $(e.target).val()
            };

            $.ajax({
                url: API + 'memberships',
                type: 'PATCH',
                data: data,
                success: function(ret) {
                    if (ret.ret === 0) {
                        self.$('.js-ok').removeClass('hidden');
                    }
                }
            });
        },

        changeVolunteering: function(e) {
            var self = this;
            var me = Iznik.Session.get('me');
            var data = {
                userid: me.id,
                groupid: self.model.get('id'),
                volunteeringallowed: $(e.target).val()
            };

            $.ajax({
                url: API + 'memberships',
                type: 'PATCH',
                data: data,
                success: function(ret) {
                    if (ret.ret === 0) {
                        self.$('.js-ok').removeClass('hidden');
                    }
                }
            });
        },

        render: function() {
            var self = this;
            Iznik.View.prototype.render.call(this).then(function() {
                var freq = parseInt(self.model.get('mysettings').emailfrequency);
                self.$('.js-frequency').val(freq);
                var events = parseInt(self.model.get('mysettings').eventsallowed);
                self.$('.js-events').val(events);
                var volunteering = parseInt(self.model.get('mysettings').volunteeringallowed);
                self.$('.js-volunteering').val(volunteering);
            })
        }
    });

    Iznik.Views.User.Pages.Settings.VerifyFailed = Iznik.Views.Modal.extend({
        template: 'user_settings_verifyfailed'
    });

    Iznik.Views.User.Pages.Settings.VerifySucceeded = Iznik.Views.Modal.extend({
        template: 'user_settings_verifysucceeded'
    });

    Iznik.Views.User.Pages.Settings.NoEmail = Iznik.Views.Modal.extend({
        template: 'user_settings_noemail',

        events: {
            'click .js-save': 'save'
        },

        save: function() {
            var self = this;
            self.$('.js-email').removeClass('error-border');
            self.$('.js-verifyemail').hide();
            var email= this.$('.js-email').val();
            if (email.length > 0 && isValidEmailAddress(email)) {
                var me = Iznik.Session.get('me');
                me.email = email;
                Iznik.Session.set('me', me);
                Iznik.Session.save({
                    id: me.id,
                    email: email
                }, {
                    patch: true,
                    success: function(model, response, options) {
                        if (response.ret == 0) {
                            self.close();
                        } else if (response.ret == 10) {
                            self.$('.js-verifyemail').fadeIn('slow');
                            self.$('.js-close').hide();
                        }
                    }
                });
            } else {
                self.$('.js-email').addClass('error-border');
                self.$('.js-email').focus();
            }
        }
    });
});