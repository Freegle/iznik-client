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
    Iznik.Views.ModTools.Pages.HappinessMembers = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_members_happiness_main",

        retField: 'members',

        countsChanged: function() {
            this.groupSelect.render();
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'modtools_members_happiness_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                });

                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: true,
                    mod: true,
                    counts: ['happinessmembers'],
                    id: 'happinessGroupSelect'
                });

                self.listenTo(self.groupSelect, 'selected', function (selected) {
                    // Change the group selected.
                    self.selected = selected;

                    // We haven't fetched anything for this group yet.
                    self.lastFetched = null;
                    self.context = null;

                    self.collection = new Iznik.Collections.Members.Happiness(null, {
                        groupid: self.selected
                    });

                    // CollectionView handles adding/removing/sorting for us.
                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.ModTools.Member.Happiness,
                        modelViewOptions: {
                            collection: self.collection,
                            page: self
                        },
                        collection: self.collection,
                        processKeyEvents: false
                    });

                    self.collectionView.render();

                    self.fetch({
                        groupid: self.selected > 0 ? self.selected : null
                    });
                });

                // Render after the listen to as they are called during render.
                self.groupSelect.render().then(function(v) {
                    self.$('.js-groupselect').html(v.el);
                });

                // If we detect that the pending counts have changed on the server, refetch the members so that we add/remove
                // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
                self.listenTo(Iznik.Session, 'happinessmemberscountschanged', _.bind(self.countsChanged, self));
                self.listenTo(Iznik.Session, 'happpinessmembersothercountschanged', _.bind(self.countsChanged, self));
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Member.Happiness = Iznik.Views.ModTools.Member.extend({
        tagName: 'li',

        template: 'modtools_members_happiness_member',

        events: {
            'click .js-chat': 'chat'
        },

        chat: function() {
            var self = this;
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                ChatHolder().openChatToUser(self.model.get('user').id);
            })
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.ModTools.Member.prototype.render.call(self);
            p.then(function(self) {
                self.$('.timeago').timeago();

                var groupid = self.model.get('message').groups[0].groupid;
                var group = Iznik.Session.getGroup(groupid);
                self.$('.js-group').html(group.get('nameshort'));
            });

            return(p);
        }
    });
});