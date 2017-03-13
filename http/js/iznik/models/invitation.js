define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Invitation = Iznik.Model.extend({
        urlRoot: API + 'invitation',

        parse: function (ret) {
            if (ret.hasOwnProperty('invitation')) {
                return (ret.socialaction);
            } else {
                return (ret);
            }
        }
    });

    Iznik.Collections.Invitations = Iznik.Collection.extend({
        model: Iznik.Models.Invitation,

        url: API + 'invitation',

        parse: function (ret) {
            return ret.invitations;
        }
    });
});