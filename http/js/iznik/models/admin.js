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

    Iznik.Collections.Admin = Iznik.Collection.extend({
        url: API + 'admin',

        model: Iznik.Models.Admin,

        parse: function (ret) {
            return (ret.admins);
        }
    });
});