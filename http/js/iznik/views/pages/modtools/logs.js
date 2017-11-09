define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    "iznik/modtools",
    'iznik/models/logs',
    'iznik/views/pages/pages',
    "iznik/views/pages/modtools/messages",
    'iznik/views/infinite',
    'iznik/views/group/select'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.ModTools.Pages.Logs = Iznik.Views.Infinite.extend({
        modtools: true,

        logtype: 'messages',

        template: "modtools_logs_main",

        retField: 'logs',

        events: {
            'change .js-logsubtype': 'refetch',
            'change .js-date': 'refetch',
            'click .js-searchbtn': 'refetch'
        },

        refetch: function() {
            var self = this;

            self.lastFetched = null;
            self.context = null;
            self.collection.reset();

            self.fetch({
                groupid: self.selected,
                logtype: self.logtype,
                logsubtype: self.$('.js-logsubtype').val(),
                search: self.$('.js-search').val(),
                date: self.$('.js-date').val()
            });
        },

        render: function () {
            var self = this;
            self.logtype = self.options.logtype ? self.options.logtype : 'messages';

            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                if (self.logtype == 'messages') {
                    self.$('.js-navmess').addClass('active');
                } else if (self.logtype == 'memberships') {
                    self.$('.js-navmemb').addClass('active');
                }

                self.collection = new Iznik.Collections.Logs(null, {
                    modtools: true,
                    groupid: self.selected,
                    group: Iznik.Session.get('groups').get(self.selected),
                    logtype: self.logtype
                });

                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: false,
                    mod: true,
                    id: 'logsSelect'
                });

                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.Pages.Logs.One,
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
                    self.refetch();
                });

                // Render after the listen to as they are called during render.
                self.groupSelect.render().then(function(v) {
                    self.$('.js-groupselect').html(v.el);
                });
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Pages.Logs.One = Iznik.View.extend({
        tagName: 'li',

        template: "modtools_logs_one",

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(new Date(self.model.get('timestamp')));
                self.$('.js-timestamp').html(m.format('MMM DD YYYY hh:mmA'));
            });

            return(p);
        }
    });
});