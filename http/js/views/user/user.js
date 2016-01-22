Iznik.Views.ModTools.User = IznikView.extend({
    template: 'modtools_user_user',

    events: {
        'click .js-posts': 'posts',
        'click .js-logs': 'logs',
        'click .js-remove': 'remove',
        'click .js-ban': 'ban',
        'click .js-addcomment': 'addComment',
        'click .js-spammer': 'spammer'
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

    spammer: function() {
        var self = this;
        var v = new Iznik.Views.ModTools.EnterReason();
        self.listenToOnce(v, 'reason', function(reason) {
            $.ajax({
                url: API + 'spammers',
                type: 'POST',
                data: {
                    userid: self.model.get('id'),
                    reason: reason,
                    collection: 'PendingAdd'
                }, success: function(ret) {
                    (new Iznik.Views.ModTools.User.Reported().render());
                }
            });
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

    addComment: function() {
        var self = this;

        var model = new Iznik.Models.ModTools.User.Comment({
            userid: this.model.get('id'),
            groupid: this.model.get('groupid')
        });

        var v = new Iznik.Views.ModTools.User.CommentModal({
            model: model
        });

        // When we close, update what's shown.
        this.listenToOnce(v, 'modalClosed', function() {
            self.model.fetch().then(function() {
                self.render()
            });
        });

        v.render();
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

        _.each(this.model.get('comments'), function(comment) {
            self.$('.js-comments').append((new Iznik.Views.ModTools.User.Comment({
                model: new Iznik.Models.ModTools.User.Comment(comment)
            })).render().el);
        });

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

Iznik.Views.ModTools.User.Reported = Iznik.Views.Modal.extend({
    template: 'modtools_user_reported'
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

    addOtherInfo: function() {
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

        // Add any other group memberships we need to display.
        self.$('.js-memberof').empty();
        var groupids = [ self.model.get('groupid') ];
        _.each(self.model.get('memberof'), function(group) {
            if (groupids.indexOf(group.id) == -1) {
                var mod = new IznikModel(group);
                var v = new Iznik.Views.ModTools.Member.Of({
                    model: mod
                });
                self.$('.js-memberof').append(v.render().el);
                groupids.push(group.id);
            }
        });

        self.$('.js-applied').empty();
        _.each(self.model.get('applied'), function(group) {
            if (groupids.indexOf(group.id) == -1) {
                // Don't both displaying applications to groups we've just listed as them being a member of.
                var mod = new IznikModel(group);
                var v = new Iznik.Views.ModTools.Member.Applied({
                    model: mod
                });
                self.$('.js-applied').append(v.render().el);
            }
        });
    }
});

Iznik.Views.ModTools.Member.OtherEmail = IznikView.extend({
    template: 'modtools_member_otheremail'
});

Iznik.Views.ModTools.Member.Of = IznikView.extend({
    template: 'modtools_member_of'
});

Iznik.Views.ModTools.Member.Applied = IznikView.extend({
    template: 'modtools_member_applied'
});

Iznik.Views.ModTools.User.Comment = IznikView.extend({
    template: 'modtools_user_comment',

    events: {
        'click .js-edit': 'edit',
        'click .js-delete': 'deleteMe'
    },

    edit: function() {
        var v = new Iznik.Views.ModTools.User.CommentModal({
            model: this.model
        });

        this.listenToOnce(v, 'modalClosed', this.render);

        v.render();
    },

    deleteMe: function() {
        this.model.destroy().then(this.remove());
    },

    render: function() {
        this.$el.html(window.template(this.template)(this.model.toJSON2()));
        this.$('.timeago').timeago();
        return(this);
    }
});

Iznik.Views.ModTools.User.CommentModal = Iznik.Views.Modal.extend({
    template: 'modtools_user_comment_modal',

    events: {
        'click .js-save': 'save'
    },

    save: function() {
        var self = this;

        self.model.save().then(function() {
            self.close();
        });
    },

    render2: function() {
        var self = this;

        self.open(null);

        self.fields = [
            {
                name: 'user1',
                control: 'input',
                placeholder: 'Add a comment about this member here'
            },
            {
                name: 'user2',
                control: 'input',
                placeholder: '...and more information here'
            },
            {
                name: 'user3',
                control: 'input',
                placeholder: '...and here'

            },
            {
                name: 'user4',
                control: 'input',
                placeholder: 'You get the idea.'
            },
            {
                name: 'user5',
                control: 'input'
            },
            {
                name: 'user6',
                control: 'input'
            },
            {
                name: 'user7',
                control: 'input'
            },
            {
                name: 'user8',
                control: 'input'
            },
            {
                name: 'user9',
                control: 'input'
            },
            {
                name: 'user10',
                control: 'input'
            },
            {
                name: 'user11',
                control: 'input'
            }
        ];

        self.form = new Backform.Form({
            el: $('#js-form'),
            model: self.model,
            fields: self.fields
        });

        self.form.render();

        // Make it full width.
        self.$('label').remove();
        self.$('.col-sm-8').removeClass('col-sm-8').addClass('col-sm-12');

        // Layout messes up a bit.
        self.$('.form-group').addClass('clearfix');

        // Turn on spell-checking
        self.$('textarea, input:text').attr('spellcheck', true);
    },

    render: function() {
        var self = this;

        this.$el.html(window.template(this.template)(this.model.toJSON2()));

        if (self.model.get('id')) {
            // We want to refetch the model to make sure we edit the most up to date settings.
            self.model.fetch().then(self.render2.call(self));
        } else {
            // We're adding one; just render it.
            self.render2();
        }

        return(this);
    }
});
