require('animate.css');

import SlidingRichMarker from 'iznik/SlidingRichMarker';


define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    'googlemaps-js-rich-marker',
    'iznik/models/visualise'
], function($, _, Backbone, moment, Iznik, r) {
    Iznik.Views.Visualise.Map = Iznik.View.extend({
        template: 'user_visualise_map',

        index: 0,

        firstFetch: true,
        nextTimer: false,
        bounds: null,
        moving: true,
        animating: false,
        to: null,
        from: null,
        marker: null,
        fromMarker: null,
        toMarker: null,
        markers: [],
        asks: [ 'Oooh!', 'Please!', 'Yes!', 'Me?', 'Perfect!', 'Ideal!' ],
        thanks: [ 'Thanks!', 'Ta!', 'Cheers!'],
        welcomes: [ 'Welcome!', 'No prob!' ],

        render: function () {
            var self = this;

            self.items = new Iznik.Collections.Visualise.Items();

            var p = Iznik.View.prototype.render.call(this).then(function () {
                var target = self.$('.js-maparea');
                var mapWidth = target.outerWidth();
                target.css('height', mapWidth + 'px');

                // Set explicit dimensions otherwise map collapses.
                target.css('width', target.width());
                var height =  target.width();
                height = height < 200 ? 200 : height;
                target.css('height', height);

                // Just centre on one of the centres of Britain.  Yes, there are multiple.
                var mapOptions = {
                    mapTypeControl      : false,
                    streetViewControl   : false,
                    center              : new google.maps.LatLng(53.9450, -2.5209),
                    panControl          : mapWidth > 400,
                    zoomControl         : mapWidth > 400,
                    zoom                : 5
                };

                self.map = new google.maps.Map(target.get()[0], mapOptions);

                // Render the map
                google.maps.event.addDomListener(self.map, 'idle', _.bind(self.idle, self));
            });
            return (p);
        },

        doFetch: function() {
            var self = this;

            // Might be the same as the last fetch, but might not be if we started with the map zoomed into
            // one area and then moved it.
            var bounds = self.map.getBounds();
            var ne = bounds.getNorthEast();
            var sw = bounds.getSouthWest();
            var parms = {
                swlat: sw.lat(),
                swlng: sw.lng(),
                nelat: ne.lat(),
                nelng: ne.lng()
            };

            self.items.fetch({
                data: parms,
                remove: false
            }).then(_.bind(self.nextItem, self));
        },

        idle: function() {
            var self = this;

            if (self.firstFetch) {
                self.firstFetch = false;
                self.doFetch();
            } else if (self.moving) {
                if (self.map.getBounds().contains(self.from) && self.map.getBounds().contains(self.to)) {
                    // The map now contains both from and to.  But we might be zoomed out too far.
                    self.nowContains();
                }
            } else if (!self.animating) {
                // We aren't moving. Get the items at the current location.
                self.doFetch();
            }
        },

        nowContains: function() {
            var self = this;

            if (!self.animating && !self.nextTimer) {
                self.animating = true;

                // Create a marker of the person who offered it.
                _.each(self.markers, function(marker) {
                    marker.setMap(null);
                });

                self.markers = [];

                // The sequence is:
                // - sender drops down
                // - item drops down
                // - repliers bounce in
                // - receiver moves to sender
                // - item and receiver move to sender
                var fromicon = {
                    url: self.item.get('from').icon,
                    scaledSize: new google.maps.Size(50, 50),
                    origin: new google.maps.Point(0,0),
                    anchor: new google.maps.Point(25,25)
                };

                // Add the offerer.
                self.fromMarker = new SlidingRichMarker({
                    position: self.from,
                    map: self.map,
                    shadow: 'none'
                });

                var v = new Iznik.Views.Visualise.User({
                    model: new Iznik.Model(self.item.get('from'))
                });

                v.render().then(function() {
                    v.$el.addClass('animated bounceInDown');
                    self.fromMarker.setContent(v.el)
                });

                self.markers.push(self.fromMarker);

                _.delay(function() {
                    self.itemMarker = new SlidingRichMarker({
                        position: self.from,
                        duration: 5000,
                        shadow: 'none',
                        map: self.map,
                        zIndex: google.maps.Marker.MAX_ZINDEX + 1
                    });

                    var v = new Iznik.Views.Visualise.Item({
                        model: self.item
                    });

                    v.render().then(function() {
                        self.itemMarker.setContent(v.el)
                    });

                    self.markers.push(self.itemMarker);

                    _.delay(_.bind(function() {
                        var self = this;
                        _.each(self.item.get('others'), function(other) {
                            var othericon = {
                                url: other.icon,
                                scaledSize: new google.maps.Size(50, 50),
                                origin: new google.maps.Point(0,0),
                                anchor: new google.maps.Point(25,25)
                            };

                            var marker = new SlidingRichMarker({
                                position: new google.maps.LatLng(other.lat, other.lng),
                                shadow: 'none',
                                map: self.map
                            });

                            self.markers.push(marker);

                            var v = new Iznik.Views.Visualise.Other({
                                model: new Iznik.Model(other)
                            });

                            v.render().then(function() {
                                v.$el.addClass('animated zoomIn');
                                marker.setContent(v.el)
                            });

                            self.markers.push(marker);

                            (new Iznik.Views.Visualise.Speech({
                                model: new Iznik.Model({ text: _.sample(self.asks) }),
                                map: self.map,
                                position: new google.maps.LatLng(other.lat, other.lng),
                                startDelay: 1000,
                                endDelay: 2000
                            })).render();
                        });

                        var toicon = {
                            url: self.item.get('to').icon,
                            scaledSize: new google.maps.Size(50, 50),
                            origin: new google.maps.Point(0,0),
                            anchor: new google.maps.Point(25,25)
                        };

                        self.toMarker = new SlidingRichMarker({
                            position: self.to,
                            duration: 5000,
                            map: self.map,
                            shadow: 'none',
                            icon: toicon,
                            zIndex: google.maps.Marker.MAX_ZINDEX + 1
                        });

                        var v = new Iznik.Views.Visualise.User({
                            model: new Iznik.Model(self.item.get('to'))
                        });

                        v.render().then(function() {
                            v.$el.addClass('animated zoomIn');
                            self.toMarker.setContent(v.el)
                        });

                        self.markers.push(self.toMarker);

                        (new Iznik.Views.Visualise.Speech({
                                model: new Iznik.Model({ text: _.sample(self.asks) }),
                                map: self.map,
                                position: self.to,
                                startDelay: 1000,
                                endDelay: 2000
                        })).render();

                        _.delay(_.bind(function() {
                            _.delay(_.bind(function() {
                                // Fade out the others now that the taker is moving there.
                                $('.js-other').addClass('animated zoomOut');
                            }, self), 1000);

                            // // Slide the taker to the offerer.
                            self.toMarker.setPosition(self.from);

                            _.delay(_.bind(function() {
                                // Move back with the item we've collected.
                                self.toMarker.setPosition(self.to);
                                self.itemMarker.setPosition(self.to);

                                _.delay(_.bind(function() {
                                    _.delay(_.bind(function() {
                                        self.itemMarker.setMap(null);
                                    }, self), 5000);

                                    (new Iznik.Views.Visualise.Speech({
                                        model: new Iznik.Model({ text: _.sample(self.thanks) }),
                                        map: self.map,
                                        position: self.to,
                                        startDelay: 5000,
                                        endDelay: 3000
                                    })).render();

                                    (new Iznik.Views.Visualise.Speech({
                                        model: new Iznik.Model({ text: _.sample(self.welcomes) }),
                                        map: self.map,
                                        position: self.from,
                                        startDelay: 6000,
                                        endDelay: 2000
                                    })).render();

                                    self.animating = false;

                                    if (!self.nextTimer) {
                                        self.nextTimer = true;
                                        _.delay(_.bind(self.nextItem, self), 8000);
                                    }
                                }, self), 1000);
                            }, self), 5000);
                        }, self), 3000);
                    }, self), 2000);
                }, 2000);
            }

            // No longer moving the map.
            self.moving = false;
        },

        nextItem: function() {
            var self = this;

            self.nextTimer = false;

            if (self.itemMarker) {
                // Destroy last one.
                self.itemMarker.setMap(null);
            }

            self.item = self.items.shift();

            if (self.item) {
                self.from = new google.maps.LatLng(self.item.get('fromlat'), self.item.get('fromlng'));
                self.to = new google.maps.LatLng(self.item.get('tolat'), self.item.get('tolng'));

                // Find the minimum bounds which the map needs to show.
                self.bounds = new google.maps.LatLngBounds();
                self.bounds.extend(self.from);
                self.bounds.extend(self.to);
                _.each(self.others, function(other) {
                    self.bounds.extend(new google.maps.LatLng(other.lat, other.lng));
                });

                var mapDim = {
                    height: self.$('.js-maparea').height(),
                    width: self.$('.js-maparea').width()
                };

                // Get the zoom.  Zoom out one so that we have some space for info windows
                var idealZoom = Iznik.getBoundsZoomLevel(self.bounds, mapDim) - 1;
                idealZoom = Math.max(10, idealZoom);

                if (idealZoom != self.map.getZoom() || !self.map.getBounds().contains(self.from) || !self.map.getBounds().contains(self.to)) {
                    // The map doesn't currently contain the points we need.
                    self.moving = true;
                    self.map.fitBounds(self.bounds);
                    self.map.setZoom(idealZoom);
                } else {
                    // The map does currently contain the point we need.
                    self.moving = false;
                    self.nowContains();
                }
            } else {
                self.doFetch();
            }
        }
    });

    Iznik.Views.Visualise.Item = Iznik.View.extend({
        template: 'user_visualise_item'
    });

    Iznik.Views.Visualise.User = Iznik.View.extend({
        template: 'user_visualise_user'
    });

    Iznik.Views.Visualise.Other = Iznik.View.extend({
        template: 'user_visualise_other'
    });

    Iznik.Views.Visualise.Speech = Iznik.View.extend({
        template: 'user_visualise_speech',

        render: function() {
            var self = this;

            _.delay(_.bind(function() {
                var self = this;

                self.marker = new RichMarker({
                    position: self.options.position,
                    map: self.options.map,
                    shadow: 'none'
                });

                var p = Iznik.View.prototype.render.call(self);

                p.then(function() {
                    self.marker.setContent(self.el);
                    console.log("Set content", self.el.innerHTML);
                    self.$el.addClass('animated zoomIn');

                    _.delay(_.bind(function() {
                        console.log("Zoom out");
                        self.$el.addClass('animated zoomOut');

                        _.delay(_.bind(function() {
                            self.marker.setMap(null);
                            self.destroyIt();
                        }, self), 1000);
                    }, self), self.options.endDelay);
                });
            }, self), self.options.startDelay)
        }
    });
});