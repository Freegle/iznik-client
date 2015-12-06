Iznik.Views.ModTools.Pages.Approved = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_approved_main",

    fetching: false,
    start: null,
    startdate: null,

    fetch: function(start) {
        var self = this;

        if (!self.fetching) {
            self.fetching = true;
            self.$('.js-none').hide();

            var data = {
                collection: 'Approved'
            };

            if (self.selected > 0) {
                data.groupid = self.selected;
            }

            if (self.start) {
                // If we're selecting a different group, reset the start.
                data.start = self.selected != self.lastFetched ? null : self.startdate;
            }

            // Fetch more messages - and leave the old ones in the collection
            this.msgs.fetch({
                data: data,
                remove: self.selected != self.lastFetched
            }).then(function() {
                self.lastFetched = self.selected;

                self.fetching = false;
                if (!self.start) {
                    self.$('.js-none').fadeIn('slow');
                }

                if (self.msgs.length > 0) {
                    self.msgs.each(function(msg) {
                        var thisone = (new Date(msg.get('date'))).getTime();
                        if (self.start == null || thisone < self.start) {
                            self.start = thisone;
                            self.startdate = msg.get('date');
                        }
                    });

                    // Waypoints allow us to see when we have scrolled to the bottom.
                    if (self.lastWaypoint) {
                        self.lastWaypoint.destroy();
                    }

                    var vm = self.collectionView.viewManager;
                    var lastView = vm.last();

                    if (lastView) {
                        self.lastMessage = lastView;
                        self.lastWaypoint = new Waypoint({
                            element: lastView.el,
                            handler: function(direction) {
                                if (direction == 'down') {
                                    // We have scrolled to the last view.  Fetch more.
                                    self.fetch();
                                }
                            },
                            offset: '99%' // Fire as soon as this view becomes visible
                        });
                    }
                }
            });
        }
    },

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        this.msgs = new Iznik.Collections.Message();

        // CollectionView handles adding/removing/sorting for us.
        self.collectionView = new Backbone.CollectionView( {
            el : self.$('.js-list'),
            modelView : Iznik.Views.ModTools.Message.Approved,
            modelViewOptions: {
                collection: self.msgs,
                page: self
            },
            collection: self.msgs
        } );

        self.collectionView.render();

        var v = new Iznik.Views.Group.Select({
            systemWide: false,
            all: true,
            id: 'groupSelect'
        });

        self.listenTo(v, 'selected', function(selected) {
            self.selected = selected;
            self.fetch();
        });

        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(v.render().el);

        // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
        // appropriately.
        this.listenTo(Iznik.Session, 'approvedcountschanged', this.fetch);
    }
});

Iznik.Views.ModTools.Message.Approved = Iznik.Views.ModTools.Message.extend({
    template: 'modtools_approved_message',

    events: {
        'click .js-delete' : 'deleteMe',
        'click .js-viewsource': 'viewSource',
        'click .js-rarelyused': 'rarelyUsed'
    },

    deleteMe: function() {
        var self = this;

        // We delete the message on all groups.  Future enhancement?
        _.each(self.model.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: self.model.get('id'),
                    groupid: group.id,
                    action: 'Delete'
                }, success: function(ret) {
                    self.$el.fadeOut('slow');
                }
            })
        });
    },

    render: function() {
        var self = this;

        self.$el.html(window.template(self.template)(self.model.toJSON2()));
        _.each(self.model.get('groups'), function(group, index, list) {
            var mod = new IznikModel(group);

            // Add in the message, because we need some values from that
            mod.set('message', self.model.toJSON());

            var v = new Iznik.Views.ModTools.Message.Approved.Group({
                model: mod
            });
            self.$('.js-grouplist').append(v.render().el);

            var mod = new Iznik.Models.ModTools.User(self.model.get('fromuser'));
            var v = new Iznik.Views.ModTools.User({
                model: mod
            });

            self.$('.js-user').html(v.render().el);

            // The Yahoo part of the user
            var mod = IznikYahooUsers.findUser({
                email: self.model.get('envelopefrom') ? self.model.get('envelopefrom') : self.model.get('fromaddr'),
                group: group.nameshort,
                groupid: group.id
            });

            mod.fetch().then(function() {
                var v = new Iznik.Views.ModTools.Yahoo.User({
                    model: mod
                });
                self.$('.js-yahoo').append(v.render().el);
            });
        });

        // Add any attachments.
        _.each(self.model.get('attachments'), function(att) {
            var v = new Iznik.Views.ModTools.Message.Photo({
                model: new IznikModel(att)
            });

            self.$('.js-attlist').append(v.render().el);
        });

        // Add the default standard actions.
        var configs = Iznik.Session.get('configs');
        var sessgroup = Iznik.Session.get('groups').get(group.id);
        var config = configs.get(sessgroup.get('configid'));

        self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
            model: new IznikModel({
                title: 'Reply',
                action: 'Leave Approved Message',
                message: self.model,
                config: config
            })
        }).render().el);

        self.$('.js-stdmsgs').append(new Iznik.Views.ModTools.StdMessage.Button({
            model: new IznikModel({
                title: 'Delete',
                action: 'Delete Approved Message',
                message: self.model,
                config: config
            })
        }).render().el);

        if (config) {
            self.checkMessage(config);

            // Add the other standard messages, in the order requested.
            var stdmsgs = config.get('stdmsgs');
            var order = JSON.parse(config.get('messageorder'));
            var sortmsgs = [];
            _.each(order, function (id) {
                var stdmsg = null;
                _.each(stdmsgs, function (thisone) {
                    if (thisone.id == id) {
                        stdmsg = thisone;
                    }
                });

                if (stdmsg) {
                    sortmsgs.push(stdmsg);
                    stdmsgs = _.without(stdmsgs, stdmsg);
                }
            });

            sortmsgs = $.merge(sortmsgs, stdmsgs);

            var anyrare = false;

            _.each(sortmsgs, function (stdmsg) {
                if (_.contains(['Leave Approved Message', 'Delete Approved Message'], stdmsg.action)) {
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
        //this.$el.fadeIn('slow');

        return(this);
    }
});

Iznik.Views.ModTools.Message.Approved.Group = IznikView.extend({
    template: 'modtools_approved_group',

    render: function() {
        var self = this;
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        return(this);
    }
});
