define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.ModTools.Spammer = Iznik.Model.extend({
        urlRoot: API + 'spammers',

        parse: function (ret) {
            // We might either be called from a collection, where the spammer is at the top level, or
            // from getting an individual spammer, where it's not.
            if (ret.hasOwnProperty('spammer')) {
                return (ret.spammer);
            } else {
                return (ret);
            }
        },

        requestRemove: function (reason) {
            var self = this;

            $.ajax({
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                url: API + 'spammers/' + self.get('id'),
                data: {
                    id: self.get('id'),
                    collection: 'PendingRemove',
                    reason: reason
                }, success: function (ret) {
                    self.remove();
                }
            })
        },

        delete: function (reason) {
            var self = this;

            $.ajax({
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'DELETE'
                },
                url: API + 'spammers/' + self.get('id'),
                data: {
                    id: self.get('id'),
                    reason: reason
                }, success: function (ret) {
                    self.remove();
                }
            })
        }
    });

    Iznik.Collections.ModTools.Spammers = Iznik.Collection.extend({
        url: API + 'spammers',

        model: Iznik.Models.ModTools.Spammer,

        initialize: function (options) {
            this.options = options;

            // Use a comparator to show in most recent first order
            this.comparator = function (a) {
                return (-(new Date(a.get('added'))).getTime());
            }
        },

        parse: function (ret) {
            // Save off the return in case we need any info from it, e.g. context for searches.
            this.ret = ret;
            return (ret.spammers);
        }
    });
});