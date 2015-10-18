Iznik.Views.ModTools.Pages.Spam = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_spam_main",

    render: function() {
        Iznik.Views.Page.prototype.render.call(this);

        self.msgs = new Iznik.Collections.Message();
        self.$('.js-none').hide();

        self.msgs.fetch({
            data: {
                collection: 'Spam'
            }
        }).then(function() {
            if (self.msgs.length == 0) {
                self.$('.js-none').fadeIn('slow');
            } else {
                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.Message.Spam,
                    collection: self.msgs
                });

                self.collectionView.render();
            }
        });
    }
});

Iznik.Views.ModTools.Message.Spam = IznikView.extend({
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
