define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Admin = Iznik.Model.extend({
        urlRoot: API + 'admin',

        parse: function (ret) {
            if (ret.hasOwnProperty('admin')) {
                return(ret.admin);
            } else {
                return(ret);
            }
        }
    });
});