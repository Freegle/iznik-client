Iznik.Views.ModTools.Pages.ApprovedMessages = Iznik.Views.Page.extend({
    modtools: true,
    search: false,
    context: null,

    template: "modtools_messages_approved_main",

    events: {
        'click .js-search': 'search',
        'keyup .js-searchterm': 'keyup'
    },

    fetching: null,
    start: null,
    startdate: null,

    keyup: function(e) {
        // Search on enter.
        if (e.which == 13) {
            this.$('.js-search').click();
        }
    },

    fetch: function(start) {
        var self = this;

        self.$('.js-none').hide();

        var data = {
            collection: 'Approved'
        };

        if (self.selected > 0) {
            // Specific group
            data.groupid = self.selected;
        }

        if (self.options.search) {
            // We're searching.  Pass any previous search results context so that we get the next set of results.
            if (self.msgs.ret) {
                data.context = self.msgs.ret.context;
            }
        } else {
            // We're not searching. We page using the date.
            data.start = self.startdate;
        }

        // Fetch more messages - and leave the old ones in the collection
        if (self.fetching == self.selected) {
            // Already fetching the right group.
            return;
        } else {
            self.fetching = self.selected;
        }

        var v = new Iznik.Views.PleaseWait();
        v.render();

        this.msgs.fetch({
            data: data,
            remove: self.selected != self.lastFetched
        }).then(function() {
            v.close();

            self.fetching = null;
            self.lastFetched = self.selected;

            if (!self.start) {
                self.$('.js-none').fadeIn('slow');
            }

            if (self.msgs.length > 0) {
                var gotsome = false;

                self.msgs.each(function(msg) {
                    //console.log("Fetched", msg.get('id'), msg.get('date'));
                    var thisone = (new Date(msg.get('date'))).getTime();
                    if (self.start == null || thisone < self.start) {
                        self.start = thisone;
                        self.startdate = msg.get('date');
                        gotsome = true;
                    }
                });

                // Waypoints allow us to see when we have scrolled to the bottom.
                if (self.lastWaypoint) {
                    self.lastWaypoint.destroy();
                }

                if (gotsome) {
                    // We got some different messages, so set up a scroll handler.  If we didn't get any different
                    // messages, then there's no point - we could keep hitting the server with more requests
                    // and not getting any.
                    var vm = self.collectionView.viewManager;
                    var lastView = vm.last();

                    if (lastView) {
                        self.lastMessage = lastView;
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
            }
        });
    },

    search: function() {
        var term = this.$('.js-searchterm').val();

        if (term != '') {
            Router.navigate('/modtools/messages/approved/' + encodeURIComponent(term), true);
        } else {
            Router.navigate('/modtools/messages/approved', true);
        }
    },

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        // The type of collection we're using depends on whether we're searching.  It controls how we fetch.
        if (self.options.search) {
            self.msgs = new Iznik.Collections.Messages.Search(null, {
                search: self.options.search
            });

            self.$('.js-searchterm').val(self.options.search);
        } else {
            self.msgs = new Iznik.Collections.Message();
        }

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

            // We haven't fetched anything for this group yet.
            self.lastFetched = null;

            // Do so.
            self.fetch();
        });

        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(self.groupSelect.render().el);

        // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
        // appropriately.
        this.listenTo(Iznik.Session, 'approvedcountschanged', _.bind(this.fetch, this));
        this.listenTo(Iznik.Session, 'approvedcountschanged', _.bind(this.groupSelect.render, this.groupSelect));
        this.listenTo(Iznik.Session, 'approvedothercountschanged', _.bind(this.groupSelect.render, this.groupSelect));

        // We seem to need to redelegate
        self.delegateEvents();
    }
});

Iznik.Views.ModTools.Message.Approved = Iznik.Views.ModTools.Message.extend({
    template: 'modtools_messages_approved_message',

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
        self.model.set('mapicon', window.location.protocol + '//' + window.location.hostname + '/images/mapmarker.gif');

        // Get a zoom level for the map.
        _.each(self.model.get('groups'), function(group) {
            self.model.set('mapzoom', group.settings.hasOwnProperty('map') ? group.settings.map.zoom : 12);
        });

        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        // We handle the subject as a special case rather than a template expansion.  We might be doing a search, in
        // which case we want to highlight the matched words.  So we split out the subject string into a sequence of
        // spans, which then allows us to highlight any matched ones.
        self.$('.js-subject').html(self.wordify(self.model.get('subject')));
        var matched = self.model.get('matchedon');
        if (matched) {
            self.$('.js-subject span').each(function() {
                if ($(this).html().toLowerCase().indexOf(matched.word) != -1) {
                    $(this).addClass('searchmatch');
                }
            });
        }

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
                self.showRelated();

                // Add the other standard messages, in the order requested.
                var sortmsgs = orderedMessages(config.get('stdmsgs'), config.get('messageorder'));
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
        });

        // Add any attachments.
        _.each(self.model.get('attachments'), function(att) {
            var v = new Iznik.Views.ModTools.Message.Photo({
                model: new IznikModel(att)
            });

            self.$('.js-attlist').append(v.render().el);
        });

        this.$('.timeago').timeago();

        // If we reject, approve or delete this message then the view should go.
        this.listenToOnce(self.model, 'deleted', function() {
            self.$el.fadeOut('slow', function() {
                self.remove();
            });
        });

        return(this);
    }
});

Iznik.Views.ModTools.Message.Approved.Group = IznikView.extend({
    template: 'modtools_messages_approved_group',

    render: function() {
        var self = this;
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        return(this);
    }
});
