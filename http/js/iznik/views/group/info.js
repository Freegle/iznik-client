define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'jquery.dd'
], function($, _, Backbone, Iznik) {
    var groupSelectIdCounter = 0;

    Iznik.Views.Group.Info = Iznik.View.extend({
        template: 'group_info'
    });
});