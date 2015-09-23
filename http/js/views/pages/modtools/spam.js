Iznik.Views.ModTools.Pages.Spam = Iznik.Views.Page.extend({
    modtools: true,

    template: "modtools_spam_main",

    messageAdded: function(message) {
        var v = new Iznik.Views.ModTools.Message.Spam({
            model: message
        });

        var el = v.render().el;
        this.$('.js-list').append(el);
        this.$('.timeago').timeago();

        $(el).fadeIn('slow');
    },

    render: function() {
        Iznik.Views.Page.prototype.render.call(this);

        var msgs = new Iznik.Collections.Message();

        this.listenTo(msgs, 'add', this.messageAdded);

        msgs.fetch({
            data: {
                collection: 'messages_spam'
            }
        });
    }
});

Iznik.Views.ModTools.Message.Spam = IznikView.extend({
    template: 'modtools_spam_message',

    render: function() {
        // We overwrite this.el so that we can avoid the wrapping element.
        var html = window.template(this.template)(this.model.toJSON2());
        console.log("Expanded to", html);
        this.el = html;
        this.delegateEvents(this.events);
        return(this);
    }
});
