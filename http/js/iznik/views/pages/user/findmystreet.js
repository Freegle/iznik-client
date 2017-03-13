define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/facebook',
    'moment',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/models/membership',
    'iznik/models/group',
    'gmaps',
    'richMarker'
], function($, _, Backbone, Iznik, FBLoad, moment) {
    Iznik.Views.User.Pages.FindMyStreet = Iznik.Views.User.Pages.WhereAmI.extend({
        template: "user_findmystreet_main",

        events: {
            'click .js-findme': 'streetwhack',
            'click .js-click': 'gotoGroup',
            'keyup .js-postcode': 'myKeyUp',
            'click .js-sharefb': 'shareFB'
        },

        shareFB: function() {
            var self = this;
            var params = {
                method: 'share',
                href: window.location.protocol + '//' + window.location.host + '/streetwhack/' + self.count + '?src=streetwhack',
            };

            FB.ui(params, function (response) {
                self.$('.js-fbshare').fadeOut('slow');
            });
        },

        click: function() {
            window.open(self.group.get('url'));
        },

        myKeyUp: function() {
            this.$('js-findme, .js-result').hide();
            this.$('.js-map, .js-group').empty();
        },

        savelocation: function(location) {
            var self = this;
            self.location = location;
            self.$('.js-findme').fadeIn('slow');
        },

        streetwhack: function() {
            var self = this;

            this.$('js-findme, .js-result').hide();
            this.$('.js-map, .js-group').empty();
            self.$('.js-loader').show();

            $.ajax({
                url: API + 'locations',
                type: 'GET',
                data: {
                    findmystreet: self.location.id
                }, success: function(ret) {
                    self.$('.js-loader').hide();
                    self.$('.js-beforeresult').slideUp('slow');

                    self.$('.js-streetname').html(ret.streets[0].name);
                    self.$('.js-namewrapper').fadeIn('slow');

                    var count = ret.streets.length;
                    self.$('.js-streetcount').html(count);
                    self.count = count;

                    if (count == 1) {
                        self.$('.js-just1').fadeIn('slow');
                    } else if (count <= 10) {
                        self.$('.js-1to10').fadeIn('slow');
                    } else if (count <= 100) {
                        self.$('.js-10to100').fadeIn('slow');
                    } else {
                        self.$('.js-morethan100').fadeIn('slow');
                    }

                    var target = self.$('.js-map');
                    var mapWidth = target.outerWidth();
                    target.css('height', mapWidth + 'px');

                    // Set explicit dimensions otherwise map collapses.
                    target.css('width', target.width());
                    var height =  target.width();
                    height = height < 200 ? 200 : height;
                    target.css('height', height);

                    // Create map centred on the specified place.
                    var mapOptions = {
                        mapTypeControl      : false,
                        streetViewControl   : false,
                        center              : new google.maps.LatLng(53.9450, -2.5209),
                        panControl          : mapWidth > 400,
                        zoomControl         : true,
                        zoomControlOptions: {
                            position: google.maps.ControlPosition.LEFT_TOP
                        },
                        zoom                : 5
                    };

                    self.map = new google.maps.Map(target.get()[0], mapOptions);

                    // Add them
                    _.each(ret.streets, function(street) {
                        self.marker = new RichMarker({
                            position: new google.maps.LatLng(street.lat, street.lng)
                        });

                        self.marker.setContent('<img title="You!" src="http://maps.google.com/mapfiles/kml/pal3/icon48.png" />');
                        self.marker.setDraggable(false);
                        self.marker.setMap(self.map);
                        self.marker.setFlat(true);
                    });

                    // Show the nearest group.
                    if (self.groupsnear && self.groupsnear.length > 0) {
                        self.group = new Iznik.Models.Group({
                            id: self.groupsnear[0].id
                        });
                        self.group.fetch().then(function() {
                            var v = new Iznik.Views.User.Pages.FindMyStreet.Group({
                                model: self.group
                            });

                            v.render().then(function() {
                                self.$('.js-membercount').html(self.group.get('membercount').toLocaleString());
                                self.$('.js-group').html(v.$el);

                                var founded = self.group.get('founded');
                                if (founded) {
                                    var m = new moment(founded);
                                    self.$('.js-foundeddate').html(m.format('Do MMMM, YYYY'));
                                    self.$('.js-founded').show();
                                }

                                // Add the description.  We use a default because that helps with SEO.
                                var desc = self.group.get('description');
                                desc = desc ? desc : "Give and get stuff for free with " + self.group.get('namedisplay') + ".  Offer things you don't need, and ask for things you'd like.  Don't just recycle - reuse with Freegle!";
                                self.$('.js-description').html(desc);

                                // Any links in here are real.
                                self.$('.js-description a').attr('data-realurl', true);

                                self.delegateEvents();
                            });
                        });
                    }
                }
            })
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.User.Pages.WhereAmI.prototype.render.call(this);

            p.then(function(self) {
                self.listenTo(self, 'gotlocation', _.bind(self.savelocation, self));

                FBLoad().render();
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.FindMyStreet.Group = Iznik.View.extend({
        template: 'user_findmystreet_group'
    });
});