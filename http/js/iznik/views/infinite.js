define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.Infinite = Iznik.Views.Page.extend({
        context: null,
        fetching: null,
        fetchData: {},
        fetchPromise: null,
        listening: false,
        wait: null,

        fetch: function (data) {
            var self = this;

            if (self.fetchPromise && self.fetching == self.selected) {
                // We are already fetching what we need to be.
                // console.log("Already fetching");
                return(self.fetchPromise);
            } else {
                // console.log("Not already fetching");
                self.fetching = self.selected;
                self.fetchPromise = new Promise(function(resolve, reject) {
                    if (data) {
                        // We were passed some data to use on the fetch.  Save it for future fetches when we need to
                        // scroll.
                        self.fetchData = data;
                        // console.log("Infinite fetch data passed", data);
                    } else {
                        // We weren't - but we might have some saved.
                        data = self.fetchData ? self.fetchData : {};
                        // console.log("Infinite fetch data from saved", data);
                    }

                    self.$('.js-loading').removeClass('hidden');
                    self.$('.js-none').hide();

                    data.context = self.context;

                    if (!self.context) {
                        // We're at the top.
                        $('.js-scrolltop').addClass('hidden');
                    }

                    if (!self.wait) {
                        self.wait = new Iznik.Views.PleaseWait({
                            timeout: 30000,
                            label: "infinite fetch"
                        });

                        self.wait.render();
                    }

                    if (!self.listening) {
                        self.collectionView.on('add', function (modelView) {
                            self.$('.js-none').hide();
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
                    // console.log("Fetch vs", self.selected, self.lastFetched, self.selected != self.lastFetched, data);
                    self.collection.fetch({
                        data: data,
                        remove: self.selected != self.lastFetched,
                        success: function (collection, response, options) {
                            // console.log("Check response", self.retField, response);
                            if (response.hasOwnProperty(self.retField) && response[self.retField].length > 0) {
                                // We want find last one, so that we can tell when we've scrolled to it.  We might
                                // be using visibleModelsFilter, so we need to watch for that class.
                                // console.log("Look for last", self.collectionView);
                                var last = self.collectionView.$el.find("li:not('.not-visible'):last");
                                // console.log("Last visible", last);

                                if (last.length > 0) {
                                    // Waypoints allow us to see when we have scrolled to the bottom.
                                    if (self.lastWaypoint) {
                                        // console.log("Destroy last");
                                        self.lastWaypoint.destroy();
                                    }

                                    // console.log("Set up waypoint for", last.get(0));
                                    self.lastWaypoint = new Waypoint({
                                        element: last.get(0),
                                        handler: function (direction) {
                                            if (direction == 'down') {
                                                if (self.collection.length > 3) {
                                                    $('.js-scrolltop').removeClass('hidden');
                                                    $('.js-scrolltop').click(function () {
                                                        $('html,body').animate({scrollTop: 0}, 'slow', function () {
                                                            $('.js-scrolltop').addClass('hidden');
                                                        });
                                                    });
                                                }

                                                // We have scrolled to the last view.  Fetch more as long as we've not switched
                                                // away to another page.
                                                // console.log("Scrolled to last", last.closest('body').length);
                                                if (last.closest('body').length > 0) {
                                                    // console.log("Still in DOM, fetch");
                                                    self.fetch();
                                                }
                                            }
                                        },
                                        offset: '99%' // Fire as soon as self view becomes visible
                                    });
                                } else {
                                    // We were returned some values, but it looks like we've filtered them all out.
                                    // So fetch the next lot.
                                    self.fetchPromise = null;
                                    self.fetch();
                                }
                            }

                            // console.log("Fetched");
                            if (self.wait) {
                                self.wait.close();
                                self.wait = null;
                            }

                            self.$('.js-loading').addClass('hidden');
                            self.fetching = null;
                            self.fetchPromise = null;
                            self.lastFetched = self.selected;
                            self.context = response.context;

                            // console.log("Fetched length", collection.length);
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