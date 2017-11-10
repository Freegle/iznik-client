define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    'iznik/views/pages/pages',
    'iznik/views/pages/modtools/messages',
    'iznik/views/pages/modtools/members_approved',
    'iznik/views/infinite',
    'iznik/views/group/select'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.StoriesMembers = Iznik.Views.Page.extend({
        modtools: true,

        template: "modtools_members_stories_main",

        retField: 'stories',

        countsChanged: function() {
            this.groupSelect.render();
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = self.options.newsletter ? 'modtools_members_stories_newsletterhelp' : 'modtools_members_stories_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                });

                if (!self.options.newsletter) {
                    self.groupSelect = new Iznik.Views.Group.Select({
                        systemWide: false,
                        all: true,
                        mod: true,
                        counts: ['stories'],
                        id: 'StoriesGroupSelect'
                    });

                    self.listenTo(self.groupSelect, 'selected', function (selected) {
                        // Change the group selected.
                        self.selected = selected;

                        // We haven't fetched anything yet.
                        self.lastFetched = null;
                        self.context = null;

                        self.collection = new Iznik.Collections.Members.Stories(null, {
                            groupid: self.selected > 0 ? self.selected : null
                        });

                        // CollectionView handles adding/removing/sorting for us.
                        self.collectionView = new Backbone.CollectionView({
                            el: self.$('.js-list'),
                            modelView: Iznik.Views.ModTools.Member.Story,
                            modelViewOptions: {
                                collection: self.collection,
                                page: self,
                                newsletter: self.options.newsletter
                            },
                            collection: self.collection,
                            processKeyEvents: false
                        });

                        self.collectionView.render();

                        self.collection.fetch({
                            data: {
                                groupid: self.selected > 0 ? self.selected : null,
                                reviewed: 0
                            }
                        });
                    });

                    // Render after the listen to as they are called during render.
                    self.groupSelect.render().then(function (v) {
                        self.$('.js-groupselect').html(v.el);
                    });
                } else {
                    self.collection = new Iznik.Collections.Members.Stories();

                    // CollectionView handles adding/removing/sorting for us.
                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.ModTools.Member.Story,
                        modelViewOptions: {
                            collection: self.collection,
                            page: self,
                            newsletter: self.options.newsletter
                        },
                        collection: self.collection,
                        processKeyEvents: false
                    });

                    self.collectionView.render();

                    self.collection.fetch({
                        data: {
                            reviewed: 0,
                            newsletter: self.options.newsletter
                        }
                    });
                }

                // If we detect that the pending counts have changed on the server, refetch the members so that we add/remove
                // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
                self.listenTo(Iznik.Session, 'storiescountschanged', _.bind(self.countsChanged, self));
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Member.Story = Iznik.View.Timeago.extend({
        tagName: 'li',

        template: 'modtools_members_stories_story',

        events: {
            'click .js-chat': 'chat',
            'click .js-use': 'use',
            'click .js-dont': 'dont'
        },

        use: function() {
            var self = this;
            if (self.options.newsletter) {
                self.model.useForNewsletter().then(function() {
                    self.$el.fadeOut('slow');
                })
            } else {
                self.model.useForPublicity().then(function() {
                    self.$el.fadeOut('slow');
                })
            }
        },

        dont: function() {
            var self = this;
            if (self.options.newsletter) {
                self.model.dontUseForNewsletter().then(function() {
                    self.$el.fadeOut('slow');
                })
            } else {
                self.model.dontUseForPublicity().then(function() {
                    self.$el.fadeOut('slow');
                })
            }
        },

        chat: function() {
            var self = this;
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                ChatHolder().openChatToUser(self.model.get('user').id);
            })
        }
    });
});