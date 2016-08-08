define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    'iznik/views/modal',
    'bootstrap-switch',
    'bootstrap-datepicker'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.User = Iznik.View.extend({
        template: 'modtools_user_user',

        events: {
            'click .js-posts': 'posts',
            'click .js-offers': 'offers',
            'click .js-takens': 'takens',
            'click .js-wanteds': 'wanteds',
            'click .js-receiveds': 'receiveds',
            'click .js-modmails': 'modmails',
            'click .js-others': 'others',
            'click .js-logs': 'logs',
            'click .js-remove': 'remove',
            'click .js-ban': 'ban',
            'click .js-purge': 'purge',
            'click .js-addcomment': 'addComment',
            'click .js-spammer': 'spammer',
            'click .js-whitelist': 'whitelist'
        },

        showPosts: function(offers, wanteds, takens, receiveds, others) {
            var v = new Iznik.Views.ModTools.User.PostSummary({
                model: this.model,
                collection: this.historyColl,
                offers: offers,
                wanteds: wanteds,
                takens: takens,
                receiveds: receiveds,
                others: others
            });

            v.render();
        },

        posts: function() {
            this.showPosts(true, true, true, true, true);
        },

        offers: function() {
            this.showPosts(true, false, false, false, false);
        },

        wanteds: function() {
            this.showPosts(false, true, false, false, false);
        },

        takens: function() {
            this.showPosts(false, false, true, false, false);
        },

        receiveds: function() {
            this.showPosts(false, false, false, true, false);
        },

        others: function() {
            this.showPosts(false, false, false, false, true);
        },

        modmails: function() {
            var self = this;
            var v = new Iznik.Views.ModTools.User.ModMails({
                model: self.model,
                modmailsonly: true
            });

            v.render();
        },

        whitelist: function() {
            var self = this;

            var v = new Iznik.Views.ModTools.EnterReason();
            self.listenToOnce(v, 'reason', function(reason) {
                $.ajax({
                    url: API + 'spammers',
                    type: 'POST',
                    data: {
                        userid: self.model.get('id'),
                        reason: reason,
                        collection: 'Whitelisted'
                    }, success: function(ret) {
                        // Now over to someone else to review this report - so remove from our list.
                        self.clearSuspect();
                    }
                });
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

            var v = new Iznik.Views.Confirm({
                model: self.model
            });
            v.template = 'modtools_members_removeconfirm';

            self.listenToOnce(v, 'confirmed', function() {
                $.ajax({
                    url: API + 'memberships',
                    type: 'DELETE',
                    data: {
                        userid: self.model.get('id'),
                        groupid: self.model.get('groupid')
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            self.$el.fadeOut('slow');
                            self.model.trigger('removed');
                        }
                    }
                });
            });

            v.render();
        },

        ban: function() {
            // Ban them - remove with appropriate flag.
            var self = this;

            var v = new Iznik.Views.Confirm({
                model: self.model
            });
            v.template = 'modtools_members_banconfirm';

            self.listenToOnce(v, 'confirmed', function() {
                $.ajax({
                    url: API + 'memberships',
                    type: 'DELETE',
                    data: {
                        userid: self.model.get('id'),
                        groupid: self.model.get('groupid'),
                        ban: true
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            self.$el.fadeOut('slow');
                            self.model.trigger('removed');
                        }
                    }
                });
            });

            v.render();
        },
            
        purge: function() {
            var self = this;
            var v = new Iznik.Views.Confirm({
                model: self.model
            });
            v.template = 'modtools_members_purgeconfirm';

            self.listenToOnce(v, 'confirmed', function() {
                $.ajax({
                    url: API + 'user',
                    type: 'DELETE',
                    data: {
                        id: self.model.get('userid')
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            self.$el.fadeOut('slow');
                            self.model.trigger('removed');
                        }
                    }
                });
            });

            v.render();
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
                    self.render();
                });
            });

            v.render();
        },

        render: function() {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.historyColl = new Iznik.Collections.ModTools.MessageHistory();
                _.each(self.model.get('messagehistory'), function (message, index, list) {
                    self.historyColl.add(new Iznik.Models.ModTools.User.MessageHistoryEntry(message));
                });

                self.$('.js-msgcount').html(self.historyColl.length);

                if (self.historyColl.length == 0) {
                    self.$('.js-msgcount').closest('.btn').addClass('disabled');
                }

                var counts = {
                    Offer: 0,
                    Wanted: 0,
                    Taken: 0,
                    Received: 0,
                    Other: 0
                };

                self.historyColl.each(function (message) {
                    if (counts.hasOwnProperty(message.get('type'))) {
                        counts[message.get('type')]++;
                    }
                });

                _.each(counts, function (value, key, list) {
                    self.$('.js-' + key.toLowerCase() + 'count').html(value);
                });

                var modcount = self.model.get('modmails');
                self.$('.js-modmailcount').html(modcount);

                if (modcount > 0) {
                    self.$('.js-modmailcount').closest('.badge').addClass('btn-danger');
                    self.$('.js-modmailcount').addClass('white');
                    self.$('.glyphicon-warning-sign').addClass('white');
                }

                var comments = self.model.get('comments');
                _.each(comments, function (comment) {
                    if (comment.groupid) {
                        comment.group = Iznik.Session.getGroup(comment.groupid).toJSON2();
                    }

                    new Iznik.Views.ModTools.User.Comment({
                        model: new Iznik.Models.ModTools.User.Comment(comment)
                    }).render().then(function (v) {
                        self.$('.js-comments').append(v.el);
                    });
                });

                if (!comments || comments.length == 0) {
                    self.$('.js-comments').hide();
                }

                var spammer = self.model.get('spammer');
                if (spammer) {
                    var v = new Iznik.Views.ModTools.User.SpammerInfo({
                        model: new Iznik.Model(spammer)
                    });

                    v.render().then(function (v) {
                        self.$('.js-spammerinfo').append(v.el);
                    });
                }

                if (Iznik.Session.isAdmin()) {
                    self.$('.js-adminonly').removeClass('hidden');
                }
            });

            return (p);
        }
    });

    Iznik.Views.ModTools.User.PostSummary = Iznik.Views.Modal.extend({
        template: 'modtools_user_postsummary',

        render: function() {
            var p = Iznik.Views.Modal.prototype.render.call(this);
            p.then(function(self) {
                self.collection.each(function (message) {
                    var type = message.get('type');
                    var display = false;

                    switch (type) {
                        case 'Offer':
                            display = self.options.offers;
                            break;
                        case 'Wanted':
                            display = self.options.wanteds;
                            break;
                        case 'Taken':
                            display = self.options.takens;
                            break;
                        case 'Received':
                            display = self.options.receiveds;
                            break;
                        case 'Other':
                            display = self.options.others;
                            break;
                    }

                    if (display) {
                        var v = new Iznik.Views.ModTools.User.SummaryEntry({
                            model: message
                        });
                        v.render().then(function (v) {
                            self.$('.js-list').append(v.el);
                        });
                    }
                });

                self.open(null);
            })

            return(p);
        }
    });

    Iznik.Views.ModTools.User.SummaryEntry = Iznik.View.extend({
        template: 'modtools_user_summaryentry',

        render: function() {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                var mom = new moment(self.model.get('date'));
                self.$('.js-date').html(mom.format('llll'));
            });
            return(p);
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

        addLog: function(log) {
            var self = this;

            var v = new Iznik.Views.ModTools.User.LogEntry({
                model: new Iznik.Model(log)
            });

            v.render().then(function(v) {
                self.$('.js-list').append(v.el);
            });
        },

        getChunk: function() {
            var self = this;

            this.model.fetch({
                data: {
                    logs: true,
                    modmailsonly: self.options.modmailsonly,
                    logcontext: this.logcontext
                },
                success: function(model, response, options) {
                    self.logcontext = response.logcontext;

                    // TODO This can't be right.
                    if ((response.hasOwnProperty('user') && response.user.logs.length > 0) ||
                        (response.hasOwnProperty('member') && response.member.logs.length > 0)) {
                        self.$('.js-more').show();
                    }
                }
            }).then(function() {
                self.$('.js-loading').hide();
                var logs = self.model.get('logs');

                _.each(logs, function (log) {
                    self.addLog(log);
                });

                if (!self.moreShown) {
                    self.moreShown = true;
                }

                if (self.first && (_.isUndefined(logs) || logs.length == 0)) {
                    self.$('.js-none').show();
                }

                self.first = false;
            });
        },

        render: function() {
            var p = Iznik.Views.Modal.prototype.render.call(this);
            p.then(function(self) {
                self.open(null);
                self.getChunk();
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.User.LogEntry = Iznik.View.extend({
        template: 'modtools_user_logentry',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                var mom = new moment(self.model.get('timestamp'));
                self.$('.js-date').html(mom.format('DD-MMM-YY HH:mm'));
            });
            return(p);
        }
    });

    // Modmails are very similar to logs.
    Iznik.Views.ModTools.User.ModMails = Iznik.Views.ModTools.User.Logs.extend({
        template: 'modtools_user_modmails',
        addLog: function(log) {
            var self = this;

            var v = new Iznik.Views.ModTools.User.ModMailEntry({
                model: new Iznik.Model(log)
            });

            v.render().then(function(v) {
                self.$('.js-list').append(v.el);
            });
        }
    });

    Iznik.Views.ModTools.User.ModMailEntry = Iznik.View.extend({
        template: 'modtools_user_logentry',

        render: function() {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                var mom = new moment(self.model.get('timestamp'));
                self.$('.js-date').html(mom.format('DD-MMM-YY HH:mm'));

                // The log template will add logs, but highlighted.  We want to remove the highlighting for the modmail
                // display.
                self.$('div.nomargin.alert.alert-danger').removeClass('nomargin alert alert-danger');
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Member = Iznik.View.extend({
        rarelyUsed: function() {
            this.$('.js-rarelyused').fadeOut('slow');
            this.$('.js-stdmsgs li').fadeIn('slow');
        },

        addOtherInfo: function() {
            var self = this;
            var thisemail = self.model.get('email');

            require(['jquery-show-first'], function() {
                // Add any other emails
                self.$('.js-otheremails').empty();
                var promises = [];

                _.each(self.model.get('otheremails'), function (email) {
                    if (email.email != thisemail) {
                        var mod = new Iznik.Model(email);
                        var v = new Iznik.Views.ModTools.Message.OtherEmail({
                            model: mod
                        });
                        var p = v.render();
                        p.then(function(v) {
                            self.$('.js-otheremails').append(v.el);
                        });
                        promises.push(p);
                    }
                });

                Promise.all(promises).then(function() {
                    // Restrict how many we show
                    self.$('.js-otheremails').showFirst({
                        controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
                        count: 5
                    });
                });

                // Add any other group memberships we need to display.
                self.$('.js-memberof').empty();
                var promises2 = [];

                var groupids = [self.model.get('groupid')];
                _.each(self.model.get('memberof'), function (group) {
                    if (groupids.indexOf(group.id) == -1) {
                        var mod = new Iznik.Model(group);
                        var v = new Iznik.Views.ModTools.Member.Of({
                            model: mod,
                            user: self.model
                        });
                        var p = v.render();
                        p.then(function(v) {
                            self.$('.js-memberof').append(v.el);
                        });
                        promises2.push(p);

                        groupids.push(group.id);
                    }
                });

                Promise.all(promises2).then(function() {
                    self.$('.js-memberof').showFirst({
                        controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
                        count: 5
                    });
                });

                self.$('.js-applied').empty();
                var promises3 = [];

                _.each(self.model.get('applied'), function (group) {
                    if (groupids.indexOf(group.id) == -1) {
                        // Don't both displaying applications to groups we've just listed as them being a member of.
                        var mod = new Iznik.Model(group);
                        var v = new Iznik.Views.ModTools.Member.Applied({
                            model: mod
                        });
                        var p = v.render();
                        p.then(function(v) {
                            self.$('.js-applied').append(v.el);
                        });
                        promises3.push(p);
                    }
                });

                Promise.all(promises3).then(function() {
                    self.$('.js-applied').showFirst({
                        controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
                        count: 5
                    });
                });
            });
        }
    });

    Iznik.Views.ModTools.Member.OtherEmail = Iznik.View.extend({
        template: 'modtools_member_otheremail'
    });

    Iznik.Views.ModTools.Member.Of = Iznik.View.extend({
        template: 'modtools_member_of',
        
        events: {
            'click .js-remove': 'remove'
        },
    
        remove: function() {
            var self = this;

            if (self.options.user.get('systemrole') == 'User') {
                var v = new Iznik.Views.Confirm({
                    model: self.options.user
                });
                v.template = 'modtools_members_removeconfirm';

                self.listenToOnce(v, 'confirmed', function() {
                    $.ajax({
                        url: API + 'memberships',
                        type: 'DELETE',
                        data: {
                            userid: self.options.user.get('userid'),
                            groupid: self.options.user.get('groupid')
                        }, success: function(ret) {
                            if (ret.ret == 0) {
                                self.$el.fadeOut('slow');
                                self.options.user.trigger('removed');
                            }
                        }
                    });
                });

                v.render();
            }
        },
        
        render: function() {
            var self = this;
            var emails = this.options.user.get('otheremails');
            var email = _.findWhere(emails, {
                id: this.model.get('emailid')
            });

            if (email) {
                this.model.set('email', email.email);
            }

            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                if (Iznik.Session.isModeratorOf(self.model.get('groupid'))) {
                    self.$('.js-remove').removeClass('hidden');
                }
                
                self.$('.timeago').timeago();
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Member.Applied = Iznik.View.Timeago.extend({
        template: 'modtools_member_applied'
    });

    Iznik.Views.ModTools.User.Comment = Iznik.View.extend({
        template: 'modtools_user_comment',

        events: {
            'click .js-editnote': 'edit',
            'click .js-deletenote': 'deleteMe'
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
            var p = Iznik.View.Timeago.prototype.render.call(this);
            p.then(function(self) {
                var hideedit = true;
                var group = self.model.get('group');
                if (group && (group.role == 'Moderator' || group.role == 'Moderator')) {
                    // We are a mod on self group - we can modify it.
                    hideedit = false;
                }

                if (hideedit) {
                    self.$('.js-editnote, js-deletenote').hide();
                }

                self.$('.timeago').timeago();
            });
            
            return(p);
        }
    });

    Iznik.Views.ModTools.User.CommentModal = Iznik.Views.Modal.extend({
        template: 'modtools_user_commentmodal',

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

            var p = Iznik.Views.Modal.prototype.render.call(this);
            p.then(function(self) {
                // Focus on first input.  This is hard to do in bootstrap, especially, with fade, so just hack 
                // with a timer.
                window.setTimeout(function() {
                    $('#js-form input:first').focus();
                }, 2000);

                if (self.model.get('id')) {
                    // We want to refetch the model to make sure we edit the most up to date settings.
                    self.model.fetch().then(self.render2.call(self));
                } else {
                    // We're adding one; just render it.
                    self.render2();
                }
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.User.SpammerInfo = Iznik.View.Timeago.extend({
        template: 'modtools_user_spammerinfo'
    });
    
    Iznik.Views.ModTools.EnterReason = Iznik.Views.Modal.extend({
        template: 'modtools_members_spam_reason',

        events: {
            'click .js-cancel': 'close',
            'click .js-confirm': 'confirm'
        },

        confirm: function () {
            var self = this;
            var reason = self.$('.js-reason').val();

            if (reason.length < 3) {
                self.$('.js-reason').focus();
            } else {
                self.trigger('reason', reason);
                self.close();
            }
        },

        render: function () {
            var self = this;
            this.open(this.template);

            return (this);
        }
    });

    Iznik.Views.ModTools.Member.Freegle = Iznik.View.extend({
        template: 'modtools_freegle_user',

        render: function () {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function (self) {
                self.$('.js-emailfrequency').val(self.model.get('emailfrequency'));

                self.$('.datepicker').datepicker({
                    format: 'D, dd MM yyyy',
                    startDate: '0d',
                    endDate: '+30d'
                });

                var onholiday = self.model.get('onholidaytill');

                self.$(".js-switch").bootstrapSwitch({
                    onText: 'Paused',
                    offText: 'On',
                    state: onholiday != undefined
                });

                _.defer(function() {
                    self.$('select').selectpicker();
                });

                console.log("On holiday", onholiday);
                if (onholiday && onholiday != undefined && onholiday != "1970-01-01T00:00:00Z") {
                    self.$('.js-onholidaytill').show();
                    self.$('.js-emailfrequency').hide();
                    self.$('.datepicker').datepicker('setUTCDate', new Date(onholiday));
                } else {
                    self.$('.js-onholidaytill').hide();
                    self.$('.js-emailfrequency').show();
                }
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.User.FreegleMembership = Iznik.Views.ModTools.Member.Freegle.extend({
        // This view finds the appopriate group in a user, then renders that membership.
        render: function() {
            var self = this;
            var memberof = this.model.get('memberof');
            var membership = null;
            var p = resolvedPromise(self);

            _.each(memberof, function(member) {
                if (self.options.groupid == member.id) {
                    // This is the membership we're after
                    var mod = new Iznik.Model(member);
                    self.model = mod;
                    p = Iznik.Views.ModTools.Member.Freegle.prototype.render.call(self);
                }
            });

            return(p);
        }
    });
});