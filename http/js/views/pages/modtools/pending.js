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

    fetch: function() {
        this.msgs.fetch({
            data: {
                collection: 'Pending'
            }
        });
    },

    render: function() {
        Iznik.Views.Page.prototype.render.call(this);

        this.msgs = new Iznik.Collections.Message();

        // By setting up these listeners we will add and remove messages nicely from the view.
        this.listenTo(this.msgs, 'add', this.messageAdded);
        this.listenTo(this.msgs, 'remove', this.messageRemoved);

        // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
        // appropriately.
        this.listenTo(Iznik.Session, 'pendingcountschanged', this.fetch);
    }
});

Iznik.Views.ModTools.Message.Pending = IznikView.extend({
    template: 'modtools_pending_message',

    events: {
        'click .js-approve' : 'approve',
        'click .js-reject' : 'reject',
        'click .js-delete' : 'deleteMe'
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
        var v = new Iznik.Views.ModTools.Message.Pending.Reject({
            model: this.model
        });

        this.listenToOnce(v, 'rejected', function() {
            self.$el.fadeOut('slow', function() {
                self.remove();
            });
        });

        v.render();
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

            var v = new Iznik.Views.ModTools.Message.Pending.Group({
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
            })
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

Iznik.Views.ModTools.Message.Pending.Reject = Iznik.Views.Modal.extend({
    template: 'modtools_pending_reject',

    events: {
        'click .js-send': 'send'
    },

    send: function() {
        // We reject the message on all groups.  Future enhancement?
        var self= this;
        _.each(self.model.get('groups'), function(group, index, list) {
            $.ajax({
                type: 'POST',
                url: API + 'message',
                data: {
                    id: self.model.get('id'),
                    groupid: group.id,
                    action: 'Reject',
                    subject: 'Re: ' + self.model.get('subject'),
                    body: self.$('.js-text').val()
                }, success: function(ret) {
                    self.trigger('rejected');
                    self.close();
                }
            })
        });
    },

    render: function() {
        var self = this;

        this.$el.html(window.template(this.template)(this.model.toJSON2()));

        // Quote original message.
        var msg = this.model.get('textbody');
        msg = msg.replace(/(^|\r|\n|\r\n)/gm, '\r>');
        this.$('.js-text').val(msg);

        this.open(null);
        $('.modal').on('shown.bs.modal', function () {
            $('.modal .js-text').focus();
        });

        return(this);
    }
});


