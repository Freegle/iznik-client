define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.User.Search = Iznik.Model.extend({
        urlRoot: API + 'usersearch',

        parse: function (ret) {
            if (ret.hasOwnProperty('usersearch')) {
                return (ret.usersearch);
            } else {
                return (ret);
            }
        }
    });

    Iznik.Collections.User.Search = Iznik.Collection.extend({
        url: API + 'usersearch',

        model: Iznik.Models.User.Search,

        parse: function(ret) {
            return(ret.usersearches);
        }
    })
});