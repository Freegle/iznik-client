define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/pages/user/post',
    'iznik/views/user/message'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.MyGroups = Iznik.Views.Infinite.extend({
        template: "user_mygroups_main",

        retField: 'messages',

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);

            p.then(function(self) {
                var mygroups = Iznik.Session.get('groups');

                if (mygroups && mygroups.length > 0) {
                    self.$('.js-browse').show();

                    self.collection = new Iznik.Collections.Message(null, {
                        modtools: false,
                        collection: 'Approved'
                    });

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.User.Message.Replyable,
                        modelViewOptions: {
                            collection: self.collection,
                            page: self
                        },
                        collection: self.collection,
                        visibleModelsFilter: function(model) {
                            var thetype = model.get('type');

                            if (thetype != 'Offer' && thetype != 'Wanted') {
                                // Not interested in this type of message.
                                return(false);
                            } else {
                                // Only show an offer which has not been taken or wanted not received.
                                var paired = _.where(model.get('related'), {
                                    type: thetype == 'Offer' ? 'Taken' : 'Received'
                                });

                                return (paired.length == 0);
                            }
                        }
                    });

                    self.collectionView.render();

                    self.fetch({
                        remove: true
                    }).then(function () {
                        var some = false;

                        self.collection.each(function(msg) {
                            // Get the zoom level for maps and put it somewhere easier.
                            var zoom = 8;
                            var groups = msg.get('groups');
                            if (groups.length > 0) {
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
                }
            });

            return (p);
        }
    });
});