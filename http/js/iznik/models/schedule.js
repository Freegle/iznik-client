define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Schedule = Iznik.Model.extend({
        urlRoot: API + 'schedule',

        parse: function (ret) {
            if (ret.hasOwnProperty('schedule')) {
                return(ret.schedule);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.Schedule = Iznik.Collection.extend({
        url: API + 'schedule',

        model: Iznik.Models.Schedule,

        parse: function (ret) {
            return (ret.schedules);
        }
    });
});