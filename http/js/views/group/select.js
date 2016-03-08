var groupSelectIdCounter = 0;

Iznik.Views.Group.Select = IznikView.extend({
    template: 'group_select',

    tagName: 'select',

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

    render: function() {
        var self = this;
        console.log("Render select");

        // We hide the raw select now otherwise it shows briefly.  We set visibility on the dropdown once it's built.
        self.$el.css('visibility', 'hidden');

        if (self.options.hasOwnProperty('id') && !self.options.hasOwnProperty('selected')) {
            // We have a specified id.  We try to remember this in local storage
            try {
                self.persist = true;
                self.options.selected = localStorage.getItem('groupselect.' + self.options.id);
            } catch (e) {}
        }

        var id = "gs" + groupSelectIdCounter++;

        self.options = _.extend({}, {
            systemWide: false,
            all: true,
            id: id
        }, this.options);

        // The library needs the element to have an id
        self.$el.prop('id', self.options.id);

        // Needs to be in DOM.
        _.defer(function() {
            if (self.dropdown) {
                // Remove old values.
                do {
                    self.dropdown.remove(0);
                } while (self.dropdown.options.length > 0);
            } else {
                // Create dropdown from scratch.
                self.dropdown = self.$el.msDropdown().data("dd");
            }

            if (self.options.all) {
                self.dropdown.add({
                    text: 'All my groups',
                    value: -1,
                    title: 'All my groups'
                });
            }

            if (self.options.choose) {
                self.dropdown.add({
                    text: 'Please choose a group...',
                    value: -1,
                    title: 'Please choose a group...'
                });
            }

            if ((Iznik.Session.get('me').systemrole == 'Support' || Iznik.Session.get('me').systemrole == 'Admin') &&
                (self.options.systemWide)) {
                self.dropdown.add({
                    text: 'Systemwide',
                    value: -2,
                    title: 'Systemwide'
                });
            }

            Iznik.Session.get('groups').each(function(group) {
                var role = group.get('role');

                if (!self.options.mod || role == 'Owner' || role ==  'Moderator') {
                    self.dropdown.add({
                        text: self.getName(group),
                        value: group.get('id'),
                        title: group.get('namedisplay'),
                        image: group.get('grouplogo')
                    });
                }
            });

            if (self.options.hasOwnProperty('selected') && self.options.selected) {
                self.dropdown.setIndexByValue(self.options.selected);
            } else {
                self.dropdown.set('selectedIndex', 0);
            }

            self.dropdown.on('change', function() {
                if (self.persist) {
                    // We want to try to save the selected value in local storage to restore next time.
                    try {
                        self.options.selected = localStorage.setItem('groupselect.' + self.options.id,
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
        });

        return this;
    },

    get: function() {
        return(this.dropdown ? this.dropdown.value : null);
    },

    set: function(val) {
        this.dropdown.setIndexByValue(val);
    }
});