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
        'click .js-posts': 'posts',
        'click .js-logs': 'logs'
    },

    posts: function() {
        var v = new Iznik.Views.ModTools.User.PostSummary({
            model: this.model,
            collection: this.collection
        });

        v.render();
    },

    logs: function() {
        var self = this;
        this.$('.js-logs').fadeTo('slow', 0.5);
        this.model.fetch({
            data: {
                logs: true
            }
        }).then(function() {
            var v = new Iznik.Views.ModTools.User.Logs({
                model: self.model
            });

            v.render();
            self.$('.js-logs').fadeTo('slow', 1);
        });
    },

    render: function() {
        var self = this;

        this.$el.html(window.template(this.template)());
        this.$('.js-msgcount').html(this.collection.length);

        if (this.collection.length == 0) {
            this.$('.js-msgcount').closest('.btn').addClass('disabled');
        }

        if (this.collection.length == 1) {
            this.$('.js-plural').hide();
            this.$('.js-singular').show();
        } else {
            this.$('.js-plural').show();
            this.$('.js-singular').hide();
        }

        var counts = {
            Offer: 0,
            Wanted: 0,
            Taken: 0,
            Received: 0,
            Other: 0
        };

        this.collection.each(function(message) {
            if (counts.hasOwnProperty(message.get('type'))) {
                counts[message.get('type')]++;
            }
        });

        _.each(counts, function(value, key, list) {
            self.$('.js-' + key.toLowerCase() + 'count').html(value);
        });

        return(this);
    }
});

Iznik.Views.ModTools.User.PostSummary = Iznik.Views.Modal.extend({
    template: 'modtools_user_postsummary',

    render: function() {
        var self = this;

        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        this.collection.each(function(message) {
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

Iznik.Views.ModTools.User.Logs = Iznik.Views.Modal.extend({
    template: 'modtools_user_logs',

    render: function() {
        var self = this;

        console.log("Logs model", this.model);
        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        var logs = this.model.get('logs');
        _.each(logs, function(log) {
            console.log("Got log", log);
            var v = new Iznik.Views.ModTools.User.LogEntry({
                model: new IznikModel(log)
            });
            self.$('.js-list').append(v.render().el);
        });

        this.open(null);

        return(this);
    }
});

Iznik.Views.ModTools.User.LogEntry = IznikView.extend({
    template: 'modtools_user_logentry',

    render: function() {
        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        var mom = new moment(this.model.get('timestamp'));
        this.$('.js-date').html(mom.format('DD-MMM-YY hh:mm'));
        return(this);
    }
});
