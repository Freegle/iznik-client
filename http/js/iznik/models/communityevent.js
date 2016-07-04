define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.CommunityEvent = Iznik.Model.extend({
        urlRoot: API + 'communityevent',

        parse: function (ret) {
            if (ret.hasOwnProperty('communityevent')) {
                return(ret.communityevent);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.CommunityEvent = Iznik.Collection.extend({
        model: Iznik.Models.CommunityEvent,

        url: API + 'communityevent',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.communityevents;
        }
    });
});