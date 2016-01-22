Iznik.Views.ModTools.Pages.SpammerList = Iznik.Views.Page.extend({
    modtools: true,
    members: null,
    context: null,

    events: {
        'click .js-search': 'search',
        'keyup .js-searchterm': 'keyup'
    },

    template: "modtools_spammerlist_main",
    fetching: false,

    keyup: function(e) {
        // Search on enter.
        if (e.which == 13) {
            this.$('.js-search').click();
        }
    },

    search: function() {
        var term = this.$('.js-searchterm').val();

        if (term != '') {
            Router.navigate('/modtools/spammerlist/' + encodeURIComponent(term), true);
        } else {
            Router.navigate('/modtools/spammerlist', true);
        }
    },

    fetch: function() {
        var self = this;

        self.$('.js-none').hide();

        var search = self.$('.js-searchterm').val();

        var data = {
            context: self.context,
            search: search && search.length > 0 ? search: null
        };

        if (self.fetching) {
            // Already fetching
            return;
        }

        self.fetching = true;

        var v = new Iznik.Views.PleaseWait();
        v.render();

        this.spammers.fetch({
            data: data,
            remove: false
        }).then(function() {
            v.close();

            self.fetching = false;

            self.context = self.spammers.ret ? self.spammers.ret.context : null;

            if (self.spammers.length > 0) {
                // Peek into the underlying response to see if it returned anything and therefore whether it is
                // worth asking for more if we scroll that far.
                var gotsome = self.spammers.ret.spammers.length > 0;

                // Waypoints allow us to see when we have scrolled to the bottom.
                if (self.lastWaypoint) {
                    self.lastWaypoint.destroy();
                }

                if (gotsome) {
                    // We got some different members, so set up a scroll handler.  If we didn't get any different
                    // members, then there's no point - we could keep hitting the server with more requests
                    // and not getting any.
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

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        self.$('.js-searchterm').val(self.options.search);

        var v = new Iznik.Views.Help.Box();
        v.template = 'modtools_spammerlist_help';
        this.$('.js-help').html(v.render().el);

        self.spammers = new Iznik.Collections.ModTools.Spammers();

        // CollectionView handles adding/removing/sorting for us.
        self.collectionView = new Backbone.CollectionView( {
            el : self.$('.js-list'),
            modelView : Iznik.Views.ModTools.Spammer,
            modelViewOptions: {
                collection: self.spammers,
                page: self
            },
            collection: self.spammers
        } );

        self.collectionView.render();

        // Do so.
        self.fetch();

        // We seem to need to redelegate
        self.delegateEvents();
    }
});

Iznik.Views.ModTools.Spammer = Iznik.Views.ModTools.Member.extend({
    template: 'modtools_spammerlist_member',

    render: function() {
        var self = this;

        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        var mom = new moment(this.model.get('added'));
        this.$('.js-added').html(mom.format('ll'));

        var v = new Iznik.Views.ModTools.User({
            model: new Iznik.Models.ModTools.User(self.model.get('user'))
        });

        self.$('.js-user').html(v.render().el);

        // Add any other emails
        self.$('.js-otheremails').empty();
        var thisemail = self.model.get('user').email;
        _.each(self.model.get('user').otheremails, function(email) {
            if (email.email != thisemail) {
                var mod = new IznikModel(email);
                var v = new Iznik.Views.ModTools.Message.OtherEmail({
                    model: mod
                });
                self.$('.js-otheremails').append(v.render().el);
            }
        });

        self.$('.js-applied').empty();
        _.each(self.model.get('user').applied, function(group) {
            var mod = new IznikModel(group);
            var v = new Iznik.Views.ModTools.Member.Applied({
                model: mod
            });
            self.$('.js-applied').append(v.render().el);
        });


        this.$('.timeago').timeago();

        // If we approve, reject or ban this member then the view should go.
        this.listenToOnce(self.model, 'deleted removed', function() {
            self.$el.fadeOut('slow', function() {
                self.remove();
            });
        });

        return(this);
    }
});

