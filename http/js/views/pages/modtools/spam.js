Iznik.Views.ModTools.Pages.Spam = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_spam_main",

    messageAdded: function(message) {
        var v = new Iznik.Views.ModTools.Message.Spam({
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
                collection: 'Spam'
            }
        });
    }
});

Iznik.Views.ModTools.Message.Spam = IznikView.extend({
    template: 'modtools_spam_message',

    render: function() {
        var self = this;

        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        // When this model is removed from the collection, it will have an event triggered on it. When that happens,
        // we want to remove this view.
        this.listenToOnce(this.model, 'removed', function() {
            console.log("Spam remove", self.$el);

            self.$el.fadeOut('slow', function() {
                self.remove();
            });
        });

        return(this);
    }
});
