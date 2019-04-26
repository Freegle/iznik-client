define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base'
], function($, _, Backbone, Iznik) {
  Iznik.Models.Noticeboard = Iznik.Model.extend({
    urlRoot: API + 'noticeboard',

    parse: function (ret) {
      if (ret.hasOwnProperty('noticeboard')) {
        return (ret.noticeboard);
      } else {
        return (ret);
      }
    }
  });
});