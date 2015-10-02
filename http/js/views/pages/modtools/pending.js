Iznik.Views.ModTools.Pages.Pending = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_pending_main",

    messageAdded: function(message) {
        var v = new Iznik.Views.ModTools.Message.Pending({
            model: message
        });

        this.$('.js-list').append(v.render().el);
        this.$('.timeago').timeago();

        this.$el.fadeIn('slow');
    },

    messageRemoved: function(message) {
        // Message removed from the collection.  Trigger an event to be picked up by views, to remove themselves.
        message.trigger('removed');
    },

    render: function() {
        Iznik.Views.Page.prototype.render.call(this);

        var msgs = new Iznik.Collections.Message();

        this.listenTo(msgs, 'add', this.messageAdded);
        this.listenTo(msgs, 'remove', this.messageRemoved);

        msgs.fetch({
            data: {
                collection: 'Pending'
            }
        });
    }
});

Iznik.Views.ModTools.Message.Pending = IznikView.extend({
    template: 'modtools_pending_message',

    events: {
        'click .js-approve' : 'approve',
        'click .js-reject' : 'reject',
        'click .js-delete' : 'delete'
    },

    approve: function() {
        var self = this;

        // We approve the message on all groups.  Future enhancement?
        _.each(self.model.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: self.model.get('id'),
                    groupid: group.id,
                    action: 'Approve'
                }, success: function(ret) {
                    self.$el.fadeOut('slow');
                }
            })
        });
    },

    reject: function() {
        var self = this;

        // We reject the message on all groups.  Future enhancement?
        _.each(self.model.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: self.model.get('id'),
                    groupid: group.id,
                    action: 'Reject',
                    subject: 'Test rejection',
                    body: 'Test body ' + self.model.get('textbody')
                }, success: function(ret) {
                    self.$el.fadeOut('slow');
                }
            })
        });
    },

    delete: function() {
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

            var v = new Iznik.Views.ModTools.Message.Pending.Group({
                model: mod
            });
            self.$('.js-grouplist').append(v.render().el);
        });

        // When this model is removed from the collection, it will have an event triggered on it. When that happens,
        // we want to remove this view.
        this.listenToOnce(this.model, 'removed', function() {
            self.$el.fadeOut('slow', function() {
                self.remove();
            });
        });

        return(this);
    }
});

Iznik.Views.ModTools.Message.Pending.Group = IznikView.extend({
    template: 'modtools_pending_group',

    render: function() {
        var self = this;
        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        return(this);
    }
});
