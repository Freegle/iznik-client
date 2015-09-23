var groupSelectIdCounter = 0;

Iznik.Views.Group.Select = IznikView.extend({
    template: 'group_select',

    tagName: 'select',

    persist: false,

    render: function() {
        var self = this;

        if (self.options.hasOwnProperty('id') && !self.options.hasOwnProperty('selected')) {
            // We have a specified id.  We try to remember this in local storage
            try {
                self.persist = true;
                self.options.selected = localStorage.getItem('groupselect.' + self.options.id);
            } catch (e) {}
        } else

        self.options = _.extend({}, {
            systemWide: false,
            all: true,
            id: "gs" + groupSelectIdCounter++
        }, this.options);

        // The library needs the element to have an id
        self.$el.prop('id', self.options.id);

        if (self.dropdown) {
            // Remove old ones.
            do {
                self.dropdown.remove(0);
            } while (self.dropdown.options.length > 0);
        }

        // Needs to be in DOM.
        _.defer(function() {
            self.dropdown = self.$el.msDropdown().data("dd");

            if (self.options.all) {
                self.dropdown.add({
                    text: 'All my groups',
                    value: -1,
                    title: 'All my groups'
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
                self.dropdown.add({
                    text: group.get('namedisplay'),
                    value: group.get('id'),
                    title: group.get('namedisplay'),
                    image: group.get('grouplogo')
                });
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
                        console.log("Save value", self.dropdown);
                        self.options.selected = localStorage.setItem('groupselect.' + self.options.id,
                            self.dropdown.value);
                    } catch (e) {
                    }
                }

                self.trigger('selected', self.dropdown.value);
                self.trigger('change');
            })

            self.trigger('selected', self.dropdown.value);
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