define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Models.Log = Iznik.Model.extend({

    });

    Iznik.Collections.Logs = Iznik.Collection.extend({
        model: Iznik.Models.Log,

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        url: API + 'logs',

        parse: function(ret) {
            var self = this;
            self.ret = ret;
            return(ret.logs);
        }
    });
});