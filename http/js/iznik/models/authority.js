define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Authority = Iznik.Model.extend({
        urlRoot: API + 'authority',

        parse: function (ret) {
            if (ret.hasOwnProperty('authority')) {
                return(ret.authority);
            } else {
                return(ret);
            }
        }
    });
});