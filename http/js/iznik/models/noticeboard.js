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

  Iznik.Collections.Noticeboards = Iznik.Collection.extend({
    model: Iznik.Models.Noticeboard,

    url: API + 'noticeboard',

    parse: function (ret) {
      return ret.noticeboards;
    }
  });
});