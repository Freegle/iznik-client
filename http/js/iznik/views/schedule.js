define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/models/schedule',
    'jquery.scrollTo'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.Schedule = {};

    Iznik.Views.Schedule.Modal = Iznik.Views.Modal.extend({
        template: 'schedule_modal',

        slots: [],

        events: {
            'click .js-confirm': 'save'
        },

        save: function() {
            var self = this;

            var slots = [];

            _.each(self.slots, function(slot) {
                slots.push({
                    hour: slot.get('hour'),
                    date: slot.get('date'),
                    available: [
                        {
                            user: Iznik.Session.get('me').id,
                            available: slot.get('availableme')
                        }
                    ]
                });
            })

            var m = new Iznik.Models.Schedule({
                userid: self.model.get('id'),
                schedule: slots
            });

            m.save().then(function() {
                self.close();
            })
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.Modal.prototype.render.call(this);

            p.then(function() {
                self.$('.js-name').html(self.model.get('displayname'));

                // Add headings for the next seven days.
                for (var i = 0; i < 7; i++) {
                    var d = new Date();
                    d.setDate(d.getDate() + i);
                    d.setHours(0,0,0,0);

                    var v = new Iznik.Views.Schedule.Date({
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

                for (var h = 0; h < 24; h++) {
                    self.$('.js-slots').append(h == 8 ? '<tr class="js-morning" />' : '<tr />');

                    var v = new Iznik.Views.Schedule.Hour({
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
                        d.setHours(0,0,0,0);

                        var s = new Iznik.Model({
                            hour: h,
                            date: d,
                            me: me,
                            other: self.model
                        });

                        self.slots.push(s);

                        var v = new Iznik.Views.Schedule.Slot({
                            model: s
                        });

                        v.render();
                        self.$('.js-slots tr:last').append(v.$el);
                    }

                    _.delay(function() {
                        self.$('.js-slots').scrollTo(self.$('.js-morning'), 'slow');
                    }, 1000);
                }
            });

            return(p);
        }
    });

    Iznik.Views.Schedule.Slot = Iznik.View.extend({
        tagName: 'td',

        template: 'schedule_slot',

        className: 'nopad',

        events: {
            'click': 'select'
        },

        initialize: function() {
            this.listenTo(this.model, 'change', this.render)
        },

        select: function() {
            var myid = Iznik.Session.get('me').id;
            this.model.set('availableme', this.model.get('availableme') ? false: true);

            this.render();
        },

        render: function() {
            var self = this;
            var p = Iznik.View.prototype.render.call(this);

            p.then(function() {
                self.$el.attr('width', '12.5%');
                // self.$el.css('min-width', '50px');
                if (self.model.get('selected')) {
                    self.$el.addClass('success');
                } else {
                    self.$el.removeClass('success');
                }
            });

            return(p);
        }
    });


    Iznik.Views.Schedule.Date = Iznik.View.extend({
        tagName: 'th',

        className: 'nopad text-center',

        template: 'schedule_date',

        events: {
            'click': 'select'
        },

        select: function() {
            var self = this;
            var currval = 0;

            // Take majority opinion about whether we're selected.
            _.each(self.options.slots, function(slot) {
                if (slot.get('date').getTime() == self.model.get('date').getTime()) {
                    if (slot.get('availableme')) {
                        currval++;
                    } else {
                        currval--;
                    }
                }
            });

            _.each(self.options.slots, function(slot) {
                if (slot.get('date').getTime() == self.model.get('date').getTime()) {
                    slot.set('availableme', currval <= 0);
                }
            });
        },

        render: function() {
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

    Iznik.Views.Schedule.Hour = Iznik.View.extend({
        tagName: 'td',

        className: 'nopad',

        template: 'schedule_hour',

        events: {
            'click': 'select'
        },

        select: function() {
            var self = this;

            var currval = 0;

            // Take majority opinion about whether we're selected.
            _.each(self.options.slots, function(slot) {
                if (slot.get('hour') == self.model.get('hour')) {
                    if (slot.get('availableme')) {
                        currval++;
                    } else {
                        currval--;
                    }
                }
            });

            _.each(self.options.slots, function(slot) {
                if (slot.get('hour') == self.model.get('hour')) {
                    slot.set('availableme', currval <= 0);
                }
            });
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                self.$el.attr('width', '12.5%');
                // self.$el.css('min-width', '50px');
            });

            return (p);
        }
    });
});