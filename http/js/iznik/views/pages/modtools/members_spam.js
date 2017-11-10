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
    Iznik.Views.ModTools.Pages.SpamMembers = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_members_spam_main",

        retField: 'spammers',

        countsChanged: function() {
            this.groupSelect.render();
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'modtools_members_spam_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                });

                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: true,
                    mod: true,
                    counts: ['spammembers'],
                    id: 'spamGroupSelect'
                });

                self.listenTo(self.groupSelect, 'selected', function (selected) {
                    // Change the group selected.
                    self.selected = selected;

                    // We haven't fetched anything for this group yet.
                    self.lastFetched = null;
                    self.context = null;

                    self.collection = new Iznik.Collections.Members(null, {
                        groupid: self.selected,
                        group: Iznik.Session.get('groups').get(self.selected),
                        collection: 'Spam'
                    });

                    // CollectionView handles adding/removing/sorting for us.
                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.ModTools.Member.Spam,
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
                self.listenTo(Iznik.Session, 'approvedmemberscountschanged', _.bind(self.countsChanged, self));
                self.listenTo(Iznik.Session, 'approvedmembersothercountschanged', _.bind(self.countsChanged, self));
            });
            
            return(p);
        }
    });

    Iznik.Views.ModTools.Member.Spam = Iznik.Views.ModTools.Member.extend({
        tagName: 'li',

        template: 'modtools_members_spam_member',

        events: {
            'click .js-notspam': 'notSpam',
            'click .js-spam': 'spam',
            'click .js-whitelist': 'whitelist'
        },

        clearSuspect: function () {
            var self = this;

            var mod = new Iznik.Models.ModTools.User({
                id: self.model.get('userid')
            });

            $.ajax({
                url: API + 'user/' + self.model.get('userid'),
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                data: {
                    'suspectcount': 0,
                    'suspectreason': null,
                    'groupid': self.model.get('groupid')
                }, success: function (ret) {
                    self.$el.fadeOut('slow', function () {
                        self.remove();
                    })
                }
            });
        },

        notSpam: function () {
            // Record that this member isn't suspicious.  That will stop the server returning them to us.
            this.clearSuspect();
        },

        spam: function () {
            var self = this;

            var v = new Iznik.Views.ModTools.EnterReason();
            self.listenToOnce(v, 'reason', function (reason) {
                $.ajax({
                    url: API + 'spammers',
                    type: 'POST',
                    data: {
                        userid: self.model.get('userid'),
                        reason: reason,
                        collection: 'PendingAdd'
                    }, success: function (ret) {
                        // Now over to someone else to review this report - so remove from our list.
                        self.clearSuspect();
                    }
                });
            });

            v.render();
        },

        whitelist: function () {
            var self = this;

            var v = new Iznik.Views.ModTools.EnterReason();
            self.listenToOnce(v, 'reason', function (reason) {
                $.ajax({
                    url: API + 'spammers',
                    type: 'POST',
                    data: {
                        userid: self.model.get('userid'),
                        reason: reason,
                        collection: 'Whitelisted'
                    }, success: function (ret) {
                        // Now over to someone else to review this report - so remove from our list.
                        self.clearSuspect();
                    }
                });
            });

            v.render();
        },

        render: function () {
            var self = this;

            if (!self.rendering) {
                self.rendering = new Promise(function(resolve, reject) {
                    var p = Iznik.Views.ModTools.Member.prototype.render.call(self);
                    p.then(function(self) {
                        if (Iznik.Session.isAdmin()) {
                            self.$('.js-whitelist').css('visibility', 'visible');
                        }

                        var mom = new moment(self.model.get('joined'));
                        self.$('.js-joined').html(mom.format('llll'));

                        self.addOtherInfo();

                        // Get the group from the session
                        var group = Iznik.Session.getGroup(self.model.get('groupid'));

                        // Our user.  In memberships the id is that of the member, so we need to get the userid.
                        var mod = self.model.clone();
                        mod.set('id', self.model.get('userid'));
                        mod.set('myrole', Iznik.Session.roleForGroup(self.model.get('groupid'), true));

                        var v = new Iznik.Views.ModTools.User({
                            model: mod
                        });

                        v.render().then(function (v) {
                            self.$('.js-user').html(v.el);
                        });

                        if (group && group.get('type') == 'Freegle') {
                            var v = new Iznik.Views.ModTools.Member.Freegle({
                                model: mod
                            });

                            v.render().then(function (v) {
                                self.$('.js-freegleinfo').append(v.el);
                            })
                        }

                        // No report spammer button here.
                        //
                        // Auto remove and ban may be turned off, so leave those buttons.
                        self.$('.js-spammer').closest('li').hide();

                        if (group && group.get('onyahoo')) {
                            // Delay getting the Yahoo info slightly to improve apparent render speed.
                            _.delay(function () {
                                // The Yahoo part of the user
                                var mod = IznikYahooUsers.findUser({
                                    email: self.model.get('email'),
                                    group: group.get('nameshort'),
                                    groupid: group.get('id')
                                });

                                mod.fetch().then(function () {
                                    // We don't want to show the Yahoo joined date because we have our own.
                                    mod.unset('date');
                                    var v = new Iznik.Views.ModTools.Yahoo.User({
                                        model: mod
                                    });

                                    v.render().then(function (v) {
                                        self.$('.js-yahoo').html(v.el);
                                    });
                                });
                            }, 200);
                        }

                        self.$('.timeago').timeago();

                        self.listenToOnce(self.model, 'deleted removed rejected approved', function () {
                            self.$el.fadeOut('slow');
                        });

                        resolve();
                        self.rendering = null;
                    });
                });
            }

            return (self.rendering);
        }
    });
});