define([
  'jquery',
  'underscore',
  'backbone',
  'iznik/base',
  'googlemaps-js-rich-marker',
  'geocomplete'
], function ($, _, Backbone, Iznik) {
  Iznik.Views.User.Noticeboard = {}

  Iznik.Views.User.Noticeboard.Added = Iznik.Views.Modal.extend({
    template: 'user_noticeboard_added',

    events: {
      'click .js-update': 'saveUpdate'
    },

    saveUpdate: function() {
      var self = this;
      var pos = self.map.getPosition()
      var lat = pos.lat();
      var lng = pos.lng();
      var name = self.$('.js-name').val();

      if (name.length) {
        $.ajax({
          type: 'PATCH',
          url: API + 'noticeboard',
          data: {
            id: self.model.get('id'),
            lat: lat,
            lng: lng,
            name: name,
            description: self.$('.js-description').val()
          }, complete: function() {
            self.close();
          }
        });
      } else {
        self.$('.js-name').focus().addClass('error-border');
      }
    },

    render: function() {
      var self = this;

      var p = Iznik.Views.Modal.prototype.render.call(this);

      p.then(function() {
        self.map = new Iznik.Views.User.Noticeboard.Map({
          model: new Iznik.Model({
            clat: self.model.get('lat'),
            clng: self.model.get('lng'),
            target: self.$('.js-maparea'),
            zoom: 17
          }),
          summary: false
        })

        self.map.render().then(function () {
        })
      });

      return(p)
    }
  })

  Iznik.Views.User.Noticeboard.Map = Iznik.View.extend({
    getPosition: function() {
      return(this.marker.getPosition())
    },

    updateMap: function() {
      var self = this;

      if (self.marker) {
        self.marker.setMap(null)
      }

      var icon = '/images/mapmarker.gif?a=1'
      self.marker = new google.maps.Marker({
        position: self.map.getCenter(),
        icon: icon,
        map: self.map,
      })
    },

    render: function () {
      var self = this

      // Note target might be outside this view.
      var target = $(self.model.get('target'))
      console.log("Target", target)
      var mapWidth = target.outerWidth()

      // Create map centred on the specified place.
      var mapOptions = {
        mapTypeControl: false,
        streetViewControl: false,
        center: new google.maps.LatLng(this.model.get('clat'), this.model.get('clng')),
        panControl: mapWidth > 400,
        zoomControl: mapWidth > 400,
        zoom: self.model.get('zoom'),
        gestureHandling: 'greedy'
      }

      self.map = new google.maps.Map(target.get()[0], mapOptions)

      google.maps.event.addDomListener(self.map, 'idle', function () {
        self.updateMap()
      })

      // Keep the marker in the centre.
      google.maps.event.addDomListener(self.map, 'drag', function () {
        self.updateMap()
      })

      return (Iznik.resolvedPromise(self))
    }
  })
})