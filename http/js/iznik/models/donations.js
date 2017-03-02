define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Donations = Iznik.Model.extend({
        url: API + 'donations',

        parse: function(ret) {
            return(ret.donations);
        }
    });
});