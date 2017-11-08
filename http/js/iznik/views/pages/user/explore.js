define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/pages/user/group',
    'iznik/views/user/message',
    'iznik/models/group',
    'gmaps',
    'richMarker',
    'jquery.geocomplete'
], function ($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Pages.Explore = Iznik.Views.Page.extend({
        template: 'user_explore_main',

        title: 'Explore',
        
        events: {
            'click .js-getloc': 'getLocation',
            'click .js-locbtn': 'locButton',
            'keyup .js-location': 'keyUp'
        },

        keyUp: function(e) {
            if (e.which === 13) {
                // Just suppress - we want them to choose from the autocomplete.
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        },

        locButton: function() {
            this.$('.js-location').trigger('geocode');
        },

        getLocation: function() {
            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        gotLocation: function(position) {
            this.map.moveTo(position.coords.latitude, position.coords.longitude);
        },

        render: function () {
            var self = this;

            self.title = self.options.region ? ("Groups in " + self.options.region) : 'Explore';

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                if (!navigator.geolocation) {
                    self.$('.js-geoloconly').hide();
                }

                self.$('.js-location').geocomplete({
                    types: ['(cities)'],
                    componentRestrictions: {
                        country: ['uk']
                    }
                }).bind("geocode:result", function (event, result) {
                    self.map.moveTo(result.geometry.location.lat(), result.geometry.location.lng());
                });

                // Get all the groups.  There aren't too many, and this means we are responsive when panning or zooming.
                self.collection = new Iznik.Collections.Group();
                self.collection.fetch({
                    data: {
                        grouptype: 'Freegle',
                    }
                }).then(function() {
                    if (!self.options.region) {
                        self.$('.js-exploreall').show();
                        self.$('.js-exploreregion').remove();
                        self.$('.js-findholder').show();

                        // Just centre on one of the centres of Britain.  Yes, there are multiple.
                        self.map = new Iznik.Views.Map({
                            model: new Iznik.Model({
                                clat: 53.9450,
                                clng: -2.5209,
                                zoom: 5,
                                target: self.$('.js-maparea')
                            }),
                            collection: self.collection,
                            summary: true,
                            bounds: null
                        });

                        // Add links for the different regions.
                        self.regions = new Iznik.Collection();
                        self.regions.comparator = 'id';
                        self.collection.each(function(group) {
                            var region = group.get('region');

                            if (region && region.length > 0) {
                                self.regions.add(new Iznik.Model({
                                    id: region
                                }));
                            }
                        });

                        self.regionCollectionView = new Backbone.CollectionView({
                            el: self.$('.js-regions'),
                            modelView: Iznik.Views.User.Pages.Explore.Region,
                            collection: self.regions,
                            processKeyEvents: false
                        });

                        self.regionCollectionView.render();
                        self.$('.js-regionholder').fadeIn('slow');
                    } else {
                        // We have a specific region to show.  First find the relevant groups.
                        self.$('.js-region').html(self.options.region);
                        self.$('.js-exploreall').remove();
                        self.$('.js-exploreregion').show();
                        var newgroups = self.collection.where({
                            region: self.options.region
                        });
                        self.collection = new Iznik.Collection(newgroups);

                        // Find a centre and bounding box
                        var swlat = swlng = nelat = nelng = clat = clng = 0;
                        var bounds = new google.maps.LatLngBounds();
                        self.collection.each(function(group) {
                            var lat = group.get('lat');
                            var lng = group.get('lng');
                            swlat = lat < swlat ? lat : swlat;
                            swlng = lng < swlng ? lng : swlng;
                            nelat = lat > nelat ? lat : nelat;
                            nelng = lng > nelng ? lng : nelng;
                            bounds = bounds.extend(new google.maps.LatLng(lat, lng));
                        });

                        var clat = (swlat + nelat) / 2;
                        var clng = (swlng + nelng) / 2;

                        self.map = new Iznik.Views.Map({
                            model: new Iznik.Model({
                                clat: clat,
                                clng: clng,
                                target: self.$('.js-maparea')
                            }),
                            collection: self.collection,
                            summary: false,
                            bounds: bounds
                        });
                    }

                    self.map.render().then(function() {
                        if (self.options.search) {
                            // We've been asked to search for a place.
                            self.$('.js-location').val(self.options.search);
                            self.locButton();
                        }

                        self.$('.js-nogroup').fadeIn('slow');
                    });
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.Explore.Region = Iznik.View.extend({
        template: 'user_explore_region',
        tagName: 'li'
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

        fitted: false,

        updateMap: function() {
            var self = this;

            if (self.options.bounds && !self.fitted) {
                self.fitted = true;
                self.map.fitBounds(self.options.bounds);
            }

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

                if (self.options.summary) {
                    $('.js-numgroups').html(self.collection.length);
                    $('.js-groupsumm').css('visibility', 'visible');
                    $('.js-groupsumm').fadeIn('slow');
                } else {
                    $('.js-groupsumm').hide();
                }

                self.lastBounds = boundsstr;

                var within = 0;

                self.collection.each(function(group) {
                    if (bounds.contains(new google.maps.LatLng(group.get('lat'), group.get('lng'))) &&
                        group.get('onmap')) {
                        within++
                    }
                });

                var groupsshown = 0;

                self.collection.each(function(group) {
                    if (bounds.contains(new google.maps.LatLng(group.get('lat'), group.get('lng'))) &&
                        group.get('onmap') && group.get('publish')) {
                        groupsshown++;
                        var latLng = new google.maps.LatLng(group.get('lat'), group.get('lng'));

                        if (within > 20) {
                            // Switch to pins for large collections
                            var icon = window.location.protocol + '//' + window.location.hostname + '/images/mapmarker.gif?a=1';
                            var marker = new google.maps.Marker({
                                position: latLng,
                                icon: icon,
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

                if (groupsshown == 0 && self.map.getZoom() > 0) {
                    // We aren't showing any groups.  Zoom out until we are.
                    self.map.setZoom(self.map.getZoom() - 1);
                }
            }
        },

        resize: function(e) {
            var mapWidth = $(e.target).outerWidth();
            $(e.target).css('height', mapWidth + 'px');
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
                self.updateMap();
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
            if (this.model.get('external')) {
                // External group - open new tab.
                window.open(this.model.get('external'));
            } else if (this.model.get('onyahoo') && !this.model.get('onhere')) {
                // Yahoo group - open new tab.
                window.open("https://groups.yahoo.com/group/" + this.model.get('nameshort'));
            } else {
                Router.navigate('/explore/' + this.model.get('nameshort'), true);
            }
        }
    });

    Iznik.Views.Map.GroupText = Iznik.View.extend({
        template: 'user_explore_grouptext'
    });

    Iznik.Views.User.Pages.ExploreGroup = Iznik.Views.User.Pages.Group.extend({
        template: 'user_explore_single',

        events: {
            'click .js-join': 'join',
            'click .js-leave': 'leave'
        },

        join: function() {
            Router.navigate('/explore/' + this.model.get('nameshort') + '/join', true);
        },

        leave: function() {
            var self = this;

            $.ajax({
                url: API + 'memberships',
                type: 'DELETE',
                data: {
                    groupid: self.model.get('id'),
                    userid: Iznik.Session.get('me').id
                },
                success: function(ret) {
                    if (ret.ret === 0) {
                        // Now force a refresh of the session.
                        self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                            self.model.set('role', 'Non-member');
                            Router.navigate('/explore/' + self.model.get('nameshort'), true);
                        });

                        Iznik.Session.testLoggedIn(true);
                    }
                }
            })    ;
        },

        filter: function(model) {
            var thetype = model.get('type');

            if (thetype != 'Offer' && thetype != 'Wanted') {
                // Not interested in this type of message.
                return(false);
            } else {
                return (model.get('outcomes').length == 0);
            }
        },

        showHideJoin: function() {
            var self = this;

            var id = self.model.get('id');
            self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                if (loggedIn) {
                    var group = Iznik.Session.getGroup(id);

                    if (!_.isUndefined(group)) {
                        self.$('.js-join').hide();
                        self.$('.js-leave').show();
                    } else {
                        self.$('.js-join').show();
                        self.$('.js-leave').hide();
                    }
                } else {
                    self.$('.js-join').show();
                    self.$('.js-leave').hide();
                }
            });

            Iznik.Session.testLoggedIn();
        },

        areaMap: function() {
            var self = this;

            require(['wicket-gmap3', 'wicket'], function(gm, Wkt) {
                // Need to get the polygon, which isn't there by default.
                self.group = new Iznik.Models.Group({
                    id: self.model.get('id')
                });

                self.group.fetch({
                    data: {
                        polygon: true
                    }
                }).then(function() {
                    var wkt = new Wkt.Wkt();
                    var wktstr = self.group.get('polygon');

                    try { // Catch any malformed WKT strings
                        wkt.read(wktstr);
                    } catch (e1) {
                        try {
                            self.Wkt.read(wktstr.replace('\n', '').replace('\r', '').replace('\t', ''));
                        } catch (e2) {
                            if (e2.name === 'WKTError') {
                                console.error("Ignore invalid WKT", wktstr);
                                return;
                            }
                        }
                    }

                    // Try to make the map not increase beyond the height of the description.
                    self.$('#js-areamap').css('height', self.$('.js-nameetc').height());

                    var options = {
                        center: new google.maps.LatLng(self.model.get('lat'), self.model.get('lng')),
                        zoom: 14,
                        disableDefaultUI: true,
                        mapTypeControl: false,
                        mapTypeId: google.maps.MapTypeId.ROADMAP,
                        panControl: false,
                        streetViewControl: false,
                        zoomControl: false
                    };

                    self.areamap = new google.maps.Map(self.$('#js-areamap').get(0), options);

                    var obj = null;

                    // No getBounds on polygon by default.
                    google.maps.Polygon.prototype.getBounds = function() {
                        var bounds = new google.maps.LatLngBounds();
                        var paths = this.getPaths();
                        var path;
                        for (var i = 0; i < paths.getLength(); i++) {
                            path = paths.getAt(i);
                            for (var ii = 0; ii < path.getLength(); ii++) {
                                bounds.extend(path.getAt(ii));
                            }
                        }
                        return bounds;
                    }

                    try {
                        obj = wkt.toObject(self.areamap.defaults); // Make an object
                        obj.setMap(self.areamap);
                        obj.setOptions({
                            fillColor: 'blue',
                            strokeWeight: 0,
                            opacity: 0.5
                        });

                        // Zoom the map to show the whole area.
                        var bounds = obj.getBounds();

                        var mapDim = {
                            height: self.$('#js-areamap').height(),
                            width: self.$('#js-areamap').width()
                        };

                        var zoom = getBoundsZoomLevel(bounds, mapDim);
                        self.areamap.setZoom(zoom);
                    } catch (e) {
                        console.log("WKT error", e.message, wktstr, obj);
                    }
                });
            });
        },

        render: function () {
            var self = this;

            // Create the model.  If the id is a legacy group id then it will be corrected in the model we fetch,
            // so we shouldn't use the options.id after this.
            self.model = new Iznik.Models.Group({ id: self.options.id });
            var p = self.model.fetch({
                data: {
                    polygon: true
                }
            });
            p.then(function() {
                self.title = self.model.get('namedisplay');

                // We want the raw polygon data for structured data.
                var poly = self.model.get('polygon');
                if (poly) {
                    self.model.set('rawpolygon', poly.replace('POLYGON((', '').replace('))', ''));
                }

                Iznik.Views.User.Pages.Group.prototype.render.call(self).then(function () {
                    self.$('.js-membercount').html(self.model.get('membercount').toLocaleString());

                    var founded = self.model.get('founded');
                    if (founded) {
                        var m = new moment(founded);
                        self.$('.js-foundeddate').html(m.format('Do MMMM, YYYY'));
                        self.$('.js-founded').show();
                    }

                    // Add the description.  We use a default because that helps with SEO.
                    var desc = self.model.get('description');
                    desc = desc ? desc : "Give and get stuff for free with " + self.model.get('namedisplay') + ".  Offer things you don't need, and ask for things you'd like.  Don't just recycle - reuse with Freegle!";
                    self.$('.js-description').html(desc);

                    // Any links in here are real.
                    self.$('.js-description a').attr('data-realurl', true);

                    // Add the area map.
                    self.areaMap();

                    self.collection = new Iznik.Collections.Message(null, {
                        modtools: false,
                        collection: 'Approved',
                        groupid: self.model.get('id')
                    });

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.User.Message.Replyable,
                        modelViewOptions: {
                            collection: self.collection,
                            page: self
                        },
                        collection: self.collection,
                        visibleModelsFilter: _.bind(self.filter, self),
                        processKeyEvents: false
                    });

                    self.collectionView.render();

                    // Add a type selector.  The parent class has an event and method to re-render if we change that.
                    self.$('.js-type').selectpicker();
                    self.$('.js-type').selectPersist();

                    // Get some messages
                    self.refetch();

                    if (self.options.join) {
                        var group = Iznik.Session.get(self.model.get('id'));

                        if (group) {
                            // Already a member.
                            self.showHideJoin();
                        } else {
                            $.ajax({
                                url: API + 'memberships',
                                type: 'PUT',
                                data: {
                                    groupid: self.model.get('id')
                                }, complete: function () {
                                    self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                                        self.showHideJoin();
                                        self.refetch();
                                    });

                                    Iznik.Session.testLoggedIn(true);
                                }
                            });
                        }
                    } else {
                        self.showHideJoin();
                    }
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.LegacyMessage = Iznik.Views.Page.extend({
        template: 'user_explore_message',

        render: function () {
            var self = this;
            var p = Iznik.Views.Page.prototype.render.call(self).then(function () {
                self.model = new Iznik.Models.Message({
                    id: 'L' + self.options.id
                });
                self.model.fetch({
                    data: {
                        groupid: self.options.groupid
                    },
                    processData: true
                }).then(function () {
                    // We might fail to fetch, or fetch a deleted message, or fetch a paired message.  In all these
                    // cases the message shouldn't show.
                    if (self.model.get('subject') && !self.model.get('deleted')) {
                        var v = new Iznik.Views.User.Message.Replyable({
                            model: self.model
                        });

                        v.expanded = true;

                        v.render().then(function () {
                            self.$('.js-message').append(v.el);
                            v.expand();
                        });
                    } else {
                        self.$('.js-gone').fadeIn('slow');
                    }
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.Message = Iznik.Views.Page.extend({
        template: 'user_explore_message',

        events: {
            'click .js-join': 'join'
        },

        join: function() {
            var self = this;

            $.ajax({
                url: API + 'memberships',
                type: 'PUT',
                data: {
                    groupid: self.groupid
                }, complete: function () {
                    // Refresh our group list and re-render.
                    self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                        self.render();
                    });

                    Iznik.Session.testLoggedIn(true);
                }
            });
        },

        render: function () {
            var self = this;
            var p = Iznik.Views.Page.prototype.render.call(self).then(function () {
                self.model = new Iznik.Models.Message({
                    id: self.options.id
                });
                self.model.fetch().then(function () {
                    var ret = self.model.get('ret');

                    if (ret == 2) {
                        // Permission denied.  This means it is a message which we are not allowed to see.
                        //
                        // Most commonly this is because we're logged out, and need to log in before
                        // we're allowed to see it.
                        self.listenToOnce(Iznik.Session, 'isLoggedIn', function(loggedIn) {
                            if (!loggedIn) {
                                // We are logged out, and the contents are not visible to non-group members.
                                self.listenToOnce(Iznik.Session, 'loggedIn', function () {
                                    self.render();
                                });

                                Iznik.Session.forceLogin({
                                    modtools: false
                                });
                            } else {
                                // Still can't see it logged in - need to join the group
                                var groups = self.model.get('groups');
                                _.each(groups, function(group) {
                                    var name = group.namedisplay;
                                    self.groupid = group.id;
                                    self.$('.js-groupname').html(name);
                                    self.$('.js-needtojoin').fadeIn('slow');
                                });
                            }
                        });

                        Iznik.Session.testLoggedIn();

                        self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                            var page = new Iznik.Views.ModTools.Pages.Replay({
                                sessionid: sessionid
                            });
                            self.loadRoute({page: page, modtools: true});
                        });

                    } else if (self.model.get('subject') &&
                        !self.model.get('deleted') &&
                        (!self.model.get('outcomes') || self.model.get('outcomes').length == 0)
                    ) {
                        // We might fail to fetch, or fetch a deleted message, or fetch a completed message.  In all these
                        // cases the message shouldn't show.
                        var v = new Iznik.Views.User.Message.Replyable({
                            model: self.model
                        });

                        v.expanded = true;
                        v.render().then(function () {
                            self.$('.js-message').append(v.el);

                            var group = self.model.get('groups')[0];
                            self.$('.js-moregroup').html(group.namedisplay);
                            self.$('.js-groupurl').attr('href', '/explore/' + group.nameshort);
                            self.$('.js-more').show();
                        });
                    } else {
                        self.$('.js-gone').fadeIn('slow');
                    }
                });
            });

            return (p);
        }
    });
});

