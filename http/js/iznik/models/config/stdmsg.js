define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/config/modconfig'
], function($, _, Backbone, Iznik) {
    Iznik.Models.ModConfig.StdMessage = Iznik.Model.extend({
        urlRoot: API + 'stdmsg',

        parse: function (ret) {
            return (ret.stdmsg);
        }
    });
});