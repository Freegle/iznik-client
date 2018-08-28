define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/models/schedule',
    'jquery.scrollTo'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Schedule = Iznik.View.extend({
        template: 'user_schedule_one',

        slots: [],

        hasChanged: false,

        events: {
            'click .js-confirm': 'save',
            'click .js-cancel': 'cancel',
            'click .js-neveragain': 'neverAgain',
            'click .js-slots': 'changed'
        },

        neverAgain: function() {
            Storage.set('dontaskschedule', true);
            this.cancel();
        },

        cancel: function () {
            var self = this;
            Iznik.ABTestAction('Schedule', 'Cancel');

            var myid = Iznik.Session.get('me').id;
            var some = false;

            _.each(self.slots, function (slot) {
                if (slot.get('available')) {
                    some = true;
                }
            });

            if (some && self.options.mine) {
                // Always save to server, which may trigger a chat message.  We need this for when someone replies
                // and cancels out - we still want to send an availability message if they have some availability.
                self.model.save({
                    userid: myid,
                    chatuserid: self.options.chatuserid
                }).then(function () {
                    self.trigger('saved');
                })
            }

            this.trigger('cancelled');
        },

        save: function () {
            var self = this;
            Iznik.ABTestAction('Schedule', 'Save');

            var myid = Iznik.Session.get('me').id;
            var slots = [];

            _.each(self.slots, function (slot) {
                var d = new moment(slot.get('date'));
                d.hour(slot.get('hour'));
                d = d.format();

                slots.push({
                    hour: slot.get('hour'),
                    date: d,
                    available: slot.get('available')
                });
            });

            // Always save to server, which may trigger a chat message.  We need this for when someone replies
            // and makes no changes to their availability.
            self.model.set('schedule', slots);
            self.model.save({
                userid: myid,
                chatuserid: self.options.chatuserid
            }).then(function () {
                self.trigger('saved');
            })
        },

        render: function () {
            var self = this;
            self.slots = [];

            var me = Iznik.Session.get('me');
            var myid = me ? me.id : null;

            Iznik.ABTestShown('Schedule', 'Save');
            Iznik.ABTestShown('Schedule', 'Cancel');

            self.model = new Iznik.Models.Schedule();
            self.othermodel = self.options.chatuserid ? new Iznik.Models.Schedule() : null;

            var promises = [];

            promises.push(self.model.fetch({
                data: {
                    userid: self.options.mine ? null : self.options.otheruser.get('id')
                }
            }));

            if (self.options.chatuserid) {
                promises.push(self.othermodel.fetch({
                    data: {
                        userid: self.options.chatuserid
                    }
                }));
            }

            var p = Promise.all(promises);

            p.then(function() {
                self.model.set('mine', self.options.mine);

                Iznik.View.prototype.render.call(self).then(function () {
                    if (self.options.cancel) {
                        self.$('.js-cancel').show();
                        self.$('.js-neveragain').show();
                    }

                    if (self.options.help) {
                        var v = new Iznik.Views.Help.Box();
                        v.template = 'user_schedule_help';
                        v.render().then(function (v) {
                            self.$('.js-help').html(v.el);
                        });
                    }

                    if (self.options.otheruser) {
                        self.$('.js-name').html(self.options.otheruser.get('displayname'));
                    }

                    // Add headings for the next five days.
                    for (var i = 0; i < 5; i++) {
                        var d = new Date();
                        d.setDate(d.getDate() + i);
                        d.setHours(0, 0, 0, 0);

                        var v = new Iznik.Views.User.Schedule.Date({
                            model: new Iznik.Model({
                                date: d
                            }),
                            slots: self.slots
                        });

                        v.render();
                        self.$('.js-headings').append(v.$el);
                    }

                    // Add the time slots.
                    var me = new Iznik.Model(Iznik.Session.get('me'));

                    for (var h = 0; h < 3; h++) {
                        self.$('.js-slots').append('<tr class="completefull"/>');

                        var v = new Iznik.Views.User.Schedule.TimeSlot({
                            model: new Iznik.Model({
                                slot: h
                            }),
                            slots: self.slots
                        });

                        v.render();
                        self.$('.js-slots tr:last').append(v.$el);

                        for (var i = 0; i < 5; i++) {
                            var d = new Date();
                            d.setDate(d.getDate() + i);
                            d.setHours(h, 0, 0, 0);

                            var available = false;

                            // Check the pre-existing schedule for information.
                            var existingslots = self.model.get('schedule');
                            _.each(existingslots, function(slot) {
                                var e = new Date(slot.date);
                                e.setHours(h, 0, 0, 0);

                                if (e.getTime() == d.getTime() && h == slot.hour) {
                                    available |= slot.available;
                                }
                            })

                            var availableother = false;

                            if (self.othermodel) {
                                var existingslotsother = self.othermodel.get('schedule');

                                _.each(existingslotsother, function(slot) {
                                    var e = new Date(slot.date);
                                    e.setHours(h, 0, 0, 0);

                                    if (e.getTime() == d.getTime() && h == slot.hour) {
                                        availableother |= slot.available;
                                    }
                                })
                            }

                            var s = new Iznik.Model({
                                id: d,
                                hour: h,
                                date: d,
                                available: available,
                                availableother: availableother,
                                mine: self.options.mine
                            });

                            self.slots.push(s);

                            var v = new Iznik.Views.User.Schedule.Slot({
                                model: s,
                                slots: self.slots
                            });

                            self.listenTo(s, 'change:available', _.bind(function() {
                                self.hasChanged = true;
                            }, self));

                            v.render();
                            self.$('.js-slots tr:last').append(v.$el);
                        }
                    }
                });

            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.Modal = Iznik.Views.Modal.extend({
        template: 'user_schedule_modal',

        render: function () {
            var self = this;

            self.wait = new Iznik.Views.PleaseWait();
            self.wait.render();

            // The schedule involves a server fetch; render it before opening the modal to avoid ugly empty
            // modal.
            var v = new Iznik.Views.User.Schedule({
                chatuserid: self.options.chatuserid,
                schedule: self.options.schedule,
                mine: self.options.mine,
                help: self.options.help,
                otheruser: self.options.otheruser,
                cancel: true
            });

            self.listenToOnce(v, 'saved', function () {
                self.close();
            });

            self.listenToOnce(v, 'cancelled', function () {
                self.close();
            });

            var p = v.render();

            p.then(function() {
                self.wait.close();
                Iznik.Views.Modal.prototype.render.call(self).then(function() {
                    self.$('.js-schedule').append(v.$el);
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.Slot = Iznik.View.extend({
        tagName: 'td',

        template: 'user_schedule_slot',

        events: {
            'click': 'select'
        },

        initialize: function () {
            this.listenTo(this.model, 'change', this.render)
        },

        select: function () {
            var self = this;

            if (self.model.get('mine')) {
                this.model.set('available', this.model.get('available') ? false : true);
                this.render();
            }
        },

        render: function () {
            var self = this;
            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                self.$el.attr('width', '16.66%');
            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.Date = Iznik.View.extend({
        tagName: 'th',

        className: 'nopad text-center',

        template: 'user_schedule_date',

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                self.$el.attr('width', '16.66%');
                // self.$el.css('min-width', '50px');
                var m = new moment(self.model.get('date'));
                var str = m.format("dddd") + '<br />';
                if (m.isSame(new Date(), 'day')) {
                    str += '<span class="text-muted">(Today)</span>';
                } else {
                    str += '<span class="text-muted">(' + m.format('Do') + ')</span>';
                }

                self.$('.js-datesm').html(str);
                self.$('.js-datexs').html(['S', 'M', 'T', 'W', 'T', 'F', 'S', 'S'][m.day()]);
            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.TimeSlot = Iznik.View.extend({
        tagName: 'td',

        className: 'nopad',

        template: 'user_schedule_timeslot',

        events: {
            'click': 'select'
        },

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                self.$el.attr('width', '16.66%');
                // self.$el.css('min-width', '50px');
            });

            return (p);
        }
    });
});