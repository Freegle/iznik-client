Iznik.Views.Infinite = IznikView.extend({
    context: null,
    fetching: null,

    fetch: function() {
        var self = this;

        self.$('.js-none').hide();

        var data = {
            context: self.context
        };

        if (self.selected > 0) {
            // Specific group
            data.groupid = self.selected;
        }

        // Fetch more - and leave the old ones in the collection
        if (self.fetching == self.selected) {
            // Already fetching the right group.
            return;
        } else {
            self.fetching = self.selected;
        }

        var v = new Iznik.Views.PleaseWait();
        v.render();

        self.collectionView.on('add', function(modelView) {
            var pos = modelView.collection.indexOf(modelView.model);

            if (pos + 1 == modelView.collection.length) {
                // This is the last one.

                // Waypoints allow us to see when we have scrolled to the bottom.
                if (self.lastWaypoint) {
                    self.lastWaypoint.destroy();
                }

                self.lastWaypoint = new Waypoint({
                    element: modelView.el,
                    handler: function(direction) {
                        if (direction == 'down') {
                            // We have scrolled to the last view.  Fetch more as long as we've not switched
                            // away to another page.
                            if (jQuery.contains(document.documentElement, modelView.el)) {
                                self.fetch();
                            }
                        }
                    },
                    offset: '99%' // Fire as soon as this view becomes visible
                });
            }
        });

        self.collectionView.on('remove', function(modelView) {
            if (modelView.collection.length == 0) {
                self.$('.js-none').fadeIn('slow');
            }
        });

        this.collection.fetch({
            data: data,
            remove: self.selected != self.lastFetched,
            success: function(collection, response, options) {
                v.close();

                self.fetching = null;
                self.lastFetched = self.selected;
                self.context = response.context;

                if (collection.length == 0) {
                    self.$('.js-none').fadeIn('slow');
                }
            }
        });
    }
});
