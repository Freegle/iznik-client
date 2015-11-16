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

            if (self.start) {
                data.start = self.startdate;
            }

            // Fetch more messages - and leave the old ones in the collection
            this.msgs.fetch({
                data: data,
                reset: false,
                remove: false
            }).then(function() {
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

        // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
        // appropriately.
        this.listenTo(Iznik.Session, 'approvedcountschanged', this.fetch);
        this.fetch();
    }
});

Iznik.Views.ModTools.Message.Approved = IznikView.extend({
    template: 'modtools_approved_message',

    events: {
        'click .js-delete' : 'deleteMe'
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

            var mod = new Iznik.Models.ModTools.User(self.model.get('fromuser'));
            var v = new Iznik.Views.ModTools.User({
                model: mod
            });

            self.$('.js-user').html(v.render().el);

            var v = new Iznik.Views.ModTools.Message.Approved.Group({
                model: mod
            });
            self.$('.js-grouplist').append(v.render().el);

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
