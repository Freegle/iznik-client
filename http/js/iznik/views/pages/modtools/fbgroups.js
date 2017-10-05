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
            'click .js-all': 'selectAll',
            'click .js-share': 'share'
        },

        selectAll: function() {
            if (this.$('.js-all').is(':checked')) {
                this.$('.js-msgselect').prop('checked', true);
            } else {
                this.$('.js-msgselect').prop('checked', false);
            }
        },

        render: function () {
            var self = this;
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'modtools_fbgroups_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                })

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
                        groupid: self.selected > 0 ? self.selected : null,
                        facebook_postable: true
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
        template: 'modtools_fbgroups_summary',

        render: function() {
            var self = this;

            var p = Iznik.View.Timeago.prototype.render.call(this);
            var arrival = (new Date(self.model.get('arrival'))).getTime();

            p.then(function() {
                self.$('.js-buttons').empty();

                var groups = self.model.get('groups');
                _.each(groups, function(group) {
                    var g = Iznik.Session.getGroup(group.id);
                    _.each(g.get('facebook'), function(fb) {
                        // Show groups where we haven't shared something more recent than this one.
                        if (fb.type == 'Group' && (!fb.msgarrival || (new Date(fb.msgarrival)).getTime() < arrival)) {
                            var v = new Iznik.Views.ModTools.Message.FacebookGroup({
                                model: new Iznik.Model(fb),
                                message: self.model,
                                group: g
                            });

                            v.render();
                            self.$('.js-buttons').append(v.$el);
                        }
                    });
                })
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Message.FacebookGroup = Iznik.View.extend({
        tagName: 'li',

        template: 'modtools_fbgroups_group',

        events: {
            'click .js-share': 'share'
        },

        share: function() {
            var self = this;

            FB.login(function(){
                var id = self.options.message.get('id');
                FB.api('/' + self.model.get('id') + '/feed', 'post', {
                    link: 'https://www.ilovefreegle.org/message/' + id + '?src=fbgroup'
                }, function(response) {
                    console.log("Share returned", response);
                    if (response.hasOwnProperty('error')) {
                        self.$('.js-error').html(response.error);
                        self.$el.show();
                    } else {
                        self.$el.fadeOut('slow');
                        var g = new Iznik.Models.Group();
                        g.recordFacebookShare(self.model.get('uid'), id, self.options.message.get('arrival'));
                    }
                });
            }, {
                scope: 'publish_actions'
            });
        }
    });
});