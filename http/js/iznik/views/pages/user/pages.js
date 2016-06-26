define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'typeahead'
], function($, _, Backbone, Iznik) {
        Iznik.Views.User.Pages.WhereAmI = Iznik.Views.Page.extend({
        events: {
            'click .js-getloc': 'getLocation',
            'change .js-homegroup': 'changeHomeGroup',
            'typeahead:change .js-postcode': 'locChange',
            'click .tt-suggestion': 'locChange'
        },

        getLocation: function() {
            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        changeHomeGroup: function() {
            try {
                localStorage.setItem('myhomegroup', this.$('.js-homegroup select').val());
            } catch (e) {}
        },

        locChange: function() {
            var loc = this.$('.js-postcode').typeahead('val');

            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: loc
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        self.recordLocation(ret.locations[0], true);
                    }
                }
            });
        },

        recordLocation: function(location, changegroup) {
            var self = this;

            if (this.$('.js-postcode').typeahead('val') != location.name && changegroup) {
                // We've changed location.  We might need to change group too.
                try {
                    localStorage.removeItem('myhomegroup');
                } catch (e) {}
            }

            this.$('.js-postcode').typeahead('val', location.name);
            self.$('.js-next').fadeIn('slow');
            self.$('.js-ok').fadeIn('slow');

            var groupsnear = location.groupsnear;

            try {
                var l = location;

                // Save space.
                delete l.groupsnear;

                Iznik.Session.setSetting('mylocation', l);
                localStorage.setItem('mylocation', JSON.stringify(l))
            } catch (e) {};

            var groups = self.$('.js-groups');

            if (groups.length > 0 && groupsnear) {
                // Show home groups.
                groups.empty();
                _.each(groupsnear, function(groupnear) {
                    groups.append('<option value="' + groupnear.id + '" />');
                    groups.find('option:last').text(groupnear.namedisplay);
                });

                var homegroup = null;

                try {
                    homegroup = localStorage.getItem('myhomegroup');
                } catch (e) {};

                if (homegroup) {
                    groups.val(homegroup);
                }

                self.$('.js-homegroup').fadeIn('slow');
            } else {
                self.$('.js-homegroup').hide();
            }
        },

        gotLocation: function(position) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                }, success: function(ret) {
                    if (ret.ret == 0 && ret.location) {
                        self.recordLocation(ret.location, true);
                    }
                }
            });
        },

        postcodeSource: function(query, syncResults, asyncResults) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: query
                }, success: function(ret) {
                    var matches = [];
                    _.each(ret.locations, function(location) {
                        matches.push(location.name);
                    });

                    asyncResults(matches);

                    if (matches.length == 0) {
                        self.$('.js-postcode').tooltip({'trigger':'focus', 'title': 'Please use a valid UK postcode'});
                        self.$('.js-postcode').tooltip('show');
                    } else {
                        self.$('.js-postcode').tooltip('hide');
                    }
                }
            })
        },

        render: function() {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function(self) {
                if (!navigator.geolocation) {
                    self.$('.js-geoloconly').hide();
                }

                self.$('.js-postcode').typeahead({
                    minLength: 2,
                    hint: false,
                    highlight: true
                }, {
                    name: 'postcodes',
                    source: _.bind(self.postcodeSource, self)
                });

                try {
                    // See if we know where we are from last time.
                    var mylocation = localStorage.getItem('mylocation');
                    
                    if (!mylocation) {
                        mylocation = Iznik.Session.getSetting('mylocation', null);
                    }
                    
                    var postcode = JSON.parse(mylocation).name;

                    if (mylocation) {
                        self.$('.js-postcode').typeahead('val', postcode);
                        self.locChange.call(self);
                    }
                } catch (e) {};

                var groupoverride = $('meta[name=iznikusergroupoverride]').attr("content");
                if (groupoverride) {
                    self.$('.js-groupoverridename').html(groupoverride);
                    self.$('.js-groupoverride').fadeIn('slow');
                }
            });

            return(p);
        }
    });
});