define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    "iznik/models/chat/chat",
    'iznik/views/pages/pages',
    'iznik/views/infinite'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.ChatReport = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_chatreport_main",

        retField: 'chatmessages',

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'modtools_chatreport_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                });

                self.context = null;
                self.collection = new Iznik.Collections.Chat.Report();

                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.ChatReport,
                    modelViewOptions: {
                        collection: self.collection,
                        page: self
                    },
                    collection: self.collection
                });

                self.collectionView.render();
                self.fetch();

                self.listenTo(Iznik.Session, 'chatreportcountschanged', _.bind(self.fetch, self));
                self.listenTo(Iznik.Session, 'chatreportothercountschanged', _.bind(self.fetch, self));
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.ChatReport = Iznik.View.Timeago.extend({
        template: 'modtools_chatreport_one',

        events: {
            'click .js-approve': 'approve',
            'click .js-delete': 'deleteMe',
            'click .js-view': 'view'
        },

        approve: function() {
            var self = this;
            $.ajax({
                url: API + 'chatmessages',
                type: 'POST',
                data: {
                    id: self.model.get('id'),
                    action: 'Approve'
                }, success: function(ret) {
                    if (ret.ret === 0) {
                        self.$el.fadeOut('slow', _.bind(self.destroyIt, self));
                    }
                }
            })
        },

        deleteMe: function() {
            var self = this;
            $.ajax({
                url: API + 'chatmessages',
                type: 'POST',
                data: {
                    id: self.model.get('id'),
                    action: 'Reject'
                }, success: function(ret) {
                    if (ret.ret === 0) {
                        self.$el.fadeOut('slow', _.bind(self.destroyIt, self));
                    }
                }
            })
        },

        view: function() {
            var self = this;

            var chat = new Iznik.Models.Chat.Room({
                id: self.model.get('chatid')
            });

            chat.fetch().then(function() {
                var v = new Iznik.Views.Chat.Modal({
                    model: chat
                });

                v.render();
            });
        }
    });
});