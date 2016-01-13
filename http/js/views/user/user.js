Iznik.Views.ModTools.User = IznikView.extend({
    template: 'modtools_user_user',

    events: {
        'click .js-posts': 'posts',
        'click .js-logs': 'logs',
        'click .js-remove': 'remove',
        'click .js-ban': 'ban'
    },


    posts: function() {
        var v = new Iznik.Views.ModTools.User.PostSummary({
            model: this.model,
            collection: this.historyColl
        });

        v.render();
    },

    logs: function() {
        var self = this;
        var v = new Iznik.Views.ModTools.User.Logs({
            model: self.model
        });

        v.render();
    },

    remove: function() {
        // Remove membership
        var self = this;

        var mod = new Iznik.Models.Membership({
            userid: this.model.get('id'),
            groupid: this.model.get('groupid')
        });

        mod.fetch().then(function() {
            mod.destroy({
                success: function(model, response) {
                    self.model.trigger('removed');
                }
            });
        });
    },

    ban: function() {
        // Ban them - remove with appropriate flag.
        var self = this;

        var mod = new Iznik.Models.Membership({
            userid: this.model.get('id'),
            groupid: this.model.get('groupid'),
            ban: true
        });

        mod.fetch().then(function() {
            mod.destroy({
                success: function(model, response) {
                    self.model.trigger('removed');
                }
            });
        });
    },

    render: function() {
        var self = this;
        this.$el.html(window.template(this.template)(this.model.toJSON2()));

        self.historyColl = new Iznik.Collections.ModTools.MessageHistory();
        _.each(this.model.get('messagehistory'), function(message, index, list) {
            self.historyColl.add(new Iznik.Models.ModTools.User.MessageHistoryEntry(message));
        });

        this.$('.js-msgcount').html(this.historyColl.length);

        if (this.historyColl.length == 0) {
            this.$('.js-msgcount').closest('.btn').addClass('disabled');
        }

        var v = new Iznik.Views.ModTools.User.History({
            model: this.model,
            collection: this.historyColl
        });

        this.$('.js-messagehistory').html(v.render().el);

        return (this);
    }
});

Iznik.Views.ModTools.User.History = IznikView.extend({
    template: 'modtools_user_history',

    render: function() {
        var self = this;

        this.$el.html(window.template(this.template)());

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

        var modcount = this.model.get('modmails');
        self.$('.js-modmailcount').html(modcount);

        if (modcount > 0) {
            self.$('.js-modmailcount').closest('.badge').addClass('btn-danger');
            self.$('.js-modmailcount').addClass('white');
            self.$('.glyphicon-warning-sign').addClass('white');
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

    context: null,

    events: {
        'click .js-more': 'more'
    },

    first: true,

    moreShown: false,
    more: function() {
        this.getChunk();
    },

    getChunk: function() {
        var self = this;

        this.model.fetch({
            data: {
                logs: true,
                logcontext: this.context
            },
            success: function(model, response, options) {
                self.logcontext = response.logcontext;
            }
        }).then(function() {
            var logs = self.model.get('logs');

            _.each(logs, function (log) {
                var v = new Iznik.Views.ModTools.User.LogEntry({
                    model: new IznikModel(log)
                });

                self.$('.js-list').append(v.render().el);

            });

            if (!self.moreShown) {
                self.moreShown = true;
            }

            console.log("Logs", logs);
            if (self.first && (_.isUndefined(logs) || logs.length == 0)) {
                self.$('.js-none').show();
            }

            self.first = false;
        });
    },

    render: function() {
        var self = this;

        this.$el.html(window.template(this.template)(this.model.toJSON2()));

        this.open(null);
        this.getChunk();

        return(this);
    }
});

Iznik.Views.ModTools.User.LogEntry = IznikView.extend({
    template: 'modtools_user_logentry',

    render: function() {
        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        var mom = new moment(this.model.get('timestamp'));
        this.$('.js-date').html(mom.format('DD-MMM-YY HH:mm'));
        return(this);
    }
});

Iznik.Views.ModTools.Member = IznikView.extend({
    rarelyUsed: function() {
        this.$('.js-rarelyused').fadeOut('slow');
        this.$('.js-stdmsgs li').fadeIn('slow');
    },

    addOtherEmails: function() {
        var self = this;
        var thisemail = self.model.get('email');

        // Add any other emails
        self.$('.js-otheremails').empty();
        _.each(self.model.get('otheremails'), function(email) {
            if (email.email != thisemail) {
                var mod = new IznikModel(email);
                var v = new Iznik.Views.ModTools.Message.OtherEmail({
                    model: mod
                });
                self.$('.js-otheremails').append(v.render().el);
            }
        });
    }
});

Iznik.Views.ModTools.Member.OtherEmail = IznikView.extend({
    template: 'modtools_member_otheremail'
});
