define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    "iznik/models/social",
    'iznik/views/pages/pages',
    'iznik/views/infinite'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.SocialActions = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_socialactions_main",

        retField: 'socialactions',

        countsChanged: function() {
            this.render();
        },

        render: function () {
            var self = this;
            var p = Iznik.Views.Infinite.prototype.render.call(this);

            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'modtools_socialactions_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                })

                self.lastFetched = null;
                self.context = null;

                self.collection = new Iznik.Collections.SocialActions();

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.SocialAction,
                    collection: self.collection
                });

                self.collectionView.render();
                self.fetch();

                // If we detect that the pending counts have changed on the server, refetch the members so that we add/remove
                // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
                self.listenTo(Iznik.Session, 'socialactionscountschanged', _.bind(self.countsChanged, self));
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.SocialAction = Iznik.View.extend({
        template: 'modtools_socialactions_one',

        render: function() {
            var self = this;
            console.log("Render action", self);
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                // Show buttons for the remaining groups that haven't shared this.
                self.$('.js-buttons').empty();
                _.each(self.model.get('groups'), function(groupid) {
                    var group = Iznik.Session.getGroup(groupid);

                    if (group.get('type') == 'Freegle') {
                        var v = new Iznik.Views.ModTools.SocialAction.FacebookShare({
                            model: group
                        });

                        v.render().then(function() {
                            self.$('.js-buttons').append(v.$el);
                        });
                    }
                });
            });

            return(this);
        }
    });

    Iznik.Views.ModTools.SocialAction.FacebookShare = Iznik.View.extend({
        template: 'modtools_socialactions_facebookshare',

        tagName: 'li'
    });
});