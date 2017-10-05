define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Group = Iznik.Model.extend({
        urlRoot: API + 'group',

        parse: function (ret) {
            if (ret.hasOwnProperty('group')) {
                return(ret.group);
            } else {
                return(ret);
            }
        },

        recordFacebookShare: function(uid, msgid, msgarrival) {
            return($.ajax({
                url: API + 'group',
                type: 'POST',
                data: {
                    action: 'RecordFacebookShare',
                    uid: uid,
                    msgid: msgid,
                    msgarrival: msgarrival
                }
            }));
        },

        removeFacebookGroup: function(uid) {
            return($.ajax({
                url: API + 'group',
                type: 'POST',
                data: {
                    action: 'RemoveFacebookGroup',
                    id: this.get('id'),
                    uid: uid
                }
            }));
        }
    });

    Iznik.Collections.Group = Iznik.Collection.extend({
        model: Iznik.Models.Group,

        url: API + 'groups',

        ret: null,

        initialize: function (models, options) {
            this.options = options;
        },

        parse: function(ret) {
            return ret.groups;
        }
    });
});