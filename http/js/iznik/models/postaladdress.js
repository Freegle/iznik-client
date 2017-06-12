define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.PostalAddress = Iznik.Model.extend({
        urlRoot: API + 'address',

        parse: function (ret) {
            if (ret.hasOwnProperty('address')) {
                return(ret.address);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.PostalAddress = Iznik.Collection.extend({
        url: API + 'address',

        model: Iznik.Models.PostalAddress,

        parse: function (ret) {
            return (ret.addresses);
        }
    });
});