define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/selectpersist',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/pages/user/post',
    'iznik/views/group/select',
    'iznik/views/user/message'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.MyGroups = Iznik.Views.Infinite.extend({
        template: "user_mygroups_main",

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
                    // Only show an offer which has not been taken or wanted not received.
                    var paired = _.where(model.get('related'), {
                        type: thetype == 'Offer' ? 'Taken' : 'Received'
                    });

                    return (paired.length == 0);
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
        },

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
                        visibleModelsFilter: _.bind(self.filter, self)
                    });

                    self.collectionView.render();

                    // Add a group selector and re-render if we change it.  The selected function is called during
                    // render which will trigger the initial view.
                    var v = new Iznik.Views.Group.Select({
                        systemWide: false,
                        all: true,
                        mod: false,
                        id: 'myGroupsSelect'
                    });

                    self.listenTo(v, 'selected', function(selected) {
                        self.selected = selected;
                        self.refetch();
                    });

                    // Add a type selector and re-render if we change that.
                    self.$('.js-type').selectpicker();
                    self.$('.js-type').selectPersist();

                    // Render after the listen to as they are called during render.
                    v.render().then(function(v) {
                        self.$('.js-groupselect').html(v.el);
                    });
                }
            });

            return (p);
        }
    });
});