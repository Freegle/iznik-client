define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.SocialAction = Iznik.Model.extend({
        urlRoot: API + 'socialactions',

        parse: function (ret) {
            if (ret.hasOwnProperty('socialaction')) {
                return(ret.socialaction);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.SocialActions = Iznik.Collection.extend({
        model: Iznik.Models.SocialAction,

        url: API + 'socialactions',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.socialactions;
        }
    });
});