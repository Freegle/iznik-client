define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    "iznik/models/volunteering",
    'iznik/views/pages/pages',
    'iznik/views/infinite',
    'iznik/views/group/select',
    'iznik/views/group/volunteering'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.PendingVolunteering = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_volunteering_main",

        retField: 'volunteerings',

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
                    counts: ['pendingvolunteering'],
                    id: 'volunteeringGroupSelect'
                });

                self.listenTo(self.groupSelect, 'selected', function (selected) {
                    // Change the group selected.
                    self.selected = selected;

                    // We haven't fetched anything for this group yet.
                    self.lastFetched = null;
                    self.context = null;

                    self.collection = new Iznik.Collections.Volunteering(null, {
                        groupid: self.selected,
                        group: Iznik.Session.get('groups').get(self.selected)
                    });

                    // CollectionView handles adding/removing/sorting for us.
                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.ModTools.Volunteering,
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
                self.listenTo(Iznik.Session, 'pendingvolunteeringcountschanged', _.bind(self.countsChanged, self));
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Volunteering = Iznik.Views.User.Volunteering.Editable.extend({
        tagName: 'li',
        template: 'modtools_volunteering_pending',
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