import SlidingMarker from 'marker-animate-unobtrusive';

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
        infos: [],
        asks: [ 'Oooh!', 'Please!', 'Yes!', 'Me?', 'Perfect!', 'Ideal!' ],

        render: function () {
            var self = this;

            // We want all marker moves to be animated.
            SlidingMarker.initializeGlobally();

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
            console.log("nowContains", self.moving, self.animating);

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
                self.fromMarker = new google.maps.Marker({
                    position: self.from,
                    animation: google.maps.Animation.DROP,
                    map: self.map,
                    icon: fromicon
                });

                self.markers.push(self.fromMarker);

                _.delay(_.bind(function() {
                    // Make the offerer bounce.
                    self.fromMarker.setAnimation(google.maps.Animation.BOUNCE);
                }, self), 500);

                _.delay(function() {
                    // Stop offerer bouncing and show item.
                    self.fromMarker.setAnimation(null);

                    self.frominfo = new google.maps.InfoWindow({
                        content: '<img src="' + self.item.get('attachment').thumb + '?w=150" class="img-mediumthumbnail img-thumbnail img-rounded img-responsive" />',
                        disableAutoPan: true
                    });

                    self.frominfo.open(self.map, self.fromMarker);
                    $(".gm-style-iw").next("div").hide();

                    _.delay(_.bind(function() {
                        var self = this;
                        console.log("Got others", self.item.get('others').length);
                        _.each(self.item.get('others'), function(other) {
                            var othericon = {
                                url: other.icon,
                                scaledSize: new google.maps.Size(50, 50),
                                origin: new google.maps.Point(0,0),
                                anchor: new google.maps.Point(25,25)
                            };

                            var marker = new google.maps.Marker({
                                position: new google.maps.LatLng(other.lat, other.lng),
                                animation: google.maps.Animation.BOUNCE,
                                map: self.map,
                                icon: othericon
                            });

                            self.markers.push(marker);

                            var info = new google.maps.InfoWindow({
                                content: '<b>' + _.sample(self.asks) + '</b>',
                                disableAutoPan: true
                            });

                            info.open(self.map, marker);
                            $(".gm-style-iw").next("div").hide();

                            self.infos.push(info);
                        });

                        var toicon = {
                            url: self.item.get('to').icon,
                            scaledSize: new google.maps.Size(50, 50),
                            origin: new google.maps.Point(0,0),
                            anchor: new google.maps.Point(25,25)
                        };

                        self.toMarker = new SlidingMarker({
                            position: self.to,
                            animation: google.maps.Animation.BOUNCE,
                            duration: 5000,
                            map: self.map,
                            icon: toicon,
                            zIndex: google.maps.Marker.MAX_ZINDEX + 1
                        });

                        self.markers.push(self.toMarker);

                        var info = new google.maps.InfoWindow({
                            content: '<b>' + _.sample(self.asks) + '</b>',
                            disableAutoPan: true
                        });

                        info.open(self.map, self.toMarker);
                        $(".gm-style-iw").next("div").hide();

                        self.infos.push(info);

                        _.delay(_.bind(function() {
                            // Stop them all bouncing.
                            _.each(self.markers, function(marker) {
                                marker.setAnimation(null);
                            });

                            // Close their info windows.
                            _.each(self.infos, function(info) {
                                info.close();
                            });

                            self.infos = [];

                            // // Slide the taker to the offerer.
                            self.toMarker.setPosition(self.from);

                            _.delay(_.bind(function() {
                                // Close the offerer info and open the taker - at the same place.
                                self.frominfo.close();

                                self.toinfo = new google.maps.InfoWindow({
                                    content: '<img src="' + self.item.get('attachment').thumb + '?w=150" class="img-mediumthumbnail img-thumbnail img-rounded img-responsive" />',
                                    disableAutoPan: true
                                });

                                self.toinfo.open(self.map, self.toMarker);
                                $(".gm-style-iw").next("div").hide();

                                // Move back with the item we've collected; the info window will tag along.
                                self.toMarker.setPosition(self.to);

                                self.animating = false;

                                if (!self.nextTimer) {
                                    self.nextTimer = true;
                                    _.delay(_.bind(self.nextItem, self), 6000);
                                }
                            }, self), 5000);
                        }, self), 2000);
                    }, self), 2000);
                }, 2000);
            }

            // No longer moving the map.
            self.moving = false;
        },

        nextItem: function() {
            var self = this;

            self.nextTimer = false;

            if (self.marker) {
                // Destroy last one.
                self.marker.setMap(null);
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
                console.log("Ideal zoom", idealZoom);
                idealZoom = Math.max(10, idealZoom);

                if (idealZoom != self.map.getZoom() || !self.map.getBounds().contains(self.from) || !self.map.getBounds().contains(self.to)) {
                    // The map doesn't currently contain the points we need.
                    console.log("Need to move");
                    self.moving = true;
                    self.map.fitBounds(self.bounds);
                    self.map.setZoom(idealZoom);
                    console.log("Zoom to", idealZoom);
                } else {
                    // The map does currently contain the point we need.
                    console.log("No need to move");
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
});