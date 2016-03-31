define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.ModConfig = Iznik.Model.extend({
        urlRoot: API + 'modconfig',

        parse: function (ret) {
            return (ret.config);
        }
    });
});