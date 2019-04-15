define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base'
], function ($, _, Backbone, Iznik) {
  Iznik.Models.Admin = Iznik.Model.extend({
    urlRoot: API + 'admin',

    parse: function (ret) {
      if (ret.hasOwnProperty('admin')) {
        return (ret.admin)
      } else {
        return (ret)
      }
    },

    delete: function (reason) {
      var self = this

      return $.ajax({
        type: 'POST',
        headers: {
          'X-HTTP-Method-Override': 'DELETE'
        },
        url: API + 'admin/' + self.get('id'),
        data: {
          id: self.get('id'),
          reason: reason
        }, success: function (ret) {
          self.destroy()
        }
      })
    }
  })

  Iznik.Collections.Admin = Iznik.Collection.extend({
    url: API + 'admin',

    model: Iznik.Models.Admin,

    parse: function (ret) {
      return (ret.admins)
    }
  })
})