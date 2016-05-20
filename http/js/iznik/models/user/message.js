define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/message'
], function($, _, Backbone, Iznik) {
    Iznik.Models.Message.Attachment = Iznik.Model.extend({
        urlRoot: API + 'image'
    });
});