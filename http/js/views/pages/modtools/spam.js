Iznik.Views.ModTools.Pages.Spam = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_spam_main",

    fetch: function() {
        var self = this;
        self.$('.js-none').hide();

        var data = {
            collection: 'Spam'
        };

        if (self.selected > 0) {
            data.groupid = self.selected;
        }

        // For spam messages we don't do paging so put a high limit.
        data.limit = 100;

        this.msgs.fetch({
            data: data,
            remove: true
        }).then(function() {
            self.lastFetched = self.selected;

            if (self.msgs.length == 0) {
                self.$('.js-none').fadeIn('slow');
            } else {
                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView( {
                    el : self.$('.js-list'),
                    modelView : Iznik.Views.ModTools.Message.Spam,
                    collection : self.msgs
                } );

                self.collectionView.render();

                // Unfortunately collectionView doesn't have an event for when the length changes.
                function checkLength(self) {
                    var lastlen = 0;
                    return(function() {
                        if (lastlen != self.collectionView.length) {
                            lastlen = self.collectionView.length;
                            if (self.collectionView.length == 0) {
                                self.$('.js-none').fadeIn('slow');
                            } else {
                                self.$('.js-none').hide();
                            }
                        }

                        window.setTimeout(checkLength, 2000);
                    });
                }

                window.setTimeout(checkLength, 2000);
            }
        });
    },

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        var v = new Iznik.Views.Help.Box();
        v.template = 'modtools_spam_info';
        this.$('.js-help').html(v.render().el);

        self.msgs = new Iznik.Collections.Message();

        this.groupSelect = new Iznik.Views.Group.Select({
            systemWide: false,
            all: true,
            mod: true,
            counts: [ 'spam', 'spamother' ],
            id: 'spamGroupSelect'
        });

        self.listenTo(this.groupSelect, 'selected', function(selected) {
            self.selected = selected;
            self.fetch();
        });

        // Render after the listen to as they are called during render.
        self.$('.js-groupselect').html(self.groupSelect.render().el);

        // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
        // appropriately.
        this.listenTo(Iznik.Session, 'spamcountschanged', _.bind(this.fetch, this));
        this.listenTo(Iznik.Session, 'spamcountschanged', _.bind(this.groupSelect.render, this.groupSelect));
        this.listenTo(Iznik.Session, 'spamcountsotherchanged', _.bind(this.groupSelect.render, this.groupSelect));
    }
});

Iznik.Views.ModTools.Message.Spam = Iznik.Views.ModTools.Message.extend({
    template: 'modtools_spam_message',

    events: {
        'click .js-notspam': 'notspam',
        'click .js-spam': 'spam'
    },

    notspam: function() {
        var self = this;
        _.each(self.model.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: self.model.get('id'),
                    groupid: group.id,
                    action: 'NotSpam'
                }, success: function (ret) {
                    self.$el.fadeOut('slow');
                }
            });
        });
    },

    spam: function() {
        var self = this;
        _.each(self.model.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: self.model.get('id'),
                    groupid: group.id,
                    action: 'Delete',
                    reason: 'Deleted as spam'
                }, success: function (ret) {
                    self.$el.fadeOut('slow');
                }
            });
        });
    },

    render: function() {
        var self = this;

        self.$el.html(window.template(self.template)(self.model.toJSON2()));
        _.each(self.model.get('groups'), function(group, index, list) {
            var mod = new IznikModel(group);

            // Add in the message, because we need some values from that
            mod.set('message', self.model.toJSON());

            var v = new Iznik.Views.ModTools.Message.Spam.Group({
                model: mod
            });
            self.$('.js-grouplist').append(v.render().el);
        });

        this.$('.timeago').timeago();
        this.$el.fadeIn('slow');

        return(this);
    }
});

Iznik.Views.ModTools.Message.Spam.Group = IznikView.extend({
    template: 'modtools_spam_group',

    render: function() {
        var self = this;
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        return(this);
    }
});
