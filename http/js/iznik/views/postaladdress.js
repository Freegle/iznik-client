define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Views.PostalAddress = Iznik.View.extend({
        template: 'postaladdress',

        location: null,

        events: {
            'typeahead:change .js-postcode': 'pcChange'
        },

        address: function() {
            return(this.$('.js-address').val());
        },

        to: function() {
            return(this.$('.js-to').val())
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
            self.$('.js-address').empty();
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
                                self.$('.js-address').append('<option value="' + address.id + '" />');
                                self.$('.js-address option:last').html(address.singleline);
                            });
                            self.$('.js-addresscontainer').slideDown('slow');
                            self.$('.js-address').click();
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
            var p = Iznik.View.prototype.render.call(this);

            p.then(function() {
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
            });

            return(p);
        }
    });
});