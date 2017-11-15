define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/selectpersist',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/group/communityevents',
    'iznik/views/group/volunteering',
    'iznik/views/pages/user/post',
    'iznik/views/pages/user/group',
    'iznik/views/group/select',
    'iznik/views/user/message',
    'iznik/views/supportus'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Pages.MyGroups = Iznik.Views.User.Pages.Group.extend({
        template: "user_mygroups_main",

        render: function () {
            var p = Iznik.Views.User.Pages.Group.prototype.render.call(this);

            // (new Iznik.Views.User.eBay()).render();

            Storage.set('lasthomepage', 'mygroups');

            p.then(function (self) {
                var mygroups = Iznik.Session.get('groups');

                if (mygroups && mygroups.length > 0) {
                    self.$('.js-browse').show();

                    self.collection = new Iznik.Collections.Message(null, {
                        modtools: false,
                        collection: 'Approved'
                    });

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-msglist'),
                        modelView: Iznik.Views.User.Message.Replyable,
                        modelViewOptions: {
                            collection: self.collection,
                            page: self
                        },
                        collection: self.collection,
                        visibleModelsFilter: _.bind(self.filter, self),
                        processKeyEvents: false
                    });

                    self.collectionView.render();

                    // Add a group selector and re-render if we change it.  The selected function is called during
                    // render which will trigger the initial view.
                    var v = new Iznik.Views.Group.Select({
                        systemWide: false,
                        all: true,
                        mod: false,
                        grouptype: 'Freegle',
                        id: 'myGroupsSelect'
                    });

                    self.listenTo(v, 'selected', function (selected) {
                        self.selected = selected;
                        self.refetch();

                        var group = null;

                        if (selected == -1) {
                            // No specific group.  But if we have a single group, we want to show that.
                            if (mygroups.length === 1) {
                                group = mygroups.first();
                            }
                        } else {
                            // Show info, including leave button, for this group.
                            group = Iznik.Session.getGroup(selected);
                        }

                        self.$('.js-groupinfo').empty();

                        if (group) {
                            var w = new Iznik.Views.User.Pages.MyGroups.GroupInfo({
                                model: new Iznik.Models.Group(group.attributes)
                            });
                            w.render().then(function () {
                                self.$('.js-groupinfo').html(w.$el);
                            });
                        }

                        // Left menu is community events
                        var v = new Iznik.Views.User.CommunityEventsSidebar({
                            groupid: selected == -1 ? null : selected
                        });
                        v.render().then(function () {
                            $('#js-eventcontainer').html(v.$el);
                        });

                        // Right menu is volunteer vacancies
                        var x = new Iznik.Views.User.VolunteeringSidebar({
                            groupid: selected == -1 ? null : selected
                        });
                        x.render().then(function () {
                            $('#js-volunteeringcontainer').append(x.$el);
                        });
                    });

                    // Render after the listen to as that are called during render.
                    v.render().then(function (v) {
                        self.$('.js-msggroupselect').html(v.el);
                    });

                    // Add a type selector.  The parent class has an event and method to re-render if we change that.
                    self.$('.js-type').selectpicker();
                    self.$('.js-type').selectPersist();
                } else {
                    self.$('.js-somegroups').hide();
                    self.$('.js-nogroups').fadeIn('slow');
                }
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.MyGroups.GroupInfo = Iznik.View.extend({
        template: 'user_mygroups_groupinfo',

        events: {
            'click .js-leave': 'leave'
        },

        leave: function () {
            var self = this;

            $.ajax({
                url: API + 'memberships',
                type: 'DELETE',
                data: {
                    groupid: self.model.get('id'),
                    userid: Iznik.Session.get('me').id
                },
                success: function (ret) {
                    if (ret.ret === 0) {
                        // Now force a refresh of the session.
                        window.location.reload();
                    }
                }
            })
        },

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function () {
                self.$('.js-membercount').html(self.model.get('membercount').toLocaleString());

                // Add the description
                var desc = self.model.get('description');

                if (desc) {
                    self.$('.js-gotdesc').show();
                    self.$('.js-description').html(desc);

                    // Any links in here are real.
                    self.$('.js-description a').attr('data-realurl', true);
                }

                var founded = self.model.get('founded');
                if (founded) {
                    var m = new moment(founded);
                    self.$('.js-foundeddate').html(m.format('Do MMMM, YYYY'));
                    self.$('.js-founded').show();
                }

                // Fetch any visible mods.
                self.model.fetch({
                    data: {
                        showmods: true
                    }
                }).then(function () {
                    console.log("Refetched", self.model.attributes);
                    var showmods = self.model.get('showmods');

                    if (showmods) {
                        self.showmods = new Iznik.Collection(showmods);

                        self.collectionView = new Backbone.CollectionView({
                            el: self.$('.js-showmods'),
                            modelView: Iznik.Views.User.Pages.MyGroups.ModInfo,
                            collection: self.showmods,
                            processKeyEvents: false
                        });

                        self.collectionView.render();
                        self.$('.js-modstoshow').fadeIn('slow');
                    }
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.MyGroups.ModInfo = Iznik.View.extend({
        tagName: 'li',
        template: 'user_mygroups_modinfo'
    });
});