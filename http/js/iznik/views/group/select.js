define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'jquery.dd'
], function($, _, Backbone, Iznik) {
    var groupSelectIdCounter = 0;

    Iznik.Views.Group.Select = Iznik.View.extend({
        template: 'group_select',

        persist: false,
        dropdown: null,

        getName: function(group) {
            var self = this;
            var name = group.get('namedisplay');
            if (self.options.hasOwnProperty('counts')) {
                // We need to annotate the name with counts.
                var total = 0;
                var work = group.get('work');
                _.each(self.options.counts, function(count) {
                    if (work && work.hasOwnProperty(count)) {
                        total += work[count];
                    }
                });

                if (total > 0) {
                    name += ' (' + total + ')';
                }
            }

            return(name);
        },

        updateCounts: function() {
            // TODO This code is hacky - it scans the whole DOM, and surely there's either a method inside the select
            // we could use, or we should be using a different select.
            var self = this;

            if (self.$el.closest('body').length > 0) {
                // Still in DOM
                self.$('option').each(function() {
                    var group = Iznik.Session.getGroup($(this).val());
                    if (group) {
                        var name = self.getName(group);
                        var seek = group.get('namedisplay');
                        $(this).text(name);
                        $('li._msddli_').each(function() {
                            if ($(this).prop('title') == seek) {
                                $(this).find('span').html(name);
                            }
                        })
                    }
                });

                self.listenToOnce(Iznik.Session, 'countschanged', self.updateCounts);
            }
        },

        inDOM: function() {
            var self = this;

            if (self.id) {
                // We have a specified id.  We try to remember this in local storage
                try {
                    self.persist = true;

                    if (!self.options.selected) {
                        // We haven't been passed a value to select - use what we last had.
                        self.options.selected = Storage.get('groupselect.' + self.id);
                    }
                } catch (e) {}
            } else {
                self.id = id = "gs" + groupSelectIdCounter++;
            }

            self.options = _.extend({}, {
                systemWide: false,
                all: true,
                id: self.id
            }, this.options);

            // The library needs the element to have an id
            self.$el.prop('id', self.id);

            if (self.dropdown) {
                // Remove old values.
                self.dropdown.destroy();
            }

            var json = [];

            if (self.options.all) {
                json.push({
                    text: 'All my groups',
                    value: -1,
                    title: 'All my groups'
                });
            }

            if (self.options.choose) {
                json.push({
                    text: 'Please choose a group...',
                    value: -1,
                    title: 'Please choose a group...'
                });
            }

            if ((Iznik.Session.get('me').systemrole == 'Support' || Iznik.Session.get('me').systemrole == 'Admin') &&
                (self.options.systemWide)) {
                json.push({
                    text: 'Systemwide',
                    value: -2,
                    title: 'Systemwide'
                });
            }

            var gotselected = false;
            Iznik.Session.get('groups').each(function(group) {
                if (group.get('id') == self.options.selected) {
                    gotselected = true;
                }

                var role = group.get('role');
                // console.log("Consider group", group, role);

                if ((!self.options.mod || role == 'Owner' || role ==  'Moderator') &&
                    (!self.options.grouptype || self.options.grouptype == group.get('type'))) {
                    json.push({
                        text: self.getName(group),
                        value: group.get('id'),
                        title: group.get('namedisplay'),
                        image: group.get('grouplogo')
                    });
                }
            });

            // Now create the dropdown.  We do this from a JSON array because otherwise the UI updates each time we
            // add one, which performs atrociously for many items.
            self.dropdown = self.$el.msDropdown({
                byJson: {
                    data: json,
                    name: 'groupselect.' + self.id
                }
            }).data("dd");

            // console.log("Consider autoselect", json, gotselected);
            if (json.length === (self.options.choose ? 2 : 1) && !gotselected) {
                // Just one group - select it by default.
                gotselected = true;
                self.options.selected = json[self.options.choose ? 1 : 0].value;
            }

            if (gotselected && self.options.hasOwnProperty('selected') && self.options.selected) {
                self.dropdown.setIndexByValue(self.options.selected);
            } else {
                self.dropdown.set('selectedIndex', 0);
            }

            self.dropdown.on('change', function() {
                if (self.persist) {
                    // We want to try to save the selected value in local storage to restore next time.
                    try {
                        self.options.selected = Storage.set('groupselect.' + self.id,
                            self.dropdown.value);
                    } catch (e) {
                    }
                }

                self.trigger('selected', self.dropdown.value);
                self.trigger('change');
            });

            self.trigger('selected', self.dropdown.value);

            // If any of our counts change, re-render the select in case it includes counts in the dropdown
            self.listenToOnce(Iznik.Session, 'countschanged', self.updateCounts);

            // We've built the dropdown so we can show it now.
            $('.dd').css('visibility', 'visible');

            self.trigger('completed');
        },

        render: function() {
            var self = this;

            self.id  = self.options.hasOwnProperty('id') ? self.options.id : null;

            if (self.id && $('#' + self.id).length > 0 && !$('#' + self.id).is('select')) {
                // Check that we've called this with the right element type, otherwise it fails in a way that's
                // a pain to debug.  Not that I've made that mistake repeatedly, you understand.
                console.error("Need to use a select element", self.id, $('#' + self.id));
            }

            // We hide the raw select now otherwise it shows briefly.  We set visibility on the dropdown once it's built.
            // self.$el.css('visibility', 'hidden');

            // The dropdown library needs it to be in the DOM.
            self.waitDOM(self, self.inDOM);

            return(resolvedPromise(self));
        },

        get: function() {
            return(this.dropdown ? this.dropdown.value : null);
        },

        set: function(val) {
            this.dropdown.setIndexByValue(val);
        }
    });
});