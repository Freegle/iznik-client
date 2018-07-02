define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Team = Iznik.Model.extend({
        urlRoot: API + 'team',

        add: function(userid) {
            var self = this;
            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: API + 'team',
                    type: 'PATCH',
                    data: {
                        id: self.get('id'),
                        action: 'Add',
                        userid: userid
                    }, success: function (ret) {
                        if (ret.ret === 0) {
                            resolve();
                        } else {
                            reject(ret);
                        }
                    }, error: function() {
                        reject(null);
                    }
                })
            });

            return(p);
        },

        remove: function(userid) {
            var self = this;
            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: API + 'team',
                    type: 'PATCH',
                    data: {
                        id: self.get('id'),
                        userid: userid,
                        action: 'Remove'
                    }, success: function (ret) {
                        if (ret.ret === 0) {
                            resolve();
                        } else {
                            reject(ret);
                        }
                    }, error: function() {
                        reject(null);
                    }
                })
            });

            return(p);
        },

        parse: function (ret) {
            if (ret.hasOwnProperty('team')) {
                return(ret.team);
            } else {
                return(ret);
            }
        }
    });

    Iznik.Collections.Team = Iznik.Collection.extend({
        model: Iznik.Models.Team,

        url: API + 'team',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.teams;
        }
    });
});