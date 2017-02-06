define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/views/pages/pages',
    'iznik/models/activity',
    'gmaps',
    'richMarker',
    'jquery.geocomplete'
], function ($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Pages.LiveMap = Iznik.Views.Page.extend({
        template: 'user_livemap_main',

        title: 'Live Map',

        markers: [],

        queue: [],

        fetching: false,

        render: function () {
            var self = this;

            self.activeMessages = new Iznik.Collections.Activity.RecentMessages();

            self.activeMessages.on('add', function(activity) {
                self.queue.push(activity.attributes);
            });

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                // Just centre on one of the centres of Britain.  Yes, there are multiple.
                self.markers = [];

                // Note target might be outside this view.
                var target = $('.js-maparea');
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
                    zoomControl         : mapWidth > 400,
                    zoom                : 5
                };

                self.map = new google.maps.Map(target.get()[0], mapOptions);

                // Render the map
                google.maps.event.addDomListener(self.map, 'idle', function() {
                    self.activeMessages.fetch().then(self.checkAdd());
                });
            });
            return (p);
        },

        checkAdd: function() {
            var self = this;
            console.log("Queue len", self.queue.length);
            if (self.queue.length > 0) {
                if (self.queue[0].message.delta > 0) {
                    // Not ready yet.
                    console.log("Not ready", self.queue[0].message.delta);
                    self.queue[0].message.delta--;
                } else {
                    // Ready to show.
                    var activity = self.queue.shift();
                    var msg = activity.message;
                    var group = activity.group;

                    var marker = new RichMarker({
                        position: new google.maps.LatLng(group.lat, group.lng),
                        map: self.map
                    });

                    var content = new Iznik.Views.User.Pages.LiveMap.Message({
                        model: new Iznik.Model(activity)    ,
                        map: self.map,
                        marker: marker
                    });

                    // Show the message as a tooltip below.
                    content.render().then(function() {
                        var subj = content.model.get('message').subject;
                        if (subj.charAt(0) === '[') {
                            // Remove group tag.
                            subj = subj.substring(subj.indexOf(']') + 1).trim();
                        }
                        content.$el.tooltip({
                            'trigger': 'manual',
                            'placement': 'bottom',
                            'title': subj
                        });

                        marker.setContent(content.el);
                        content.$el.tooltip('show');

                        // Clear after a while.
                        _.delay(_.bind(function() {
                            this.setMap(null);
                            console.log("Clear marker", this);
                        }, marker), 300000);
                    });

                    marker.setFlat(true);
                }
            }

            if (self.queue.length <= 2 && !self.fetching) {
                // Get more.
                self.fetching = true;
                console.log("Fetch more");
                self.activeMessages.fetch({
                    remove: true
                }).then(function() {
                    console.log("Fetched more");
                    self.fetching = false;
                })
            }

            _.delay(_.bind(self.checkAdd, self), 1000);
        }
    });

    Iznik.Views.User.Pages.LiveMap.Message = Iznik.View.extend({
        template: 'user_livemap_message'
    });
});
