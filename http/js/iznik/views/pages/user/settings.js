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
            'changeDate .js-onholidaytill': 'onholidaytill',
            'keyup .js-name': 'nameChange',
            'keyup .js-email': 'emailChange'
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
            console.log("On holiday till", till, me);
            if (this.$('.js-switch').bootstrapSwitch('state')) {
                this.$('.js-onholidaytill').show();
                this.$('.js-until').show();
                this.$('.js-onholidaytill').datepicker('update', till);
            } else {
                this.$('.js-onholidaytill').val('1970-01-01T00:00:00Z');
                this.$('.js-onholidaytill').hide();
                this.$('.js-until').hide();
                console.log("Not on holiday - clear");

                Iznik.Session.save({
                    id: me.id,
                    onholidaytill: null
                }, {
                    patch: true
                });
            }
        },

        nameChange: function(e) {
            var self = this;
            self.$('.js-name').removeClass('error-border');
            if (e.which === 13) {
                var name = this.$('.js-name').val();
                if (name.length > 0) {
                    var me = Iznik.Session.get('me');
                    me.displayname = name;
                    Iznik.Session.set('me', me);
                    Iznik.Session.save({
                        id: me.id,
                        displayname: name
                    }, {
                        patch: true
                    }).then(function() {
                        self.$('.js-nameok').fadeIn();
                    });
                } else {
                    self.$('.js-name').addClass('error-border');
                    self.$('.js-name').focus();
                }
            }
        },

        emailChange: function(e) {
            self.$('.js-email').removeClass('error-border');
            if (e.which === 13) {
                var email= this.$('.js-email').val();
                if (email.length > 0 && isValidEmailAddress(email)) {
                    var me = Iznik.Session.get('me');
                    me.email = email;
                    Iznik.Session.set('me', me);
                    Iznik.Session.save({
                        id: me.id,
                        email: email
                    }, {
                        patch: true
                    }).then(function() {
                        self.$('.js-emailok').fadeIn();
                    });
                } else {
                    self.$('.js-email').addClass('error-border');
                    self.$('.js-email').focus();
                }
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.User.Pages.WhereAmI.prototype.render.call(this, {
                model: new Iznik.Model(Iznik.Session.get('settings'))
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
                console.log("Settings me ", JSON.parse(JSON.stringify(me)));
                self.$('.js-name').val(me.displayname);
                self.$('.js-email').val(me.email);

                console.log("On holiday?", me.onholidaytill, me.onholidaytill != undefined);
                self.$(".js-switch").bootstrapSwitch({
                    onText: 'Mails Paused',
                    offText: 'Mails On',
                    state: me.onholidaytill != undefined
                });
                self.onholiday();

                self.groupscoll = Iznik.Session.get('groups');
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-mailgroups'),
                    modelView: Iznik.Views.User.Settings.Group,
                    collection: self.groupscoll,
                    visibleModelsFilter: function(group) {
                        // Only show Freegle groups in the UI.
                        return(group.get('type') == 'Freegle')
                    }
                });

                self.collectionView.render();
            });

            return (p);
        }
    });

    Iznik.Views.User.Settings.Group = Iznik.View.extend({
        template: "user_settings_group",

        events: {
            'change .js-frequency': 'changeFreq',
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
                        userid: self.model.get('id'),
                        groupid: self.model.get('groupid')
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
        
        changeFreq: function() {
            var self = this;
            var me = Iznik.Session.get('me');
            var data = {
                userid: me.id,
                groupid: self.model.get('id'),
                emailfrequency: self.$('.js-frequency').val()
            };

            console.log("Settings change data", data);

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
            })
        }
    });
});