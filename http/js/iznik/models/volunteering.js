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
        },

        renew: function() {
            var self = this;

            var p = $.ajax({
                url: API + 'volunteering/' + self.get('id'),
                method: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                data: {
                    action: 'Renew'
                }
            });

            return(p);
        },

        expire: function() {
            var self = this;

            var p = $.ajax({
                url: API + 'volunteering/' + self.get('id'),
                method: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                data: {
                    action: 'Expire'
                }
            });

            return(p);
        }
    });

    Iznik.Collections.Volunteering = Iznik.Collection.extend({
        model: Iznik.Models.Volunteering,

        url: API + 'volunteering?systemwide=true',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.volunteerings;
        }
    });
});