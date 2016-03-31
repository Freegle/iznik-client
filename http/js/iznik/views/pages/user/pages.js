define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik) {
        Iznik.Views.User.Pages.WhereAmI = Iznik.Views.Page.extend({
        events: function(){
            return _.extend({}, Iznik.Views.Page.prototype.events,{
                'click .js-getloc': 'getLocation',
                'change .js-homegroup': 'changeHomeGroup',
                'typeahead:change .js-postcode': 'locChange'
            });
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
            console.log("loc change");
            var loc = this.$('.js-postcode').typeahead('val');
            console.log("Loc is ", loc);

            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: loc
                }, success: function(ret) {
                    console.log("Get loc", ret);
                    if (ret.ret == 0) {
                        self.recordLocation(ret.locations[0], true);
                    }
                }
            });
        },

        recordLocation: function(location, changegroup) {
            var self = this;

            console.log("Compare location", this.$('.js-postcode').typeahead('val'), location.name, location);

            if (this.$('.js-postcode').typeahead('val') != location.name && changegroup) {
                // We've changed location.  We might need to change group too.
                console.log("Location changed");
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
                    })

                    asyncResults(matches);
                }
            })
        },

        render: function() {
            var self = this;
            Iznik.Views.Page.prototype.render.call(this);

            if (!navigator.geolocation) {
                this.$('.js-geoloconly').hide();
            }

            this.$('.js-postcode').typeahead({
                minLength: 2,
                hint: false,
                highlight: true
            }, {
                name: 'postcodes',
                source: this.postcodeSource
            });

            try {
                // See if we know where we are from last time.
                var mylocation = localStorage.getItem('mylocation');
                console.log("Got old location", mylocation);
                var postcode = JSON.parse(mylocation).name;
                console.log("Postcode", postcode);

                if (mylocation) {
                    this.$('.js-postcode').typeahead('val', postcode);
                    console.log("Set it");
                    this.locChange.call(this);
                    console.log("Called change");
                }
            } catch (e) {};

            return(this);
        }
    });
});