define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'jquery.dotdotdot',
    'combodate',
    'jquery.validate.min',
    'jquery.validate.additional-methods',
    'iznik/models/volunteering',
    'iznik/views/group/select',
    'iznik/customvalidate'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.VolunteeringSidebar = Iznik.View.extend({
        template: "volunteering_list",

        events: {
            'click .js-addvolunteering': 'add'
        },

        add: function() {
            var self = this;

            // Need to be logged in to add a volunteering vacancy.
            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                var v = new Iznik.Views.User.Volunteering.Editable({
                    model: new Iznik.Models.Volunteering({})
                });
                v.render();
            });

            Iznik.Session.forceLogin();
        },

        containerHeight: function() {
            $('#js-volunteeringcontainer').css('height', window.innerHeight - $('#botleft').height() - $('nav').height() - 50)
        },

        fetched: false,

        volunteeringFetched: function() {
            // This might get called twice, once with cached info and once without, so we want to be resilient to that.
            var self = this;

            if (!self.fetched) {
                self.fetched = true;
                self.$('.js-volunteeringlist').fadeIn('slow');

                self.containerHeight();
                $(window).resize(self.containerHeight);
                $('#js-volunteeringcontainer').fadeIn('slow');
            }

            if (self.volunteering.length == 0) {
                self.$('.js-none').fadeIn('slow');
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.volunteering = new Iznik.Collections.Volunteering();

                self.volunteeringView = new Backbone.CollectionView({
                    el: self.$('.js-volunteeringlist'),
                    modelView: Iznik.Views.User.Volunteering,
                    collection: self.volunteering,
                    processKeyEvents: false
                });

                self.volunteeringView.render();

                var cb = _.bind(self.volunteeringFetched, self);
                self.volunteering.fetch({
                    cached: cb,
                    data: {
                        groupid: self.options.groupid
                    }
                }).then(cb);
            });

            return(p);
        }
    });

    Iznik.Views.User.Volunteering  = Iznik.View.extend({
        template: "volunteering_one",
        className: 'padleftsm',

        events: {
            'click .js-info': 'info'
        },

        info: function() {
            var v = new Iznik.Views.User.Volunteering.Details({
                model: this.model
            });
            v.render();
        },

        render: function() {
            var self = this;
            var p = Iznik.View.prototype.render.call(this).then(function() {
                self.$el.closest('li').addClass('completefull');

                self.$('.js-description').dotdotdot({
                    height: 60
                });

                self.model.on('change', self.render, self);
            });

            return(p);
        }
    });

    Iznik.Views.User.Volunteering.Details  = Iznik.Views.Modal.extend({
        template: "volunteering_details",

        events: {
            'click .js-delete': 'deleteMe',
            'click .js-edit': 'edit'
        },

        edit: function() {
            var self = this;
            self.close();
            var v = new Iznik.Views.User.Volunteering.Editable({
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
                // Add the link to this specific volunteering vacancy.
                var usersite = $('meta[name=iznikusersite]').attr("content");
                var url = 'https://' + usersite + '/volunteering/' + self.model.get('id');
                self.$('.js-url').html(url);
                self.$('.js-url').attr('href', url);

                var dates = self.model.get('dates');
                if (dates.length > 0) {
                    self.$('.js-dates').empty();
                    _.each(dates, function(date) {
                        var startm = new moment(date.start);
                        var start = startm.format('ddd, Do MMM YYYY HH:mm');
                        var endm = new moment(date.end);
                        var end = endm.isSame(startm, 'day') ? endm.format('HH:mm') : endm.format('ddd, Do MMM YYYY HH:mm');
                        self.$('.js-dates').append(start + ' - ' + end + '<br />');
                    });
                } else {
                    self.$('.js-dateswrapper').hide();
                }
            });
        }
    });

    Iznik.Views.User.Volunteering.Editable = Iznik.Views.Modal.extend({
        template: "volunteering_edit",

        events: {
            'click .js-save': 'save',
            'click .js-adddate': 'addDate'
        },

        closeAfterSave: true,

        wait: null,

        addDate: function(event) {
            var self = this;
            event.preventDefault();
            event.stopPropagation();

            self.dates.add(new Iznik.Model({}));
        },

        save: function() {
            var self = this;

            if (!self.wait && self.dates.length > 0) {
                self.promises = [];

                if (self.$('form').valid()) {
                    self.wait = new Iznik.Views.PleaseWait({
                        timeout: 1
                    });
                    self.wait.render();

                    self.$('input,textarea').each(function () {
                        var name = $(this).prop('name');
                        if (name.length > 0 && name != 'photo') {
                            self.model.set(name, $(this).val());
                        }
                    });

                    var p = self.model.save({}, {
                        success: function(model, response, options) {
                            if (response.id) {
                                self.model.set('id', response.id);
                            }
                        }
                    }).then(function() {
                        // Add the group and dates.
                        var groups = self.model.get('groups');
                        if (_.isUndefined(groups) || (groups.length > 0 && self.groupSelect.get() != groups[0]['id'])) {
                            self.promises.push($.ajax({
                                url: API + 'volunteering',
                                type: 'PATCH',
                                data: {
                                    id: self.model.get('id'),
                                    action: 'AddGroup',
                                    groupid: self.groupSelect.get()
                                },
                                success: function (ret) {
                                    if (!_.isUndefined(groups) && groups.length > 0) {
                                        self.promises.push($.ajax({
                                            url: API + 'volunteering',
                                            type: 'PATCH',
                                            data: {
                                                id: self.model.get('id'),
                                                action: 'RemoveGroup',
                                                groupid: groups[0]['id']
                                            }
                                        }));
                                    }
                                }
                            }));
                        }

                        // Delete any old dates.
                        var olddates = self.model.get('dates');
                        _.each(olddates, function(adate) {
                            self.promises.push($.ajax({
                                url: API + 'volunteering',
                                type: 'PATCH',
                                data: {
                                    id: self.model.get('id'),
                                    action: 'RemoveDate',
                                    dateid: adate.id
                                }
                            }));
                        });

                        // Add new dates.
                        self.datesCV.viewManager.each(function(date) {
                            var start = date.getStart();
                            var end = date.getEnd();
                            var applyby = date.getApplyBy();

                            // Remove invalid date values.
                            start = (start !== 'Invalid date') ? start : null;
                            end = (end !== 'Invalid date') ? end : null;
                            applyby = (applyby !== 'Invalid date') ? applyby : null;

                            // Because we just asked for the date, if we're in DST then we'll have been given the
                            // previous day.  Add a couple of hours to make sure.
                            if (start) {
                                start = (new moment(start)).add(2, 'hours').toISOString();
                            }
                            if (end) {
                                end = (new moment(end)).add(2, 'hours').toISOString();
                            }
                            if (applyby) {
                                applyby = (new moment(applyby)).add(2, 'hours').toISOString();
                            }

                            if (start || applyby) {
                                self.promises.push($.ajax({
                                    url: API + 'volunteering',
                                    type: 'PATCH',
                                    data: {
                                        id: self.model.get('id'),
                                        action: 'AddDate',
                                        start: start,
                                        end: end,
                                        applyby: applyby
                                    }
                                }));
                            }
                        });

                        Promise.all(self.promises).then(function() {
                            self.wait.close();
                            self.wait = null;

                            if (self.closeAfterSave) {
                                self.close();
                                (new Iznik.Views.User.Volunteering.Confirm()).render();
                            }
                        });
                    });
                }
            }
        },

        parentClass: Iznik.Views.Modal,

        groupChange: function() {
            var self = this;
            var groupid = self.groupSelect.get();

            if (groupid > 0) {
                var group = Iznik.Session.getGroup(groupid);
                if (group.get('settings').volunteering) {
                    this.$('.js-volunteeringdisabled').hide();
                    this.$('.js-save').show();
                } else {
                    this.$('.js-volunteeringdisabled').fadeIn('slow');
                    this.$('.js-save').hide();
                }
            } else {
                this.$('.js-save').show();
            }
        },

        render: function() {
            var self = this;

            require([ 'fileinput' ], function() {
                self.parentClass.prototype.render.call(self).then(function() {
                    self.groupSelect = new Iznik.Views.Group.Select({
                        systemWide: Iznik.Session.hasPermission('NationalVolunteers'),
                        all: false,
                        mod: false,
                        choose: false,
                        id: 'volunteeringGroupSelect-' + self.model.get('id')
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
                    _.each(['title', 'description', 'timecommitment', 'location', 'contactname', 'contactemail', 'contacturl', 'contactphone'], function(att)
                    {
                        self.$('.js-' + att).val(self.model.get(att));
                    })

                    var dates = self.model.get('dates');

                    if (_.isUndefined(dates) || dates.length == 0) {
                        // None so far.  Set up one for them to modify.
                        self.dates = new Iznik.Collection([
                            new Iznik.Model({})
                        ]);
                    } else {
                        self.dates = new Iznik.Collection(dates);
                    }

                    self.datesCV = new Backbone.CollectionView({
                        el: self.$('.js-dates'),
                        modelView: Iznik.Views.User.Volunteering.Date,
                        collection: self.dates,
                        processKeyEvents: false,
                        modelViewOptions: {
                            collection: self.dates
                        }
                    });

                    self.datesCV.render();

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
                                    required: false
                                },
                                end: {
                                    mindate: self,
                                    required: false
                                },
                                end: {
                                    mindate: self,
                                    required: false
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
            });
        }
    });

    Iznik.Views.User.Volunteering.Date = Iznik.View.extend({
        template: "volunteering_dates",

        events: {
            'change .js-start': 'startChange',
            'click .js-deldate': 'del'
        },

        startChange: function() {
            // Set end date after start date.
            this.$('.js-end').combodate('setValue', this.$('.js-start').combodate('getValue'));
        },

        del: function(event) {
            var self = this;
            event.preventDefault();
            event.stopPropagation();

            self.$el.fadeOut('slow', function() {
                self.options.collection.remove(self.model);
            });
        },

        render: function () {
            var self = this;
            var p = Iznik.View.prototype.render.call(this).then(function() {
                self.$el.html(window.template(self.template)(self.model.toJSON2()));

                var start = self.model.get('start');
                var end = self.model.get('end');
                var applyby = self.model.get('applyby');

                var dtopts = {
                    format: "dd MM yyyy",
                    showMeridian: true,
                    autoclose: true
                };

                if (!start) {
                    dtopts.startDate = (new Date()).toISOString().slice(0, 10);
                }

                var opts = {
                    template: "DD MMM YYYY",
                    format: "YYYY-MM-DD",
                    smartDays: true,
                    yearDescending: false,
                    minYear: new Date().getFullYear(),
                    maxYear: new Date().getFullYear() + 1,
                    customClass: 'inline'
                };

                self.$('.js-start, .js-end, .js-applyby').combodate(opts);

                if (start) {
                    self.$('.js-start').combodate('setValue', new Date(start));
                }

                if (end) {
                    self.$('.js-end').combodate('setValue', new Date(end));
                }

                if (applyby) {
                    self.$('.js-applyby').combodate('setValue', new Date(applyby));
                }

                self.$('select').addClass('form-control');

                if (self.options.collection.length > 1) {
                    self.$('.js-deldate').show();
                }
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
        },

        getApplyBy: function() {
            return(this.getDate('.js-applyby'));
        }
    });

    Iznik.Views.User.Volunteering.Confirm = Iznik.Views.Modal.extend({
        template: "volunteering_confirm"
    });
});