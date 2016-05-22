define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Alert = Iznik.Model.extend({
        urlRoot: API + 'alert',

        parse: function (ret) {
            if (ret.hasOwnProperty('alert')) {
                return (ret.alert);
            } else {
                return (ret);
            }
        }
    });
});