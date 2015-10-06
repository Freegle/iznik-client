Iznik.Views.ModTools.User = IznikView.extend({
    template: 'modtools_user_user',

    render: function() {
        this.$el.html(window.template(this.template)(this.model.toJSON2()));

        var coll = new Iznik.Collections.ModTools.MessageHistory();
        _.each(this.model.get('messagehistory'), function(message, index, list) {
            coll.add(new Iznik.Models.ModTools.User.MessageHistoryEntry(message));
        });

        var v = new Iznik.Views.ModTools.User.History({
            collection: coll,
            model: this.model
        });
        this.$('.js-messagehistory').html(v.render().el);

        return (this);
    }
});

Iznik.Views.ModTools.User.History = IznikView.extend({
    template: 'modtools_user_history',

    events: {
        'click .js-view': 'view'
    },

    view: function() {
        var v = new Iznik.Views.ModTools.User.PostSummary({
            model: this.model,
            collection: this.collection
        });

        v.render();
    },

    render: function() {
        var self = this;

        this.$el.html(window.template(this.template)());
        this.$('.js-msgcount').html(this.collection.length);

        if (this.collection.length == 1) {
            this.$('.js-plural').hide();
            this.$('.js-singular').show();
        } else {
            this.$('.js-plural').show();
            this.$('.js-singular').hide();
        }

        return(this);
    }
});

Iznik.Views.ModTools.User.PostSummary = Iznik.Views.Modal.extend({
    template: 'modtools_user_postsummary',

    render: function() {
        var self = this;

        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        this.collection.each(function(message) {
            console.log("Details message",message);
            var v = new Iznik.Views.ModTools.User.SummaryEntry({
                model: message
            });
            self.$('.js-list').append(v.render().el);
        });

        this.open(null);

        return(this);
    }
});

Iznik.Views.ModTools.User.SummaryEntry = IznikView.extend({
    template: 'modtools_user_summaryentry',

    render: function() {
        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        var mom = new moment(this.model.get('arrival'));
        this.$('.js-date').html(mom.format('llll'));
        return(this);
    }
});