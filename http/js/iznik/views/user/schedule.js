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

        events: {
            'click .js-confirm': 'save',
            'click .js-cancel': 'cancel'
        },

        cancel: function () {
            this.trigger('cancelled');
        },

        save: function () {
            var self = this;

            var myid = Iznik.Session.get('me').id;
            var other = self.options.otherid;

            var slots = [];

            _.each(self.slots, function (slot) {
                var d = new moment(slot.get('date'));
                d.hour(slot.get('hour'));
                d = d.format();

                slots.push({
                    hour: slot.get('hour'),
                    date: d,
                    available: [
                        {
                            user: myid,
                            available: slot.get('availableme')
                        }
                    ]
                });

                if (other) {
                    slots.push({
                        hour: slot.get('hour'),
                        date: d,
                        available: [
                            {
                                user: other,
                                available: slot.get('availableother')
                            }
                        ]
                    });
                }
            });

            var m = new Iznik.Models.Schedule({
                id: self.options.id,
                userid: self.model.get('id'),
                schedule: slots,
                agreed: self.options.schedule.get('agreed')
            });

            m.save().then(function () {
                self.trigger('saved');
            })
        },

        addSuggestions: function() {
            var self = this;

            self.suggestions = new Iznik.Collection();
            self.suggestions.comparator = 'date';

            _.each(self.slots, function (slot) {
                if (slot.get('availableme') && slot.get('availableother')) {
                    self.suggestions.add(slot);
                }
            });

            if (self.suggestions.length) {
                self.CV = new Backbone.CollectionView({
                    el: self.$('.js-suggestions'),
                    modelView: Iznik.Views.User.Schedule.Suggestion,
                    collection: self.suggestions,
                    processKeyEvents: false,
                    modelViewOptions: {
                        schedule: self.options.schedule,
                        slots: self.slots
                    }
                });

                self.CV.render();

                self.$('.js-suggestionwrapper').fadeIn('slow');
            }
        },

        render: function () {
            var self = this;

            var me = Iznik.Session.get('me');
            var myid = me ? me.id : null;

            self.model.set('agreed', self.options.schedule.get('agreed'));

            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                if (self.options.cancel) {
                    self.$('.js-cancel').show();
                }

                var v = new Iznik.Views.Help.Box();
                v.template = 'user_schedule_help';
                v.render().then(function (v) {
                    self.$('.js-help').html(v.el);
                });

                self.$('.js-name').html(self.model.get('displayname'));

                if (self.model.get('agreed')) {
                    var m = new moment(self.model.get('agreed'));
                    self.$('.js-agreed').html(m.format("dddd Do, hh:mma"));
                }

                // Add headings for the next seven days.
                for (var i = 0; i < 7; i++) {
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

                _.delay(function () {
                    // Add the time slots.
                    var me = new Iznik.Model(Iznik.Session.get('me'));

                    for (var h = 0; h < 24; h++) {
                        self.$('.js-slots').append(h == 8 ? '<tr class="js-morning completefull" />' : '<tr class="completefull"/>');

                        var v = new Iznik.Views.User.Schedule.Hour({
                            model: new Iznik.Model({
                                hour: h
                            }),
                            slots: self.slots
                        });

                        v.render();
                        self.$('.js-slots tr:last').append(v.$el);

                        for (var i = 0; i < 7; i++) {
                            var d = new Date();
                            d.setDate(d.getDate() + i);
                            d.setHours(h, 0, 0, 0);

                            var availableme = false;
                            var availableother = false;

                            if (self.options.slots) {
                                // We have been passed the slot information.  That is currently objective - we need to
                                // make it subjective, i.e. refer to us and the other.
                                _.each(self.options.slots, function (slot) {
                                    // console.log("Consider slot", slot, h, d);
                                    if (slot.hour == h && new Date(slot.date).getTime() == d.getTime()) {

                                        _.each(slot.available, function (available) {
                                            if (available.user == myid) {
                                                availableme = available.hasOwnProperty('available') ? available.available : false;
                                            } else if (available.user == self.model.get('id')) {
                                                availableother = available.hasOwnProperty('available') ? available.available : false;
                                            }
                                        });
                                    }
                                });
                            }

                            var s = new Iznik.Model({
                                id: d,
                                hour: h,
                                date: d,
                                me: me,
                                other: self.model,
                                availableme: availableme,
                                availableother: availableother
                            });

                            self.slots.push(s);

                            var v = new Iznik.Views.User.Schedule.Slot({
                                model: s,
                                other: self.options.other
                            });

                            v.render();
                            self.$('.js-slots tr:last').append(v.$el);
                        }

                        _.delay(function () {
                            self.$('.js-slots').scrollTo(self.$('.js-morning'), 'slow');
                        }, 1000);

                        self.addSuggestions();
                        self.listenTo(self.slots, 'change', self.addSuggestions);
                        self.listenTo(self.options.schedule, 'change', self.addSuggestions);
                    }
                }, 100);
            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.Modal = Iznik.Views.Modal.extend({
        template: 'user_schedule_modal',

        render: function () {
            var self = this;

            var p = Iznik.Views.Modal.prototype.render.call(this);

            p.then(function () {
                var v = new Iznik.Views.User.Schedule({
                    model: self.model,
                    id: self.options.id,
                    other: self.options.other,
                    otherid: self.options.otherid,
                    slots: self.options.slots,
                    schedule: self.options.schedule,
                    cancel: true
                });

                self.listenToOnce(v, 'saved', function () {
                    self.close();
                });

                self.listenToOnce(v, 'cancelled', function () {
                    self.close();
                });

                v.render();
                self.$('.js-schedule').append(v.$el);
            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.Slot = Iznik.View.extend({
        tagName: 'td',

        template: 'user_schedule_slot',

        className: 'nopad',

        events: {
            'click': 'select'
        },

        initialize: function () {
            this.listenTo(this.model, 'change', this.render)
        },

        select: function () {
            var self = this;

            var myid = Iznik.Session.get('me').id;
            this.model.set('availableme', this.model.get('availableme') ? false : true);

            this.render();

            self.options.slots.trigger('change');
        },

        render: function () {
            var self = this;
            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                if (self.options.other) {
                    self.$('.js-other').show();
                }

                self.$el.attr('width', '12.5%');
                // self.$el.css('min-width', '50px');
                if (self.model.get('selected')) {
                    self.$el.addClass('success');
                } else {
                    self.$el.removeClass('success');
                }
            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.Date = Iznik.View.extend({
        tagName: 'th',

        className: 'nopad text-center',

        template: 'user_schedule_date',

        events: {
            'click': 'select'
        },

        select: function () {
            var self = this;
            var currval = 0;

            // Take majority opinion about whether we're selected.
            _.each(self.options.slots, function (slot) {
                if (slot.get('date').getTime() == self.model.get('date').getTime()) {
                    if (slot.get('availableme')) {
                        currval++;
                    } else {
                        currval--;
                    }
                }
            });

            _.each(self.options.slots, function (slot) {
                if (slot.get('date').getTime() == self.model.get('date').getTime()) {
                    slot.set('availableme', currval <= 0);
                }
            });

            self.options.slots.trigger('change');
        },

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                self.$el.attr('width', '12.5%');
                // self.$el.css('min-width', '50px');
                var m = new moment(self.model.get('date'));
                self.$('.js-datesm').html(m.format("ddd<br />Do"));
                self.$('.js-datexs').html(['S', 'M', 'T', 'W', 'T', 'F', 'S', 'S'][m.day()]);
            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.Hour = Iznik.View.extend({
        tagName: 'td',

        className: 'nopad',

        template: 'user_schedule_hour',

        events: {
            'click': 'select'
        },

        select: function () {
            var self = this;

            var currval = 0;

            // Take majority opinion about whether we're selected.
            _.each(self.options.slots, function (slot) {
                if (slot.get('hour') == self.model.get('hour')) {
                    if (slot.get('availableme')) {
                        currval++;
                    } else {
                        currval--;
                    }
                }
            });

            _.each(self.options.slots, function (slot) {
                if (slot.get('hour') == self.model.get('hour')) {
                    slot.set('availableme', currval <= 0);
                }
            });

            self.options.slots.trigger('change');
        },

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                self.$el.attr('width', '12.5%');
                // self.$el.css('min-width', '50px');
            });

            return (p);
        }
    });

    Iznik.Views.User.Schedule.Suggestion = Iznik.View.extend({
        tagName: 'li',

        template: 'user_schedule_suggestion',

        events: {
            'click .js-choose': 'choose'
        },

        choose: function() {
            var self = this;
            self.options.schedule.set('agreed', self.model.get('date'));
            self.$('.js-choose').hide();
            self.$('.js-chosen').fadeIn('slow');
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);

            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-date').html(m.format("dddd Do, hh:mma"));

                if ((new Date(self.model.get('date'))).getTime() == (new Date(self.options.schedule.get('agreed'))).getTime()) {
                    self.$('.js-choose').hide();
                    self.$('.js-chosen').show();
                } else {
                    self.$('.js-chosen').hide();
                    self.$('.js-choose').show();
                }
            });

            return(p);
        }
    });
});