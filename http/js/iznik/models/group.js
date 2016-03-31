define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Group = Iznik.Model.extend({
        urlRoot: API + 'group',

        parse: function (ret) {
            return (ret.group);
        }
    });
});