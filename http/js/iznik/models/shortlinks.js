define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Shortlink = Iznik.Model.extend({
        urlRoot: API + 'shortlink',

        parse: function (ret) {
            if (ret.hasOwnProperty('shortlink')) {
                return(ret.shortlink);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.Shortlink = Iznik.Collection.extend({
        model: Iznik.Models.Shortlink,

        url: API + 'shortlink',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.shortlinks;
        }
    });
});