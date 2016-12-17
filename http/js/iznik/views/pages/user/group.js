define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/selectpersist',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/pages/user/post',
    'iznik/views/infinite',
    'iznik/views/group/select',
    'iznik/views/user/message'
], function($, _, Backbone, Iznik) {
    // This is abstract - never instantiated, only extended.  So it has no substantive render.
    Iznik.Views.User.Pages.Group = Iznik.Views.Infinite.extend({
        events: {
            'change .js-type': 'changeType'
        },

        retField: 'messages',

        selected: null,

        changeType: function() {
            // Re-render the collection view, which will invoke the filter and hence pick up the changed value.
            this.collectionView.render();

            // Fetch another chunk of messages in case what we are now showing is too short.
            this.fetch();
        },

        filter: function(model) {
            var thetype = model.get('type');
            var filttype = this.$('.js-type').val();

            if (thetype != 'Offer' && thetype != 'Wanted') {
                // Not interested in this type of message.
                return(false);
            } else {
                if (filttype == 'All' || filttype == thetype) {
                    // Only show a search result for active posts.
                    return (model.get('outcomes').length == 0);
                } else {
                    // This type is filtered out
                    return(false);
                }
            }
        },

        refetch: function() {
            var self = this;
            self.context = null;

            var data = {
                remove: true
            };

            if (self.selected > 0) {
                data.groupid = self.selected
            }

            self.fetch(data).then(function () {
                var some = false;

                self.collection.each(function(msg) {
                    // Get the zoom level for maps and put it somewhere easier.
                    var zoom = 8;
                    var groups = msg.get('groups');
                    if (groups.length > 0 && groups[0].settings.map.hasOwnProperty('zoom')) {
                        zoom = groups[0].settings.map.zoom;
                    }
                    msg.set('zoom', zoom);
                    var related = msg.get('related');

                    var taken = _.where(related, {
                        type: 'Taken'
                    });

                    if (taken.length == 0) {
                        some = true;
                    }
                });

                if (!some) {
                    self.$('.js-none').fadeIn('slow');
                } else {
                    self.$('.js-none').hide();
                }
            });
        },

        render: function () {
            return(Iznik.Views.Infinite.prototype.render.call(this));
        }
    });
});