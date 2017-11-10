define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    'iznik/views/pages/pages',
    'iznik/views/infinite',
    'iznik/views/group/select'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.PendingMembers = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_members_pending_main",

        retField: 'members',

        events: {
            'click .js-search': 'search',
            'keyup .js-searchterm': 'keyup'
        },

        countsChanged: function() {
            this.groupSelect.render();
        },

        keyup: function (e) {
            // Search on enter.
            if (e.which == 13) {
                this.$('.js-search').click();
            }
        },

        search: function () {
            var term = this.$('.js-searchterm').val();

            if (term != '') {
                Router.navigate('/modtools/members/pending/' + encodeURIComponent(term), true);
            } else {
                Router.navigate('/modtools/members/pending', true);
            }
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'modtools_members_pending_help';
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                });

                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: true,
                    mod: true,
                    counts: ['pendingmembers', 'pendingmembersother'],
                    id: 'pendingGroupSelect'
                });

                // The type of collection we're using depends on whether we're searching.  It controls how we fetch.
                if (self.options.search) {
                    self.collection = new Iznik.Collections.Members.Search(null, {
                        groupid: self.selected,
                        group: Iznik.Session.get('groups').get(self.selected),
                        search: self.options.search,
                        collection: 'Pending'
                    });

                    self.$('.js-searchterm').val(self.options.search);
                } else {
                    self.collection = new Iznik.Collections.Members(null, {
                        groupid: self.selected,
                        group: Iznik.Session.get('groups').get(self.selected),
                        collection: 'Pending'
                    });
                }

                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.Member.Pending,
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
                        groupid: self.selected > 0 ? self.selected : null
                    });
                });

                // Render after the listen to as they are called during render.
                self.groupSelect.render().then(function(v) {
                    self.$('.js-groupselect').html(v.el);
                });

                // If we detect that the pending counts have changed on the server, refetch the members so that we add/remove
                // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
                self.listenTo(Iznik.Session, 'pendingmemberscountschanged', _.bind(self.countsChanged, self));
                self.listenTo(Iznik.Session, 'pendingmembersothercountschanged', _.bind(self.countsChanged, self));
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Member.Pending = Iznik.Views.ModTools.Member.extend({
        tagName: 'li',

        template: 'modtools_members_pending_member',

        events: {
            'click .js-rarelyused': 'rarelyUsed'
        },

        render: function () {
            var self = this;

            if (!self.rendering) {
                self.rendering = new Promise(function(resolve, reject) {
                    var groupid = self.model.get('groupid');
                    var group = Iznik.Session.getGroup(groupid);
                    if (group) {
                        self.model.set('group', group.attributes);
                        var p = Iznik.Views.ModTools.Member.prototype.render.call(self);
                        p.then(function(self) {
                            var mom = new moment(self.model.get('joined'));

                            var now = new moment();

                            self.$('.js-joined').html(mom.format('llll'));

                            if (now.diff(mom, 'days') <= 31) {
                                self.$('.js-joined').addClass('error');
                            }

                            self.addOtherInfo();

                            // Our user.  In memberships the id is that of the member, so we need to get the userid.
                            var mod = self.model.clone();
                            mod.set('id', self.model.get('userid'));
                            mod.set('myrole', Iznik.Session.roleForGroup(self.model.get('groupid'), true));

                            var v = new Iznik.Views.ModTools.User({
                                model: mod
                            });

                            v.render().then(function(v) {
                                self.$('.js-user').html(v.el);
                            })

                            if (group.get('type') == 'Freegle') {
                                var v = new Iznik.Views.ModTools.Member.Freegle({
                                    model: mod
                                });

                                v.render().then(function (v) {
                                    self.$('.js-freegleinfo').append(v.el);
                                });
                            }

                            // No remove button for pending members.
                            self.$('.js-remove').closest('li').hide();

                            if (group.get('onyahoo')) {
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
                                        })
                                    });
                                }, 200);
                            }

                            // Add the default standard actions.
                            var configs = Iznik.Session.get('configs');
                            var sessgroup = Iznik.Session.get('groups').get(group.id);
                            var config = configs.get(sessgroup.get('configid'));

                            // Save off the groups in the member ready for the standard message
                            // TODO Hacky.  Should we split the StdMessage.Button code into one for members and one for messages?
                            self.model.set('groups', [group.attributes]);
                            self.model.set('fromname', self.model.get('displayname'));
                            self.model.set('fromaddr', self.model.get('email'));
                            self.model.set('fromuser', self.model);

                            if (self.model.get('heldby')) {
                                // Message is held - just show Release button.
                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Release',
                                        action: 'Release',
                                        message: self.model,
                                        config: config
                                    })
                                }).render().then(function(v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });
                            } else {
                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Approve',
                                        action: 'Approve Member',
                                        member: self.model,
                                        config: config
                                    })
                                }).render().then(function(v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });

                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Mail',
                                        action: 'Leave Member',
                                        member: self.model,
                                        config: config
                                    })
                                }).render().then(function(v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });

                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Delete',
                                        action: 'Delete Member',
                                        member: self.model,
                                        config: config
                                    })
                                }).render().then(function(v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });

                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Reject',
                                        action: 'Reject Member',
                                        member: self.model,
                                        config: config
                                    })
                                }).render().then(function(v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });

                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Hold',
                                        action: 'Hold',
                                        message: self.model,
                                        config: config
                                    })
                                }).render().then(function(v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });

                                if (config) {
                                    // Add the other standard messages, in the order requested.
                                    var sortmsgs = orderedMessages(config.get('stdmsgs'), config.get('messageorder'));
                                    var anyrare = false;

                                    _.each(sortmsgs, function (stdmsg) {
                                        if (_.contains(['Leave Member', 'Reject Member', 'Approve Member'], stdmsg.action)) {
                                            stdmsg.groups = [group];
                                            stdmsg.member = self.model;
                                            var v = new Iznik.Views.ModTools.StdMessage.Button({
                                                model: new Iznik.Models.ModConfig.StdMessage(stdmsg),
                                                config: config
                                            });

                                            if (stdmsg.rarelyused) {
                                                anyrare = true;
                                            }

                                            v.render().then(function(v) {
                                                self.$('.js-stdmsgs').append(v.el);

                                                if (stdmsg.rarelyused) {
                                                    $(v.el).hide();
                                                }
                                            });
                                        }
                                    });

                                    if (!anyrare) {
                                        self.$('.js-rarelyholder').hide();
                                    }
                                }
                            }

                            // If the member is held or released, we re-render, showing the appropriate buttons.
                            self.listenToOnce(self.model, 'change:heldby', self.render);

                            self.$('.timeago').timeago();

                            self.listenToOnce(self.model, 'deleted removed rejected approved', function () {
                                self.$el.fadeOut('slow');
                            });

                            resolve();
                            self.rendering = null;
                        });
                    } else {
                        console.log("Unexpected member", self.model);
                    }
                });
            }

            return (self.rendering);
        }
    });
});