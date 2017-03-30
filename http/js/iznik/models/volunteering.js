define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Volunteering = Iznik.Model.extend({
        urlRoot: API + 'volunteering',

        parse: function (ret) {
            if (ret.hasOwnProperty('volunteering')) {
                return(ret.volunteering);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.Volunteering = Iznik.Collection.extend({
        model: Iznik.Models.Volunteering,

        url: API + 'volunteering',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.volunteerings;
        }
    });
});