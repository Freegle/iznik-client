define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Models.Visualise.Item = Iznik.Model.extend({});

    Iznik.Collections.Visualise.Items = Iznik.Collection.extend({
        model: Iznik.Models.Visualise.Item,

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        url: API + 'visualise',

        parse: function(ret) {
            var self = this;
            self.ret = ret;
            return(ret.list);
        }
    });
});