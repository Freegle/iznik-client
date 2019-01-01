import 'bootstrap-fileinput/js/plugins/piexif.min.js';
import 'bootstrap-fileinput';

var tpl = require('iznik/templateloader');
var template = tpl.template;

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
    'iznik/views/group/select',
    'iznik/views/supportus',
    'iznik/customvalidate'
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

            Iznik.Session.forceLogin([
                'me',
                'groups'
            ]);
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
                self.$('.js-eventslist').fadeIn('slow');

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
                    el: self.$('.js-eventslist'),
                    modelView: Iznik.Views.User.CommunityEvent,
                    collection: self.events,
                    processKeyEvents: false
                });

                self.eventsView.render();

                var cb = _.bind(self.eventsFetched, self);
                self.events.fetch({
                    data: {
                        groupid: self.options.groupid
                    }
                }).then(cb);
            });

            return(p);
        }
    });

    Iznik.Views.User.CommunityEvent  = Iznik.View.extend({
        tagName: 'li',

        template: "communityevents_one",

        className: 'padleftsm',

        events: {
            'click .js-info': 'info'
        },

        info: function() {
            var self = this;

            var v = new Iznik.Views.User.CommunityEvent.Details({
                model: this.model
            });

            v.render();
        },

        rerender: function() {
            var self = this;

            self.model.fetch().then(_.bind(self.render, self));
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this).then(function() {
                var dates = self.model.get('dates');
                var count = 0;

                var url = 'https://' + USER_SITE + '/communityevent/' + self.model.get('id');
                self.$('.js-schemaurl').attr('content', url);

                if (dates) {
                    for (var i = 0; i < dates.length; i++) {
                        var date = dates[i];
                        if (moment().diff(date.end) < 0  || moment().isSame(date.end, 'day')) {
                            if (count == 0) {
                                var startm = new moment(date.start);
                                self.$('.js-start').html(startm.format('ddd, Do MMM HH:mm'));
                                var endm = new moment(date.end);
                                self.$('.js-end').html(endm.isSame(startm, 'day') ? endm.format('HH:mm') : endm.format('ddd, Do MMM YYYY HH:mm'));

                                if (i === 0) {
                                    // Add the schema.org info.  Only add the first (next) date.
                                    self.$('.js-schemastart').attr('content', startm.toISOString());
                                    self.$('.js-schemaend').attr('content', endm.toISOString());

                                    var pc = /((GIR 0AA)|((([A-PR-UWYZ][0-9][0-9]?)|(([A-PR-UWYZ][A-HK-Y][0-9][0-9]?)|(([A-PR-UWYZ][0-9][A-HJKSTUW])|([A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVWXY])))) [0-9][ABD-HJLNP-UW-Z]{2}))/i;
                                    var loc = self.model.get('location');
                                    var match = pc.exec(loc);

                                    if (match && match.length > 0) {
                                        var pcfound = match[1];
                                        self.$('.js-schemapostcode').attr('content', pcfound);
                                    } else {
                                        // No postcode; can't give the event an address so don't put in incomplete schema info.
                                        // TODO Could put in a location from the group?
                                        self.$('.js-schemainfo').remove();
                                    }
                                }
                            }

                            count++;
                        }
                    }
                }

                if (count > 1) {
                    self.$('.js-moredates').html('...+' + (count - 1) + ' more date' + (count == 2 ? '' : 's'));
                }

                self.$el.closest('li').addClass('completefull');

                self.model.on('edited', _.bind(self.rerender, self));
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
                var url = 'https://' + USER_SITE + '/communityevent/' + self.model.get('id');
                self.$('.js-url').html(url);
                self.$('.js-url').attr('href', url);
                self.$('.js-schemaurl').attr('content', url);

                self.$('.js-dates').empty();

                var dates = self.model.get('dates');
                for (var i = 0; i < dates.length; i++) {
                    var date = dates[i];
                    var startm = new moment(date.start);
                    var start = startm.format('ddd, Do MMM YYYY HH:mm');
                    var endm = new moment(date.end);
                    var end = endm.isSame(startm, 'day') ? endm.format('HH:mm') : endm.format('ddd, Do MMM YYYY HH:mm');
                    self.$('.js-dates').append(start + ' - ' + end + '<br />');

                    if (i === 0) {
                        // Add the schema.org info.
                        self.$('.js-schemastart').attr('content', startm.toISOString());
                        self.$('.js-schemaend').attr('content', endm.toISOString());

                        var pc = /((GIR 0AA)|((([A-PR-UWYZ][0-9][0-9]?)|(([A-PR-UWYZ][A-HK-Y][0-9][0-9]?)|(([A-PR-UWYZ][0-9][A-HJKSTUW])|([A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVWXY])))) [0-9][ABD-HJLNP-UW-Z]{2}))/i;
                        var loc = self.model.get('location');
                        var match = pc.exec(loc);

                        if (match && match.length > 0) {
                            var pcfound = match[1];
                            self.$('.js-schemapostcode').attr('content', pcfound);
                        } else {
                            // No postcode; can't give the event an address so don't put in incomplete schema info.
                            // TODO Could put in a location from the group?
                            self.$('.js-schemainfo').remove();
                        }
                    }
                }
            });
        }
    });

    Iznik.Views.User.CommunityEvent.Editable  = Iznik.Views.Modal.extend({
        template: "communityevents_edit",

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

            // Check all datas are in the future
            var datesvalid = true;
            var today = new moment();
            self.$('.js-datesinvalid').hide();
            self.datesCV.viewManager.each(function(date) {
                var start = new moment(date.getStart());
                var end = new moment(date.getEnd());

                if (start.diff(today) < 0 || end.diff(today) < 0) {
                    datesvalid = false;
                }
            });

            if (!datesvalid) {
                self.$('.js-datesinvalid').fadeIn('slow');
            } else {
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
                            if (_.isUndefined(groups) || self.groupSelect.get() != groups[0]['id']) {
                                self.promises.push($.ajax({
                                    url: API + 'communityevent',
                                    type: 'POST',
                                    headers: {
                                        'X-HTTP-Method-Override': 'PATCH'
                                    },
                                    data: {
                                        id: self.model.get('id'),
                                        action: 'AddGroup',
                                        groupid: self.groupSelect.get()
                                    },
                                    success: function (ret) {
                                        if (!_.isUndefined(groups)) {
                                            self.promises.push($.ajax({
                                                url: API + 'communityevent',
                                                type: 'POST',
                                                headers: {
                                                    'X-HTTP-Method-Override': 'PATCH'
                                                },
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
                                    url: API + 'communityevent',
                                    type: 'POST',
                                    headers: {
                                        'X-HTTP-Method-Override': 'PATCH'
                                    },
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

                                self.promises.push($.ajax({
                                    url: API + 'communityevent',
                                    type: 'POST',
                                    headers: {
                                        'X-HTTP-Method-Override': 'PATCH'
                                    },
                                    data: {
                                        id: self.model.get('id'),
                                        action: 'AddDate',
                                        start: start,
                                        end: end
                                    }
                                }));
                            });

                            if (self.model.get('photo')) {
                                self.promises.push($.ajax({
                                    url: API + 'communityevent',
                                    type: 'POST',
                                    headers: {
                                        'X-HTTP-Method-Override': 'PATCH'
                                    },
                                    data: {
                                        id: self.model.get('id'),
                                        action: 'SetPhoto',
                                        photoid: self.model.get('photo')
                                    }
                                }));
                            }

                            Promise.all(self.promises).then(function() {
                                self.wait.close();
                                self.wait = null;
                                self.trigger('saved');
                                self.model.trigger('edited');

                                if (self.closeAfterSave) {
                                    self.close();
                                    (new Iznik.Views.User.CommunityEvent.Confirm()).render();
                                }
                            });
                        });
                    }
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

            self.parentClass.prototype.render.call(self).then(function() {
                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: false,
                    mod: false,
                    choose: false,
                    grouptype: 'Freegle',
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
                    modelView: Iznik.Views.User.CommunityEvent.Date,
                    collection: self.dates,
                    processKeyEvents: false,
                    modelViewOptions: {
                        collection: self.dates
                    }
                });

                self.datesCV.render();

                self.listenTo(self.dates, 'update', function() {
                    // Re-render the first date to make sure the delete button only appears when there are
                    // multiple.
                    var first = self.datesCV.viewManager.findByIndex(0);

                    if (first) {
                        first.showHideDel();
                    }
                });

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

                    // Photo.  We ask for OCR because it is common for this to be a poster.
                    var photo = self.model.get('photo');
                    var url = !_.isUndefined(photo) ? photo.paththumb : "https://placehold.it/150x150";
                    self.$('.js-photopreview').attr('src',  url);
                    self.$('.js-photo').fileinput({
                        uploadExtraData: {
                            imgtype: 'CommunityEvent',
                            communityevent: 1,
                            ocr: true
                        },
                        showUpload: false,
                        allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                        uploadUrl: API + 'image',
                        resizeImage: true,
                        maxImageWidth: 800,
                        browseIcon: '<span class="glyphicon glyphicon-plus" />&nbsp;',
                        browseLabel: 'Upload photo',
                        browseClass: 'btn btn-primary nowrap',
                        showCaption: false,
                        showRemove: false,
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
                        },
                        elErrorContainer: '#js-uploaderror'
                    });

                    // Upload as soon as we have it.
                    self.$('.js-photo').on('fileimagesresized', function (event) {
                        // Have to defer else break fileinput validation processing.
                        _.defer(function() {
                            self.$('.file-input').hide();
                            self.$('.js-photopreview').hide();
                            self.$('.js-photo').fileinput('upload');
                        });
                    });

                    self.$('.js-photo').on('fileuploaded', function (event, data) {
                        // Once it's uploaded, hide the controls.  This means we can't edit, but that's ok for
                        // this.
                        self.$('.js-photopreview').attr('src', data.response.paththumb);
                        self.$('.js-photopreview').show();
                        self.model.set('photo', data.response.id);

                        if (data.response.ocr && data.response.ocr.length > 10) {
                            // We got some text.  The first line is most likely to be a title.
                            var p = data.response.ocr.indexOf("\n");
                            var title = p !== -1 ? data.response.ocr.substring(0, p): null;
                            var desc = p !== -1 ? data.response.ocr.substring(p + 1) : data.response.ocr;

                            if (title && self.$('.js-title').val().length === 0) {
                                self.$('.js-title').val(title);
                            }

                            // Put the rest in the description for them to sort out.
                            if (self.$('.js-description').val().length === 0) {
                                self.$('.js-description').val(desc);
                            }
                        }

                        _.delay(function() {
                            self.$('.file-preview-frame').remove();
                        }, 500);
                    });
                });
            });
        }
    });

    Iznik.Views.User.CommunityEvent.Date = Iznik.View.extend({
        tagName: 'li',

        template: "communityevents_dates",

        events: {
            'change .js-start': 'startChange',
            'change .js-end': 'endChange',
            'click .js-deldate': 'del'
        },

        startChange: function() {
            // Set end date after start date.
            this.$('.js-end').combodate('setValue', this.$('.js-start').combodate('getValue'));
        },

        endChange: function() {
            var start = this.$('.js-start').combodate('getValue');
            var end = this.$('.js-end').combodate('getValue');

            var ms = new moment(start);
            var me = new moment(end);

            if (me.isBefore(ms)) {
                // Set end date after start date.
                this.$('.js-end').combodate('setValue', this.$('.js-start').combodate('getValue'));
            }
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
                self.$el.html(template(self.template)(self.model.toJSON2()));

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
                    maxYear: new Date().getFullYear() + 1,
                    customClass: 'inline'
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
                self.showHideDel();
            });

            return(p);
        },

        showHideDel: function() {
            var self = this;

            if (self.options.collection.length > 1) {
                self.$('.js-deldate').show();
            }
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