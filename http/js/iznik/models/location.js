define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
        Iznik.Models.Location = Iznik.Model.extend({
        url: function() {
            return (API + 'locations/' + this.get('id'));
        },

        parse: function(ret) {
            var location;

            if (ret.hasOwnProperty('location')) {
                location = ret.location;
            } else {
                location = ret;
            }

            return(location);
        }
    });

    Iznik.Collections.Locations = Iznik.Collection.extend({
        model: Iznik.Models.Location,

        url: function () {
            var url = API + 'locations';
            if (this.options.swlat || this.options.swlng || this.options.nelat || this.options.nelng) {
                url += '?swlat=' + this.options.swlat + "&swlng=" + this.options.swlng + "&nelat=" + this.options.nelat + "&nelng=" + this.options.nelng;
            }

            return(url);
        },

        parse: function(ret) {
            return(ret.locations);
        }
    });
});