define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/config/modconfig'
], function($, _, Backbone, Iznik) {
    Iznik.Models.ModConfig.BulkOp = Iznik.Model.extend({
        urlRoot: API + 'bulkop',

        parse: function (ret) {
            return (ret.bulkop);
        }
    });
});