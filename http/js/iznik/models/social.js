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

    Iznik.Models.Request = Iznik.Model.extend({
        urlRoot: API + 'request',

        completed: function() {
            var self = this;

            var p = $.ajax({
                url: API + '/request',
                type: 'POST',
                data: {
                    id: self.get('id'),
                    action: 'Completed'
                }
            });

            return(p);
        },

        parse: function (ret) {
            if (ret.hasOwnProperty('request')) {
                return(ret.request);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.Requests = Iznik.Collection.extend({
        model: Iznik.Models.Request,

        url: API + 'request',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.requests;
        }
    });

    Iznik.Collections.Requests.Recent = Iznik.Collection.extend({
        model: Iznik.Models.Request,

        url: API + 'request',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.recent;
        }
    });
});