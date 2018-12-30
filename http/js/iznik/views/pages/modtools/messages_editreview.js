define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/yahoo/user',
    "iznik/modtools",
    'iznik/views/pages/pages',
    "iznik/views/pages/modtools/messages",
    'iznik/views/infinite',
    'iznik/views/group/select'
], function($, _, Backbone, Iznik, IznikYahooUsers) {
    Iznik.Views.ModTools.Pages.EditReviewMessages = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_editreview_main",

        retField: 'messages',

        countsChanged: function() {
            this.groupSelect.render();
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'modtools_editreview_info';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                });

                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: true,
                    mod: true,
                    counts: ['editreview', 'editreviewother'],
                    id: 'editReviewGroupSelect'
                });

                self.collection = new Iznik.Collections.Message(null, {
                    modtools: true,
                    groupid: self.selected,
                    group: Iznik.Session.get('groups').get(self.selected),
                    collection: 'Edit'
                });

                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.Message.EditReview,
                    modelViewOptions: {
                        collection: self.collection,
                        page: self
                    },
                    collection: self.collection,
                    processKeyEvents: false
                });

                self.collectionView.render();

                self.listenTo(self.groupSelect, 'selected', function (selected) {
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

                // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
                // appropriately.
                self.listenTo(Iznik.Session, 'editreviewcountschanged', _.bind(self.fetch, self));
                self.listenTo(Iznik.Session, 'editreviewcountschanged', _.bind(self.countsChanged, self));
                self.listenTo(Iznik.Session, 'editreviewcountsotherchanged', _.bind(self.countsChanged, self));
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Message.EditReview = Iznik.Views.ModTools.Message.extend({
        tagName: 'li',
        template: 'modtools_editreview_message',
        collectionType: 'Edit',

        events: {
            'click .js-accept': 'accept',
            'click .js-revert': 'revert',
            'click .js-chat': 'chat'
        },

        chat: function() {
            var self = this;
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                ChatHolder().openChatToUser(self.model.get('fromuser').id);
            })
        },

        accept: function () {
            var self = this;
            _.each(self.model.get('groups'), function (group, index, list) {
                $.ajax({
                    type: 'POST',
                    url: API + 'message',
                    data: {
                        id: self.model.get('id'),
                        action: 'ApproveEdits'
                    }, success: function (ret) {
                        self.$el.fadeOut('slow');
                    }
                });
            });
        },

        revert: function () {
            var self = this;
            _.each(self.model.get('groups'), function (group, index, list) {
                $.ajax({
                    type: 'POST',
                    url: API + 'message',
                    data: {
                        id: self.model.get('id'),
                        action: 'RevertEdits'
                    }, success: function (ret) {
                        self.$el.fadeOut('slow');
                    }
                });
            });
        },

        rendering: null,

        render: function () {
            var self = this;

            self.model.set('mapicon', '/images/mapmarker.gif');

            // Get a zoom level for the map.
            _.each(self.model.get('groups'), function (group) {
                self.model.set('mapzoom', group.settings.hasOwnProperty('map') ? group.settings.map.zoom : 9);
            });

            if (!self.rendering) {
                self.rendering = new Promise(function(resolve, reject) {
                    var p = Iznik.Views.ModTools.Message.prototype.render.call(self);
                    p.then(function(self) {
                        _.each(self.model.get('groups'), function (group, index, list) {
                            self.$('.js-type').val(self.model.get('type'));
                            self.$('.js-item').val(self.model.get('item').name);
                            self.$('.js-location').val(self.model.get('location').name);
                            self.$('.js-postcode').html(self.model.get('postcode').name);
                            self.$('.js-area').html(self.model.get('area').name);

                            self.$('.js-location').typeahead({
                                minLength: 2,
                                hint: false,
                                highlight: true
                            }, {
                                name: 'postcodes',
                                source: _.bind(self.postcodeSource, self)
                            });

                            var mod = new Iznik.Model(group);

                            // Add in the message, because we need some values from that
                            mod.set('message', self.model.toJSON());

                            var v = new Iznik.Views.ModTools.Message.EditReview.Group({
                                model: mod
                            });
                            v.render().then(function (v) {
                                self.$('.js-grouplist').append(v.el);
                            });

                            var mod = new Iznik.Models.ModTools.User(self.model.get('fromuser'));
                            mod.set('groupid', group.id);

                            var v = new Iznik.Views.ModTools.User({
                                model: mod
                            });

                            v.render().then(function (v) {
                                self.$('.js-user').append(v.el);
                            });

                            if (group.onyahoo) {
                                // The Yahoo part of the user
                                var mod = IznikYahooUsers.findUser({
                                    email: self.model.get('envelopefrom') ? self.model.get('envelopefrom') : self.model.get('fromaddr'),
                                    group: group.nameshort,
                                    groupid: group.id
                                });

                                mod.fetch().then(function () {
                                    var v = new Iznik.Views.ModTools.Yahoo.User({
                                        model: mod
                                    });
                                    v.render().then(function (v) {
                                        self.$('.js-yahoo').html(v.el);
                                    });
                                });
                            }
                        });

                        self.addOtherInfo();

                        // Add any attachments.
                        self.$('.js-attlist').empty();
                        _.each(self.model.get('attachments'), function (att) {
                            var v = new Iznik.Views.ModTools.Message.Photo({
                                model: new Iznik.Model(att),
                                message: self.model
                            });

                            v.render();
                            self.$('.js-attlist').append(v.el);
                        });

                        self.$('.timeago').timeago();
                        self.$el.fadeIn('slow');

                        resolve();
                        self.rendering = null;
                    });
                });
            }

            return (self.rendering);
        }
    });

    Iznik.Views.ModTools.Message.EditReview.Group = Iznik.View.extend({
        template: 'modtools_editreview_group'
    });
});