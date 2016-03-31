define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
        Iznik.Models.Message.Attachment = Iznik.Model.extend({
        urlRoot: API + 'image'
    });
});