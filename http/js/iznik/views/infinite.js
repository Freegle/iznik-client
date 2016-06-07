define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Views.Infinite = Iznik.View.extend({
        context: null,
        fetching: null,
        fetchData: {},
        fetchPromise: null,
        listening: false,

        fetch: function (data) {
            var self = this;

            if (self.fetchPromise && self.fetching == self.selected) {
                // We are already fetching what we need to be.
                console.log("Already fetching");
                return(self.fetchPromise);
            } else {
                console.log("Not already fetching");
                self.fetching = self.selected;
                self.fetchPromise = new Promise(function(resolve, reject) {
                    if (data) {
                        // We were passed some data to use on the fetch.  Save it for future fetches when we need to
                        // scroll.
                        self.fetchData = data;
                    } else {
                        // We weren't - but we might have some saved.
                        data = self.fetchData ? self.fetchData : {};
                    }

                    self.$('.js-loading').removeClass('hidden');
                    self.$('.js-none').hide();

                    data.context = self.context;

                    if (!self.context) {
                        // We're at the top.
                        $('.js-scrolltop').addClass('hidden');
                    }

                    var v = new Iznik.Views.PleaseWait();
                    v.render().then(function() {
                        if (!self.listening) {
                            self.collectionView.on('add', function (modelView) {
                                self.$('.js-none').hide();

                                var pos = modelView.collection.indexOf(modelView.model);
                                console.log("Added", pos, modelView.collection.length);

                                if (pos + 1 == modelView.collection.length) {
                                    // self is the last one.
                                    console.log("Last one", modelView.el, jQuery.contains(document.documentElement, modelView.el));

                                    // Waypoints allow us to see when we have scrolled to the bottom.
                                    if (self.lastWaypoint) {
                                        console.log("Destroy last");
                                        self.lastWaypoint.destroy();
                                    }

                                    self.lastWaypoint = new Waypoint({
                                        element: modelView.el,
                                        handler: function (direction) {
                                            if (direction == 'down') {
                                                if (modelView.collection.length > 3) {
                                                    $('.js-scrolltop').removeClass('hidden');
                                                    $('.js-scrolltop').click(function () {
                                                        $('html,body').animate({scrollTop: 0}, 'slow', function () {
                                                            $('.js-scrolltop').addClass('hidden');
                                                        });
                                                    });
                                                }

                                                // We have scrolled to the last view.  Fetch more as long as we've not switched
                                                // away to another page.
                                                if (jQuery.contains(document.documentElement, modelView.el)) {
                                                    console.log("Scrolled to last, fetch");
                                                    self.fetch();
                                                }
                                            }
                                        },
                                        offset: '99%' // Fire as soon as self view becomes visible
                                    });
                                }
                            });

                            self.collectionView.on('remove', function () {
                                if (self.collectionView.collection.length == 0) {
                                    self.$('.js-none').fadeIn('slow');
                                    $('.js-scrolltop').addClass('hidden');

                                    // console.log("Consider waypoint remove", self.lastWaypoint);
                                    if (self.lastWaypoint) {
                                        // console.log("Remove waypoint");
                                        self.lastWaypoint.destroy();
                                    }
                                }
                            });

                            self.listening = true;
                        }

                        // Fetch more - and leave the old ones in the collection unless we're fetching another group.
                        // console.log("Fetch vs", self.selected, self.lastFetched);
                        self.collection.fetch({
                            data: data,
                            remove: self.selected != self.lastFetched,
                            success: function (collection, response, options) {
                                v.close();

                                self.$('.js-loading').addClass('hidden');
                                self.fetching = null;
                                self.fetchPromise = null;
                                self.lastFetched = self.selected;
                                self.context = response.context;

                                //console.log("Fetched length", collection.length);
                                if (collection.length == 0) {
                                    self.$('.js-none').fadeIn('slow');
                                } else {
                                    self.$('.js-none').hide();
                                }

                                // console.log("Fetched");
                                resolve();
                            }, 
                            error: function() {
                                console.log("Fetch error");
                                self.fetchPromise = null;
                                reject();
                            }
                        });
                    });
                });
            }

            return(self.fetchPromise);
        }
    });

    $(document).ready(function () {
        // We add informatin dynamically after the render, and this messes up waypoints, so we need to regularly
        // tell them to sort themselves out.
        window.setInterval(Waypoint.refreshAll, 1000);
    });
});