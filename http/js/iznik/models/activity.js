define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Models.Activity.Message = Iznik.Model.extend({

    });

    Iznik.Collections.Activity.RecentMessages = Iznik.Collection.extend({
        model: Iznik.Models.Activity.Message,

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        url: API + 'activity',

        parse: function(ret) {
            var self = this;
            self.ret = ret;
            return(ret.recentmessages);
        }
    });
});