define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'typeahead',
    'iznik/models/postaladdress'
], function($, _, Backbone, Iznik) {
    Iznik.Views.PostalAddress = Iznik.View.extend({
        template: 'postaladdress_ask',

        location: null,

        events: {
            'typeahead:change .js-postcode': 'pcChange',
            'click .js-delete': 'delete',
            'change .js-address': 'setInstructions'
        },

        setInstructions: function() {
            var self = this;
            self.$('.js-instructions').val('');

            var id = self.address();
            Storage.set('postaladdress', id);

            if (id) {
                var mod = self.addresses.get(id);
                if (mod) {
                    self.$('.js-instructions').val(mod.get('instructions'));
                }
            }
        },

        delete: function() {
            var self = this;
            var id = self.$('.js-address').val();
            if (id) {
                var mod = self.addresses.get(id);

                if (mod) {
                    mod.destroy().then(function() {
                        self.render()
                    });
                }
            }
        },

        address: function() {
            return(this.$('.js-address').val());
        },

        pafaddress: function() {
            return(this.$('.js-pafaddress').val());
        },

        to: function() {
            return(this.$('.js-to').val())
        },

        instructions: function() {
            return(this.$('.js-instructions').val())
        },

        pcChange: function() {
            var self = this;
            self.$('.js-addresscontainer').slideUp('slow');

            var loc = this.$('.js-postcode').typeahead('val');

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: loc
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        self.location = ret.locations[0];
                        self.createSelect();
                    }
                }
            });
        },

        createSelect: function() {
            var self = this;
            self.$('.js-pafaddress').empty();
            self.$('.js-addresscontainer').hide();

            if (self.location) {
                $.ajax({
                    type: 'GET',
                    url: API + 'address',
                    data: {
                        postcodeid: self.location.id
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            _.each(ret.addresses, function(address) {
                                self.$('.js-pafaddress').append('<option value="' + address.id + '" />');
                                self.$('.js-pafaddress option:last').html(address.singleline);
                            });

                            self.$('.js-addresscontainer').slideDown('slow');
                            self.$('.js-pafaddress').click();
                        }
                    }
                });
            }
        },

        postcodeSource: function(query, syncResults, asyncResults) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: query,
                    groupsnear: false
                }, success: function(ret) {
                    var matches = [];
                    _.each(ret.locations, function(location) {
                        matches.push(location.name);
                    });

                    asyncResults(matches);

                    _.delay(function() {
                        self.$('.js-postcode').tooltip('destroy');
                    }, 10000);

                    if (matches.length == 0) {
                        self.$('.js-postcode').tooltip({'trigger':'focus', 'title': 'Please use a valid UK postcode (including the space)'});
                        self.$('.js-postcode').tooltip('show');
                    } else {
                        self.firstMatch = matches[0];

                        if (matches.length == 1) {
                            // If there's only one match, select it
                            self.pcChange();
                        }
                    }
                }
            })
        },

        render: function () {
            var self = this;

            self.addresses = new Iznik.Collections.PostalAddress();
            var p = self.addresses.fetch();

            p.then(function() {
                Iznik.View.prototype.render.call(self).then(function() {
                    self.$('.js-postcode').typeahead({
                        minLength: 3,
                        hint: false,
                        highlight: true
                    }, {
                        name: 'postcodes',
                        source: _.bind(self.postcodeSource, self)
                    });

                    if (self.options.postcode) {
                        self.$('.js-postcode').typeahead('val', self.options.postcode);
                    }

                    if (self.options.to) {
                        self.$('.js-to').val(self.options.to);
                    }

                    if (self.options.showTo) {
                        self.$('.js-to').show();
                    }

                    if (self.addresses.length > 0) {
                        self.$('.js-address').empty();
                        var prev = Storage.get('postaladdress');

                        self.addresses.each(function(address) {
                            var sel = (prev == address.get('id')) ? ' selected' : '';
                            self.$('.js-address').append('<option value="' + address.get('id') + '" ' + sel + '>' + address.get('singleline') + '</option>');
                        });
                        self.setInstructions();
                        self.$('.js-addresses').show();
                    } else {
                        self.$('.js-addresses').hide();
                    }
                });
            });

            return(p);
        }
    });

    Iznik.Views.PostalAddress.Modal = Iznik.Views.Modal.extend({
        template: 'postaladdress_modal',

        events: {
            'click .js-confirm': 'confirm'
        },

        confirm: function() {
            var self = this;

            var pafid = self.postaladdress.pafaddress();
            var instr = self.postaladdress.instructions();

            if (pafid) {
                // Newly added address; store and use it.
                $.ajax({
                    url: API + '/address',
                    type: 'PUT',
                    data: {
                        pafid: pafid,
                        instructions: instr
                    },
                    success: function(ret) {
                        if (ret.ret === 0) {
                            self.trigger('address', ret.id);
                            self.close();
                        }
                    }
                });
            } else {
                // No new address to add - use the one that's selected.
                var id = self.postaladdress.address();

                if (id) {
                    // Might have new instructions.
                    var inst = self.postaladdress.instructions();
                    var mod = new Iznik.Models.PostalAddress({
                        id: id,
                        instructions: inst
                    });

                    mod.save({
                        id: id,
                        instructions: inst
                    }, {
                        patch: true
                    });

                    self.trigger('address', id);
                    self.close();
                }
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Modal.prototype.render.call(this);

            p.then(function() {
                self.postaladdress = new Iznik.Views.PostalAddress();
                self.postaladdress.render().then(function() {
                    self.$('.js-addresses').html(self.postaladdress.$el);
                })
            });

            return(p);
        }
    });
});