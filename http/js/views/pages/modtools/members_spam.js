Iznik.Views.ModTools.Pages.SpamMembers = Iznik.Views.Infinite.extend({
    modtools: true,

    template: "modtools_members_spam_main",

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        var v = new Iznik.Views.Help.Box();
        v.template = 'modtools_members_spam_help';
        this.$('.js-help').html(v.render().el);

        self.groupSelect = new Iznik.Views.Group.Select({
            systemWide: false,
            all: true,
            mod: true,
            counts: [ 'spammembers' ],
            id: 'spamGroupSelect'
        });

        self.listenTo(self.groupSelect, 'selected', function(selected) {
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
            self.collectionView = new Backbone.CollectionView( {
                el : self.$('.js-list'),
                modelView : Iznik.Views.ModTools.Member.Spam,
                modelViewOptions: {
                    collection: self.collection,
                    page: self
                },
                collection: self.collection
            } );

            self.collectionView.render();
            self.fetch();
        });

        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(self.groupSelect.render().el);

        // If we detect that the pending counts have changed on the server, refetch the members so that we add/remove
        // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
        this.listenTo(Iznik.Session, 'approvedmemberscountschanged', _.bind(this.groupSelect.render, this.groupSelect));
        this.listenTo(Iznik.Session, 'approvedmembersothercountschanged', _.bind(this.groupSelect.render, this.groupSelect));

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
            type: 'POST',
            headers: {
                'X-HTTP-Method-Override': 'PATCH'
            },
            data: {
                'suspectcount': 0,
                'suspectreason': null,
                'groupid' : self.model.get('groupid')
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

        // Our user.  In memberships the id is that of the member, so we need to get the userid.
        var mod = self.model.clone();
        mod.set('id', self.model.get('userid'));
        var v = new Iznik.Views.ModTools.User({
            model: mod
        });

        self.$('.js-user').html(v.render().el);

        // No report spammer button here.
        //
        // Auto remove and ban may be turned off, so leave those buttons.
        self.$('.js-spammer').closest('li').hide();

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
                mod.unset('date');
                var v = new Iznik.Views.ModTools.Yahoo.User({
                    model: mod
                });
                self.$('.js-yahoo').append(v.render().el);
            });
        }, 200);

        this.$('.timeago').timeago();

        this.listenToOnce(self.model, 'deleted removed rejected approved', function() {
            self.$el.fadeOut('slow');
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

