Iznik.Views.ModTools.Member = IznikView.extend({
    rarelyUsed: function() {
        this.$('.js-rarelyused').fadeOut('slow');
        this.$('.js-stdmsgs li').fadeIn('slow');
    },


    addOtherEmails: function() {
        var self = this;
        var thisemail = self.model.get('email');

        // Add any other emails
        self.$('.js-otheremails').empty();
        _.each(self.model.get('otheremails'), function(email) {
            if (email.email != thisemail) {
                var mod = new IznikModel(email);
                var v = new Iznik.Views.ModTools.Message.OtherEmail({
                    model: mod
                });
                self.$('.js-otheremails').append(v.render().el);
            }
        });
    }
});

Iznik.Views.ModTools.Pages.ApprovedMembers = Iznik.Views.Page.extend({
    modtools: true,
    search: false,
    context: null,

    template: "modtools_members_approved_main",

    events: {
        'click .js-search': 'search',
        'keyup .js-searchterm': 'keyup'
    },

    fetching: null,
    context: null,

    keyup: function(e) {
        // Search on enter.
        if (e.which == 13) {
            this.$('.js-search').click();
        }
    },

    fetch: function(start) {
        var self = this;

        self.$('.js-none').hide();

        var data = {};

        if (self.selected > 0) {
            // Specific group
            data.groupid = self.selected;
        }

        if (self.options.search) {
            // We're searching.  Pass any previous search results context so that we get the next set of results.
            if (self.members.ret) {
                data.context = self.members.ret.context;
            }
        } else {
            // We're not searching. We page using the email.
            data.start = self.start;
        }

        // Fetch more members - and leave the old ones in the collection
        if (self.fetching == self.selected) {
            // Already fetching the right group.
            return;
        } else {
            self.fetching = self.selected;
        }

        var v = new Iznik.Views.PleaseWait();
        v.render();

        this.members.fetch({
            data: {
                context: self.context
            },
            remove: self.selected != self.lastFetched
        }).then(function() {
            v.close();

            self.fetching = null;
            self.lastFetched = self.selected;

            if (self.members.length > 0) {
                // Peek into the underlying response to see if it returned anything and therefore whether it is
                // worth asking for more if we scroll that far.
                var gotsome = self.members.ret.group.members.length > 0;

                self.members.each(function(member) {
                    //console.log("Fetched", msg.get('id'), msg.get('date'));
                    var thisone = member.get('email');
                    if (self.start == null || thisone > self.start) {
                        self.start = thisone;
                        gotsome = true;
                    }
                });

                // Waypoints allow us to see when we have scrolled to the bottom.
                if (self.lastWaypoint) {
                    self.lastWaypoint.destroy();
                }

                if (gotsome) {
                    // We got some different members, so set up a scroll handler.  If we didn't get any different
                    // members, then there's no point - we could keep hitting the server with more requests
                    // and not getting any.
                    self.context = self.members.ret.context;
                    var vm = self.collectionView.viewManager;
                    var lastView = vm.last();

                    if (lastView) {
                        self.lastMember = lastView;
                        self.lastWaypoint = new Waypoint({
                            element: lastView.el,
                            handler: function(direction) {
                                if (direction == 'down') {
                                    // We have scrolled to the last view.  Fetch more as long as we've not switched
                                    // away to another page.
                                    if (jQuery.contains(document.documentElement, lastView.el)) {
                                        self.fetch();
                                    }
                                }
                            },
                            offset: '99%' // Fire as soon as this view becomes visible
                        });
                    }
                }
            } else {
                self.$('.js-none').fadeIn('slow');
            }
        });
    },

    search: function() {
        var term = this.$('.js-searchterm').val();

        if (term != '') {
            Router.navigate('/modtools/members/' + encodeURIComponent(term), true);
        } else {
            Router.navigate('/modtools/members', true);
        }
    },

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        self.groupSelect = new Iznik.Views.Group.Select({
            systemWide: false,
            all: true,
            mod: true,
            counts: [ 'approved', 'approvedother' ],
            id: 'approvedGroupSelect'
        });

        self.listenTo(self.groupSelect, 'selected', function(selected) {
            // Change the group selected.
            self.selected = selected;

            // The type of collection we're using depends on whether we're searching.  It controls how we fetch.
            if (self.options.search) {
                self.members = new Iznik.Collections.Members.Search(null, {
                    groupid: self.selected,
                    group: Iznik.Session.get('groups').get(self.selected),
                    search: self.options.search
                });

                self.$('.js-searchterm').val(self.options.search);
            } else {
                self.members = new Iznik.Collections.Members(null, {
                    groupid: self.selected,
                    group: Iznik.Session.get('groups').get(self.selected)
                });
            }

            // CollectionView handles adding/removing/sorting for us.
            self.collectionView = new Backbone.CollectionView( {
                el : self.$('.js-list'),
                modelView : Iznik.Views.ModTools.Member.Approved,
                modelViewOptions: {
                    collection: self.members,
                    page: self
                },
                collection: self.members
            } );

            self.collectionView.render();

            // We haven't fetched anything for this group yet.
            self.lastFetched = null;

            // Do so.
            self.fetch();
        });

        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(self.groupSelect.render().el);

        // If we detect that the pending counts have changed on the server, refetch the members so that we add/remove
        // appropriately.
        this.listenTo(Iznik.Session, 'approvedmemberscountschanged', _.bind(this.fetch, this));
        this.listenTo(Iznik.Session, 'approvedmemberscountschanged', _.bind(this.groupSelect.render, this.groupSelect));
        this.listenTo(Iznik.Session, 'approvedmembersothercountschanged', _.bind(this.groupSelect.render, this.groupSelect));

        // We seem to need to redelegate
        self.delegateEvents();
    }
});

Iznik.Views.ModTools.Member.Approved = Iznik.Views.ModTools.Member.extend({
    template: 'modtools_members_approved_member',

    events: {
        'click .js-delete' : 'deleteMe',
        'click .js-rarelyused': 'rarelyUsed'
    },

    deleteMe: function() {
        var self = this;
    },

    render: function() {
        var self = this;

        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        self.addOtherEmails();

        // Get the group from the collection.
        var group = self.model.collection.options.group;

        // Our user
        var v = new Iznik.Views.ModTools.User({
            model: self.model
        });

        self.$('.js-user').html(v.render().el);

        // The Yahoo part of the user
        var mod = IznikYahooUsers.findUser({
            email: self.model.get('email'),
            group: group.get('nameshort'),
            groupid: group.get('id')
        });

        mod.fetch().then(function() {
            var v = new Iznik.Views.ModTools.Yahoo.User({
                model: mod
            });
            self.$('.js-yahoo').append(v.render().el);
        });

        // Add the default standard actions.
        var configs = Iznik.Session.get('configs');
        var sessgroup = Iznik.Session.get('groups').get(group.id);
        var config = configs.get(sessgroup.get('configid'));

        self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
            model: new IznikModel({
                title: 'Mail',
                action: 'Leave Approved Member',
                message: self.model,
                config: config
            })
        }).render().el);

        self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
            model: new IznikModel({
                title: 'Remove',
                action: 'Delete Approved Member',
                message: self.model,
                config: config
            })
        }).render().el);

        if (config) {
            // Add the other standard messages, in the order requested.
            var sortmsgs = orderedMessages(config.get('stdmsgs'), config.get('messageorder'));
            var anyrare = false;

            _.each(sortmsgs, function (stdmsg) {
                if (_.contains(['Leave Approved Member', 'Delete Approved Member'], stdmsg.action)) {
                    stdmsg.message = self.model;
                    var v = new Iznik.Views.ModTools.StdMessage.Button({
                        model: new IznikModel(stdmsg),
                        config: config
                    });

                    var el = v.render().el;
                    self.$('.js-stdmsgs').append(el);

                    if (stdmsg.rarelyused) {
                        anyrare = true;
                        $(el).hide();
                    }
                }
            });

            if (!anyrare) {
                self.$('.js-rarelyholder').hide();
            }
        }

        this.$('.timeago').timeago();

        // If we delete this member then the view should go.
        this.listenToOnce(self.model, 'deleted', function() {
            self.$el.fadeOut('slow', function() {
                self.remove();
            });
        });

        return(this);
    }
});

Iznik.Views.ModTools.Member.OtherEmail = IznikView.extend({
    template: 'modtools_member_otheremail'
});

