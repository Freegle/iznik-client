Iznik.Views.ModTools.Pages.SpamMembers = Iznik.Views.Page.extend({
    modtools: true,
    members: null,
    context: null,

    template: "modtools_members_spam_main",
    fetching: false,

    fetch: function() {
        var self = this;

        self.$('.js-none').hide();

        var data = {
            context: self.context
        };

        if (self.fetching) {
            // Already fetching
            return;
        }

        self.fetching = true;

        var v = new Iznik.Views.PleaseWait();
        v.render();

        this.members.fetch({
            data: data,
            remove: false
        }).then(function() {
            v.close();

            self.fetching = false;

            self.context = self.members.ret ? self.members.ret.context : null;

            if (self.members.length > 0) {
                // Peek into the underlying response to see if it returned anything and therefore whether it is
                // worth asking for more if we scroll that far.
                var gotsome = self.members.ret.members.length > 0;

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

        var v = new Iznik.Views.Help.Box();
        v.template = 'modtools_members_spam_help';
        this.$('.js-help').html(v.render().el);

        self.members = new Iznik.Collections.Members(null, {
            collection: 'Spam'
        });

        // CollectionView handles adding/removing/sorting for us.
        self.collectionView = new Backbone.CollectionView( {
            el : self.$('.js-list'),
            modelView : Iznik.Views.ModTools.Member.Spam,
            modelViewOptions: {
                collection: self.members,
                page: self
            },
            collection: self.members
        } );

        self.collectionView.render();

        // Do so.
        self.fetch();

        // If we detect that the pending counts have changed on the server, refetch the members so that we add/remove
        // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
        this.listenTo(Iznik.Session, 'spammembercountschanged', this.fetch);

        // We seem to need to redelegate
        self.delegateEvents();
    }
});

Iznik.Views.ModTools.Member.Spam = Iznik.Views.ModTools.Member.extend({
    template: 'modtools_members_spam_member',

    events: {
        'click .js-notspam': 'notSpam',
        'click .js-spam': 'spam',
        'click .js-whitelist': 'whitelist'
    },

    clearSuspect: function() {
        var self = this;

        var mod = new Iznik.Models.ModTools.User({
            id: self.model.get('userid')
        });

        $.ajax({
            url: API + 'user/' + self.model.get('userid'),
            type: 'PATCH',
            data: {
                'suspectcount': 0,
                'suspectreason': null
            }, success: function(ret) {
                self.$el.fadeOut('slow', function() {
                    self.remove();
                })
            }
        });
    },

    notSpam: function() {
        // Record that this member isn't suspicious.  That will stop the server returning them to us.
        this.clearSuspect();
    },

    spam: function() {
        var self = this;

        var v = new Iznik.Views.ModTools.EnterReason();
        self.listenToOnce(v, 'reason', function(reason) {
            $.ajax({
                url: API + 'spammers',
                type: 'POST',
                data: {
                    userid: self.model.get('userid'),
                    reason: reason,
                    collection: 'PendingAdd'
                }, success: function(ret) {
                    // Now over to someone else to review this report - so remove from our list.
                    self.clearSuspect();
                }
            });
        });

        v.render();
    },

    whitelist: function() {
        var self = this;

        var v = new Iznik.Views.ModTools.EnterReason();
        self.listenToOnce(v, 'reason', function(reason) {
            $.ajax({
                url: API + 'spammers',
                type: 'POST',
                data: {
                    userid: self.model.get('userid'),
                    reason: reason,
                    collection: 'Whitelisted'
                }, success: function(ret) {
                    // Now over to someone else to review this report - so remove from our list.
                    self.clearSuspect();
                }
            });
        });

        v.render();
    },

    render: function() {
        var self = this;

        self.model.set('group', Iznik.Session.getGroup(self.model.get('groupid')).attributes);
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        if (Iznik.Session.isAdmin()) {
            self.$('.js-whitelist').show();
        }

        var mom = new moment(this.model.get('joined'));
        this.$('.js-joined').html(mom.format('llll'));

        self.addOtherInfo();

        // Get the group from the session
        var group = Iznik.Session.getGroup(self.model.get('groupid'));

        // Our user
        var v = new Iznik.Views.ModTools.User({
            model: self.model
        });

        self.$('.js-user').html(v.render().el);

        // No remove/ban/spam buttons as we have our own.
        self.$('.js-remove, .js-ban, .js-spammer').closest('li').hide();

        // Delay getting the Yahoo info slightly to improve apparent render speed.
        _.delay(function() {
            // The Yahoo part of the user
            var mod = IznikYahooUsers.findUser({
                email: self.model.get('email'),
                group: group.get('nameshort'),
                groupid: group.get('id')
            });

            mod.fetch().then(function() {
                // We don't want to show the Yahoo joined date because we have our own.
                mod.clear('date');
                var v = new Iznik.Views.ModTools.Yahoo.User({
                    model: mod
                });
                self.$('.js-yahoo').append(v.render().el);
            });
        }, 200);

        this.$('.timeago').timeago();

        // If we approve, reject or ban this member then the view should go.
        this.listenToOnce(self.model, 'deleted removed rejected approved', function() {
            self.$el.fadeOut('slow', function() {
                self.remove();
            });
        });

        return(this);
    }
});

Iznik.Views.ModTools.EnterReason = Iznik.Views.Modal.extend({
    template: 'modtools_members_spam_reason',

    events: {
        'click .js-cancel': 'close',
        'click .js-confirm': 'confirm'
    },

    confirm: function() {
        var self = this;
        var reason = self.$('.js-reason').val();

        if (reason.length < 3) {
            self.$('.js-reason').focus();
        } else {
            self.trigger('reason', reason);
            self.close();
        }
    },

    render: function() {
        var self = this;
        this.open(this.template);

        return(this);
    }
});

