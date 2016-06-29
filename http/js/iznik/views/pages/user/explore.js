define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/models/group',
    'gmaps',
    'richMarker',
    'jquery.geocomplete'
], function ($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Explore = Iznik.Views.Page.extend({
        template: 'user_explore_main',
        
        events: {
            'click .js-getloc': 'getLocation'
        },

        getLocation: function() {
            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        gotLocation: function(position) {
            this.map.moveTo(position.coords.latitude, position.coords.longitude);
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                if (!navigator.geolocation) {
                    self.$('.js-geoloconly').hide();
                }

                self.$('.js-location').geocomplete({
                    'componentRestrictions': {
                        country: ['uk']
                    }
                }).bind("geocode:result", function (event, result) {
                    self.map.moveTo(result.geometry.location.lat(), result.geometry.location.lng());
                });

                self.map = new Iznik.Views.Map({
                    model: new Iznik.Model({
                        clat: 53.9450,
                        clng: -2.5209,
                        zoom: 5,
                        target: self.$('.js-maparea')
                    })
                });

                self.map.render();
            });

            return (p);
        }
    });

    Iznik.Views.Map = Iznik.View.extend({
        infoWindow: null,

        fetched: false,
        lastBounds: null,

        addMarker: function(marker) {
            var self = this;

            marker.setMap(this.map);
            this.markers.push(marker);
        },

        updateMap: function() {
            var self = this;

            // Get new markers for current map bounds.
            var bounds = self.map.getBounds();
            var boundsstr = bounds.toString();

            if (!self.lastBounds || boundsstr != self.lastBounds) {
                // Remove old markers
                if (this.markers) {
                    _.each(this.markers, function(marker) {
                        marker.setMap(null);
                    });

                    this.markers = [];
                }

                var views = _.union(this.groupViews, this.groupTextViews);
                if (views) {
                    _.each(views, function(view) {
                        view.destroyIt();
                    });

                    this.groupViews = [];
                    this.groupTextViews = [];
                }

                $('.js-grouptextlist').empty();

                self.lastBounds = boundsstr;
                var within = 0;
                self.collection.each(function(group) {
                    if (bounds.contains(new google.maps.LatLng(group.get('lat'), group.get('lng'))) &&
                        group.get('onmap')) {
                        within++
                    }
                });

                self.collection.each(function(group) {
                    if (bounds.contains(new google.maps.LatLng(group.get('lat'), group.get('lng'))) &&
                        group.get('onmap')) {
                        var latLng = new google.maps.LatLng(group.get('lat'), group.get('lng'));

                        if (within > 20) {
                            // Switch to pins for large collections
                            var marker = new google.maps.Marker({
                                position: latLng,
                                icon: '/images/map-pin.gif',
                                title: group.get('namedisplay')
                            });

                            google.maps.event.addListener(marker, 'click', function() {
                                self.map.setZoom(12);
                                self.map.setCenter(marker.getPosition());
                                self.updateMap();
                            });
                        } else {
                            var marker = new RichMarker({
                                position: latLng
                            });

                            var content = new Iznik.Views.Map.Group({
                                model: group,
                                map: self.map,
                                marker: marker
                            });

                            self.groupViews.push(content);

                            self.listenTo(content, 'opened', function(opened) {
                                self.groupViews.forEach(function(element, index, array) {
                                    console.log("Compare", element, opened);
                                    if (element != opened) {
                                        element.close();
                                    }
                                });
                            });

                            // Show the name as a tooltip below, which is always shown.  This helps when we
                            // don't have a group logo.
                            content.render().then(function() {
                                content.$el.tooltip({
                                    'trigger': 'manual',
                                    'placement': 'bottom',
                                    'title': content.model.get('namedisplay')
                                });

                                marker.setContent(content.el);
                                content.$el.tooltip('show');
                            });

                            marker.setFlat(true);
                        }

                        marker.setDraggable(false);
                        self.addMarker(marker);

                        var v = new Iznik.Views.Map.GroupText({
                            model: group
                        });
                        v.render().then(function() {
                            $('.js-grouptextlist').append(v.$el);
                        });
                    }
                });
            }
        },

        resize: function() {
            var mapWidth = target.outerWidth();
            target.css('height', mapWidth + 'px');
            google.maps.event.trigger(this.map, "resize");
        },

        moveTo: function(lat, lng) {
            this.map.setCenter(new google.maps.LatLng(lat, lng));
            this.map.setZoom(11);
        },

        render: function(){
            var self = this;

            self.markers = [];
            self.groupViews = [];
            self.groupTextViews = [];

            // Note target might be outside this view.
            var target = $(self.model.get('target'));
            var mapWidth = target.outerWidth();
            target.css('height', mapWidth + 'px');

            // Set explicit dimensions otherwise map collapses.
            target.css('width', target.width());
            var height = Math.floor($(window).innerHeight() / 2);
            height = height < 200 ? 200 : height;
            target.css('height', height);

            // Create map centred on the specified place.
            var mapOptions = {
                mapTypeControl      : false,
                streetViewControl   : false,
                center              : new google.maps.LatLng(this.model.get('clat'), this.model.get('clng')),
                panControl          : mapWidth > 400,
                zoomControl         : mapWidth > 400,
                zoom                : self.model.get('zoom')
            };

            self.map = new google.maps.Map(target.get()[0], mapOptions);

            google.maps.event.addDomListener(window, 'resize', _.bind(self.resize, self));
            google.maps.event.addDomListener(window, 'load', _.bind(self.resize, self));

            // Render the map
            google.maps.event.addDomListener(self.map, 'idle', function() {
                if (!self.fetched) {
                    // Get all the groups.  There aren't too many, and this means we are responsive when panning or zooming.
                    self.collection = new Iznik.Collections.Group();
                    self.collection.fetch({
                        data: {
                            grouptype: 'Freegle'
                        }
                    }).then(function() {
                        $('.js-numgroups').html(self.collection.length);
                        $('.js-groupsumm').css('visibility', 'visible');
                        $('.js-groupsumm').fadeIn('slow');
                        self.fetched = true;
                        self.updateMap();
                    });
                } else {
                    self.updateMap();
                }
            });

            return(resolvedPromise(self));
        }
    });

    Iznik.Views.Map.Group = Iznik.View.extend({
        template: 'user_explore_group',

        events: {
            'click' : 'showDetails'
        },

        showDetails: function() {
            Router.navigate('/explore/' + this.model.get('id'), true);
        }
    });

    Iznik.Views.Map.GroupText = Iznik.View.extend({
        template: 'user_explore_grouptext'
    });
});
