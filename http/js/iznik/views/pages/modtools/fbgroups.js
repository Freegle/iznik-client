define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    'iznik/views/pages/pages',
    "iznik/views/pages/modtools/messages",
    'iznik/views/infinite'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.FacebookGroups = Iznik.Views.Infinite.extend({
        modtools: true,

        retField: 'messages',

        template: "modtools_fbgroups_main",

        events: {
            'click .js-searchbtn': 'search',
            'click .js-all': 'selectAll',
            'click .js-share': 'share'
        },

        search: function() {
            var self = this;
            var search = self.$('.js-search').val();
            if (search.length > 0) {
                FB.login(function() {
                    FB.api('/search', {q: search, type: 'group'}, function (response) {
                        console.log("Search returned", response);
                        var groups = response.data;

                        if (groups && groups.length > 0) {
                            _.each(groups, function(group) {
                                var mod = new Iznik.Model({
                                    id: group.id,
                                    name: group.name
                                });

                                var v = new Iznik.Views.ModTools.Message.FacebookGroup({
                                    model: mod
                                });

                                v.render().then(function() {
                                    self.$('.js-groups').append(v.$el);
                                });
                            })
                        }
                    });
                });
            }
        },

        selectAll: function() {
            if (this.$('.js-all').is(':checked')) {
                this.$('.js-msgselect').prop('checked', true);
            } else {
                this.$('.js-msgselect').prop('checked', false);
            }
        },

        share: function() {
            var self = this;

            // Get the selected messages
            var msgs = [];
            self.$('.js-msgselect').each(function() {
                if (!$(this).closest('li').hasClass('not-visible') && $(this).is(':checked')) {
                    msgs.push($(this).data('msgid'));
                }
            });

            // Get the Facebook groups.
            var fbgroups = [];
            self.$('.js-groupid').each(function() {
                fbgroups.push($(this).data('groupid'));
            });

            FB.login(function(){
                _.each(fbgroups, function(fbgroup) {
                    _.each(msgs, function(id) {
                        var msg = self.collection.get(id);
                        FB.api('/' + fbgroup + '/feed', 'post', {
                            link: 'https://www.ilovefreegle.org/message/' + id + '?src=fbgroup',
                            description: 'Please click to view and reply - no PMs please.  Everything on Freegle is completely free.'
                        }, function(response) {
                            console.log("Share returned", response);
                            if (response.hasOwnProperty('error')) {
                                console.log("Error", self.$('.js-error'));
                                self.$('.js-error').html(response.error);
                                self.$el.show();
                            } else {
                                console.log("Success");
                                self.$el.fadeOut('slow');
                            }
                        });
                    });
                });
            }, {
                scope: 'user_managed_groups, publish_actions'
            });
        },

        filter: function(model) {
            var thetype = model.get('type');

            if (thetype != 'Offer' && thetype != 'Wanted' || model.get('source') != 'Platform') {
                // Not interested in this type of message.
                return(false);
            } else {
                // Only show a search result for active posts.
                return (model.get('outcomes').length == 0);
            }
        },

        render: function () {
            var self = this;
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                self.collection = new Iznik.Collections.Message(null, {
                    modtools: true,
                    groupid: self.selected,
                    group: Iznik.Session.get('groups').get(self.selected),
                    collection: 'Approved'
                });

                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: true,
                    mod: true,
                    id: 'approvedGroupSelect'
                });

                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.Message.FacebookSummary,
                    modelViewOptions: {
                        collection: self.collection,
                        page: self
                    },
                    collection: self.collection,
                    visibleModelsFilter: _.bind(self.filter, self),
                    processKeyEvents: false
                });

                self.collectionView.render();

                self.listenTo(self.groupSelect, 'selected', function (selected) {
                    // Change the group selected.
                    self.selected = selected;

                    // We haven't fetched anything for this group yet.
                    self.lastFetched = null;
                    self.context = null;

                    self.fetch({
                        groupid: self.selected > 0 ? self.selected : null
                    });
                });

                // Render after the listen to as they are called during render.
                self.groupSelect.render().then(function(v) {
                    self.$('.js-groupselect').html(v.el);
                });

                require(['iznik/facebook'], function(FBLoad) {
                    self.listenToOnce(FBLoad(), 'fbloaded', function () {
                        if (!FBLoad().isDisabled()) {
                            self.$('.js-share').show();
                        }
                    });

                    FBLoad().render();
                });
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Message.FacebookSummary = Iznik.View.Timeago.extend({
        template: 'modtools_fbgroups_summary'
    });

    Iznik.Views.ModTools.Message.FacebookGroup = Iznik.View.extend({
        tagName: 'li',

        template: 'modtools_fbgroups_group',

        events: {
            'click .js-delete': 'zap'
        },

        zap: function() {
            this.$el.remove();
        }
    });
});