define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    "iznik/models/communityevent",
    'iznik/views/pages/pages',
    'iznik/views/infinite',
    'iznik/views/group/select',
    'iznik/views/group/communityevents',
    'iznik/views/pages/user/communityevents'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.PendingEvents = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_communityevents_main",

        retField: 'communityevents',

        countsChanged: function() {
            this.groupSelect.render();
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: true,
                    mod: true,
                    counts: ['pendingevents'],
                    id: 'eventsGroupSelect'
                });

                self.listenTo(self.groupSelect, 'selected', function (selected) {
                    // Change the group selected.
                    self.selected = selected;

                    // We haven't fetched anything for this group yet.
                    self.lastFetched = null;
                    self.context = null;

                    self.collection = new Iznik.Collections.CommunityEvent(null, {
                        groupid: self.selected,
                        group: Iznik.Session.get('groups').get(self.selected)
                    });

                    // CollectionView handles adding/removing/sorting for us.
                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.ModTools.CommunityEvent,
                        modelViewOptions: {
                            collection: self.collection,
                            page: self
                        },
                        collection: self.collection,
                        processKeyEvents: false
                    });

                    self.collectionView.render();

                    self.fetch({
                        groupid: self.selected > 0 ? self.selected : null,
                        pending: true
                    });
                });

                // Render after the listen to as they are called during render.
                self.groupSelect.render().then(function(v) {
                    self.$('.js-groupselect').html(v.el);
                });

                // If we detect that the pending counts have changed on the server, refetch the members so that we add/remove
                // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
                self.listenTo(Iznik.Session, 'pendingeventscountschanged', _.bind(self.countsChanged, self));
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.CommunityEvent = Iznik.Views.User.CommunityEvent.Editable.extend({
        tagName: 'li',
        template: 'modtools_communityevents_pending',
        parentClass: Iznik.View,
        closeAfterSave: false,

        events: {
            'click .js-save': 'save',
            'click .js-approve': 'approve',
            'click .js-delete': 'deleteMe'
        },

        approve: function() {
            var self = this;
            self.model.save({
                'id': self.model.get('id'),
                'pending': false
            }, {
                patch: true
            });

            self.$el.fadeOut('slow');
        },

        deleteMe: function() {
            var self = this;

            var v = new Iznik.Views.Confirm();

            self.listenToOnce(v, 'confirmed', function() {
                self.model.destroy();
            });

            v.render();
        }
    });
});