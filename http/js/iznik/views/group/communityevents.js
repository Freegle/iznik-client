define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'combodate',
    'jquery.validate.min',
    'jquery.validate.additional-methods',
    'iznik/models/communityevent',
    'iznik/views/group/select'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.CommunityEventsSidebar = Iznik.View.extend({
        template: "communityevents_list",

        events: {
            'click .js-addevent': 'add'
        },

        add: function() {
            var self = this;

            // Need to be logged in to add an event.
            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                var v = new Iznik.Views.User.CommunityEvent.Editable({
                    model: new Iznik.Models.CommunityEvent({})
                });
                v.render();
            });

            Iznik.Session.forceLogin();
        },

        containerHeight: function() {
            $('#js-eventcontainer').css('height', window.innerHeight - $('#botleft').height() - $('nav').height() - 50)
        },

        fetched: false,

        eventsFetched: function() {
            // This might get called twice, once with cached info and once without, so we want to be resilient to that.
            var self = this;

            if (!self.fetched) {
                self.fetched = true;
                self.$('.js-list').fadeIn('slow');

                self.containerHeight();
                $(window).resize(self.containerHeight);
                $('#js-eventcontainer').fadeIn('slow');
            }

            if (self.events.length == 0) {
                self.$('.js-none').fadeIn('slow');
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.events = new Iznik.Collections.CommunityEvent();

                self.eventsView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.User.CommunityEvent,
                    collection: self.events
                });

                self.eventsView.render();

                var cb = _.bind(self.eventsFetched, self);
                self.events.fetch({
                    cached: cb
                }).then(cb);
            });

            return(p);
        }
    });

    Iznik.Views.User.CommunityEvent  = Iznik.View.extend({
        template: "communityevents_one",
        className: 'padleftsm',

        events: {
            'click .js-info': 'info'
        },

        info: function() {
            var v = new Iznik.Views.User.CommunityEvent.Details({
                model: this.model
            });
            v.render();
        },

        render: function() {
            var self = this;
            var p = Iznik.View.prototype.render.call(this).then(function() {
                var mom = new moment(self.model.get('dates')[0]['start']);
                self.$('.js-start').html(mom.format('ddd, Do MMM HH:mm'));
                var mom = new moment(self.model.get('dates')[0]['end']);
                self.$('.js-end').html(mom.format('ddd, Do MMM HH:mm'));
                self.$el.closest('li').addClass('completefull');

                self.model.on('change', self.render, self);
            });

            return(p);
        }
    });

    Iznik.Views.User.CommunityEvent.Details  = Iznik.Views.Modal.extend({
        template: "communityevents_details",

        events: {
            'click .js-delete': 'deleteMe',
            'click .js-edit': 'edit'
        },

        edit: function() {
            var self = this;
            self.close();
            var v = new Iznik.Views.User.CommunityEvent.Editable({
                model: self.model
            });
            v.render();
        },

        deleteMe: function() {
            var self = this;
            this.model.destroy({
                success: function() {
                    self.close();
                }
            });
        },

        render: function() {
            var self = this;
            Iznik.Views.Modal.prototype.render.call(this).then(function() {
                // Add the link to this specific event.
                var usersite = $('meta[name=iznikusersite]').attr("content");
                var url = 'https://' + usersite + '/communityevent/' + self.model.get('id');
                self.$('.js-url').html(url);
                self.$('.js-url').attr('href', url);

                self.$('.js-dates').empty();
                _.each(self.model.get('dates'), function(date) {
                    var start = (new moment(date.start)).format('ddd, Do MMM YYYY HH:mm');
                    var end = (new moment(date.end)).format('ddd, Do MMM YYYY HH:mm');
                    self.$('.js-dates').append(start + ' - ' + end + '<br />');
                });
            });
        }
    });

    Iznik.Views.User.CommunityEvent.Editable  = Iznik.Views.Modal.extend({
        template: "communityevents_edit",

        events: {
            'click .js-save': 'save'
        },
        
        closeAfterSave: true,

        save: function() {
            var self = this;

            if (this.$('form').valid()) {
                self.$('input,textarea').each(function () {
                    var name = $(this).prop('name');
                    if (name.length > 0) {
                        self.model.set(name, $(this).val());
                    }
                });

                self.model.save({}, {
                    success: function(model, response, options) {
                        if (response.id) {
                            self.model.set('id', response.id);
                        }
                    }
                }).then(function() {
                    // Add the group and dates.
                    var groups = self.model.get('groups');
                    if (_.isUndefined(groups) || self.groupSelect.get() != groups[0]['id']) {
                        $.ajax({
                            url: API + 'communityevent',
                            type: 'PATCH',
                            data: {
                                id: self.model.get('id'),
                                action: 'AddGroup',
                                groupid: self.groupSelect.get()
                            },
                            success: function (ret) {
                                if (!_.isUndefined(groups)) {
                                    $.ajax({
                                        url: API + 'communityevent',
                                        type: 'PATCH',
                                        data: {
                                            id: self.model.get('id'),
                                            action: 'RemoveGroup',
                                            groupid: groups[0]['id']
                                        }
                                    });
                                }
                            }
                        });
                    }

                    // Delete any old dates.
                    var olddates = self.model.get('dates');
                    _.each(olddates, function(adate) {
                        console.log("Remove date", adate);
                        $.ajax({
                            url: API + 'communityevent',
                            type: 'PATCH',
                            data: {
                                id: self.model.get('id'),
                                action: 'RemoveDate',
                                dateid: adate.id
                            }
                        });
                    });

                    // Add new dates.
                    for (var i = 0; i < self.dates.length; i++) {
                        var start = self.dates[i].getStart();
                        var end = self.dates[i].getEnd();

                        $.ajax({
                            url: API + 'communityevent',
                            type: 'PATCH',
                            data: {
                                id: self.model.get('id'),
                                action: 'AddDate',
                                start: start,
                                end: end
                            }
                        });
                    }
                });

                if (self.closeAfterSave) {
                    self.close();
                    (new Iznik.Views.User.CommunityEvent.Confirm()).render();
                }
            }
        },
        
        parentClass: Iznik.Views.Modal,

        groupChange: function() {
            var self = this;
            var groupid = self.groupSelect.get();
            var group = Iznik.Session.getGroup(groupid);
            if (group.get('settings').communityevents) {
                this.$('.js-eventsdisabled').hide();
                this.$('.js-save').show();
            } else {
                this.$('.js-eventsdisabled').fadeIn('slow');
                this.$('.js-save').hide();
            }
        },

        render: function() {
            var self = this;
            this.parentClass.prototype.render.call(this).then(function() {
                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: false,
                    mod: false,
                    choose: false,
                    id: 'eventGroupSelect-' + self.model.get('id')
                });

                // The group select render is a bit unusual because the dropdown requires us to have added it to the
                // DOM, so there's an event when we've really finished, at which point we can set a value.
                self.listenToOnce(self.groupSelect, 'completed', function() {
                    var groups = self.model.get('groups');
                    if (groups && groups.length) {
                        // Only one group supported in the client at the moment.
                        // TODO
                        self.groupSelect.set(groups[0].id);
                    }
                    self.groupChange();
                });

                self.groupSelect.render().then(function () {
                    self.$('.js-groupselect').html(self.groupSelect.el);
                    self.listenTo(self.groupSelect, 'change', _.bind(self.groupChange, self));
                });

                // Set the values.  We do it here rather than in the template because they might contain user data
                // which would mess up the template expansion.
                _.each(['title', 'description', 'location', 'contactname', 'contactemail', 'contacturl', 'contactphone'], function(att)
                {
                    self.$('.js-' + att).val(self.model.get(att));
                })

                var dates = self.model.get('dates');
                self.dates = [];

                if (_.isUndefined(dates) || dates.length == 0) {
                    // None so far.  Set up one for them to modify.
                    var v = new Iznik.Views.User.CommunityEvent.Dates({
                        list: self.dates,
                        model: self.model
                    });
                    v.render().then(function() {
                        self.$('.js-dates').append(v.el);
                    });
                } else {
                    // Got some dates.  Show them.
                    _.each(dates, function(adate) {
                        var v = new Iznik.Views.User.CommunityEvent.Dates({
                            list: self.dates,
                            model: new Iznik.Model(adate)
                        });
                        v.render().then(function() {
                            self.$('.js-dates').append(v.el);
                        });
                    });
                }

                // Need to make sure we're in the DOM else the validate plugin fails.
                self.waitDOM(self, function() {
                    self.validator = self.$('form').validate({
                        rules: {
                            title: {
                                required: true
                            },
                            description: {
                                required: true
                            },
                            start: {
                                mindate: self,
                                required: true
                            },
                            end: {
                                mindate: self,
                                required: true
                            },
                            location: {
                                required: true
                            },
                            contactphone: {
                                phoneUK: true
                            },
                            contactemail: {
                                email: true
                            },
                            contacturl: {
                                url: true
                            }
                        }
                    });
                });
            });
        }
    });

    Iznik.Views.User.CommunityEvent.Dates = Iznik.View.extend({
        template: "communityevents_dates",

        events: {
            'click .js-addrepeat': 'add',
            'change .js-start': 'startChange'
        },

        startChange: function() {
            // Set end date after start date.
            this.$('.js-end').combodate('setValue', this.$('.js-start').combodate('getValue'));
        },

        add: function(event) {
            event.preventDefault();
            event.stopPropagation();
            var self = this;
            this.$('.js-addrepeat').hide();
            var v = new Iznik.Views.User.CommunityEvent.Dates(this.options);
            v.render().then(function() {
                console.log("New date", self.$el.parent(), v.el);
                self.$el.parent().append(v.el);
            });
        },

        render: function () {
            var self = this;
            var p = Iznik.View.prototype.render.call(this).then(function() {
                self.options.list.push(self);
                self.$el.html(window.template(self.template)(self.model.toJSON2()));

                var start = self.model.get('start');
                var end = self.model.get('end');

                var dtopts = {
                    format: "dd MM yyyy HH:ii P",
                    showMeridian: true,
                    autoclose: true
                };

                if (!start) {
                    dtopts.startDate = (new Date()).toISOString().slice(0, 10);
                }

                var opts = {
                    template: "DD MMM YYYY hh:mm A",
                    format: "YYYY-MM-DD HH:mm",
                    smartDays: true,
                    yearDescending: false,
                    minYear: new Date().getFullYear(),
                    maxYear: new Date().getFullYear() + 1
                };

                self.$('.js-start, .js-end').combodate(opts);

                if (start) {
                    self.$('.js-start').combodate('setValue', new Date(start));
                } else {
                    // Set a default of tomorrow on the hour
                    var m = moment().add(1, 'days').startOf('hour');
                    self.$('.js-start').combodate('setValue', m.toDate());
                }

                if (end) {
                    self.$('.js-end').combodate('setValue', new Date(end));
                } else {
                    // Default event to last 1 hour.
                    var m = moment().add(1, 'days').startOf('hour').add(1, 'hours');
                    self.$('.js-end').combodate('setValue', m.toDate());
                }

                self.$('select').addClass('form-control');
            });

            return(p);
        },

        getDate: function(key) {
            try {
                var d = this.$(key).val();
                d = (new moment(d)).toISOString();
            } catch (e) {
                console.log("Date exception", e);
            }

            return(d);
        },

        getStart: function() {
            return(this.getDate('.js-start'));
        },

        getEnd: function() {
            return(this.getDate('.js-end'));
        }
    });

    Iznik.Views.User.CommunityEvent.Confirm = Iznik.Views.Modal.extend({
        template: "communityevents_confirm"
    });
});