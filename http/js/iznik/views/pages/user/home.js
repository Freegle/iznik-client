define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/message',
    'iznik/views/pages/pages',
    'iznik/views/user/message'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Home = Iznik.Views.Page.extend({
        template: "user_home_main",

        addReplies: function() {
            var self = this;
            Iznik.Session.chats.each(function (chat) {
                if (chat.get('user1').id == Iznik.Session.get('me').id) {
                    var refmsgs = chat.get('refmsgids');

                    // Show most recent message first.
                    refmsgs = _.sortBy(refmsgs, function(msgid) {
                        return(-msgid)
                    });

                    _.each(refmsgs, function(msgid) {
                        var m = new Iznik.Models.Message({
                            id: msgid,
                            chat: chat.toJSON2()
                        });

                        m.fetch().then(function() {
                            var v = new Iznik.Views.User.Home.Reply({
                                model: m
                            });

                            v.render().then(function(v) {
                                self.$('.js-replylist').append(v.el);
                            });
                        })
                    })
                }
            });
        },

        render: function () {
            var self = this;

            // Our replies are chats which we initiated to another user.
            if (Iznik.Session.hasOwnProperty('chats')) {
                // We have the chats already.
                self.addReplies();
            } else {
                // The chats will be fetched within the page render so we must be ready for that event.
                self.listenToOnce(Iznik.Session, 'chatsfetched', function() {
                    self.addReplies();
                });
            }

            var p = Iznik.Views.Page.prototype.render.call(this, {
                noSupporters: true
            });
            p.then(function(self) {
                var v = new Iznik.Views.Help.Box();
                v.template = 'user_home_homehelp';
                v.render().then(function(v) {
                    self.$('.js-homehelp').html(v.el);
                });

                var v = new Iznik.Views.Help.Box();
                v.template = 'user_home_offerhelp';
                v.render().then(function(v) {
                    self.$('.js-offerhelp').html(v.el);
                });

                // It's quicker to get all our messages in a single call.  So we have two CollectionViews, one for offers,
                // one for wanteds.
                self.offers = new Iznik.Collection();
                self.wanteds = new Iznik.Collection();

                self.offersView = new Backbone.CollectionView({
                    el: self.$('.js-offers'),
                    modelView: Iznik.Views.User.Home.Offer,
                    modelViewOptions: {
                        offers: self.offers,
                        page: self,
                        chatid: self.options.chatid
                    },
                    collection: self.offers
                });

                self.offersView.render();

                self.wantedsView = new Backbone.CollectionView({
                    el: self.$('.js-wanteds'),
                    modelView: Iznik.Views.User.Home.Wanted,
                    modelViewOptions: {
                        wanteds: self.wanteds,
                        page: self,
                        chatid: self.options.chatid
                    },
                    collection: self.wanteds
                });

                self.wantedsView.render();

                // We want to get all messages we've sent.  From the user pov we don't distinguish in
                // how they look.  This is because most messages are approved and there's no point worrying them, and
                // provoking "why hasn't it been approved yet" complaints.
                self.messages = new Iznik.Collections.Message(null, {
                    modtools: false,
                    collection: 'Approved'
                });
                self.pendingMessages = new Iznik.Collections.Message(null, {
                    modtools: false,
                    collection: 'Pending'
                });
                self.queuedMessages = new Iznik.Collections.Message(null, {
                    modtools: false,
                    collection: 'QueuedYahooUser'
                });

                var count = 0;

                _.each([self.messages, self.pendingMessages, self.queuedMessages], function(coll) {
                    // We listen for events on the messages collection and ripple them through to the relevant offers/wanteds
                    // collection.  CollectionView will then handle rendering/removing the messages view.
                    self.listenTo(coll, 'add', function (msg) {
                        var related = msg.get('related');

                        if (msg.get('type') == 'Offer') {
                            var taken = _.where(related, {
                                type: 'Taken'
                            });

                            if (taken.length == 0) {
                                self.offers.add(msg);
                            }
                        } else if (msg.get('type') == 'Wanted') {
                            var received = _.where(related, {
                                type: 'Received'
                            });

                            if (received.length == 0) {
                                self.wanteds.add(msg);
                            }
                        }
                    });

                    self.listenTo(coll, 'remove', function (msg) {
                        if (self.model.get('type') == 'Offer') {
                            self.offers.remove(msg);
                        } else if (self.model.get('type') == 'Wanted') {
                            self.wanteds.remove(msg);
                        }
                    });

                    // Now get the messages.
                    coll.fetch({
                        data: {
                            fromuser: Iznik.Session.get('me').id,
                            types: ['Offer', 'Wanted'],
                            limit: 100
                        }
                    }).then(function () {
                        // We want both fetches to finish.
                        count++;

                        if (count == 3) {
                            if (self.offers.length == 0) {
                                self.$('.js-nooffers').fadeIn('slow');
                            } else {
                                self.$('.js-nooffers').hide();
                            }
                        }
                    });
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Home.Offer = Iznik.Views.User.Message.extend({
        template: "user_home_offer",

        events: {
            'click .js-taken': 'taken',
            'click .js-withdraw': 'withdrawn'
        },

        taken: function () {
            this.outcome('Taken');
        },

        withdrawn: function () {
            this.outcome('Withdrawn');
        },

        outcome: function (outcome) {
            var self = this;

            var v = new Iznik.Views.User.Outcome({
                model: this.model,
                outcome: outcome
            });

            self.listenToOnce(v, 'outcame', function () {
                self.$el.fadeOut('slow', function () {
                    self.destroyIt();
                });
            })

            v.render();
        }
    });

    Iznik.Views.User.Home.Wanted = Iznik.Views.User.Message.extend({
        template: "user_home_wanted"
    });

    Iznik.Views.User.Outcome = Iznik.Views.Modal.extend({
        template: 'user_home_outcome',

        events: {
            'click .js-confirm': 'confirm',
            'change .js-outcome': 'changeOutcome',
            'click .btn-radio .btn': 'click'
        },

        changeOutcome: function () {
            if (this.$('.js-outcome').val() == 'Withdrawn') {
                this.$('.js-user').addClass('reallyHide');
            } else {
                this.$('.js-user').removeClass('reallyHide');
            }
        },

        click: function (ev) {
            $('.btn-radio .btn').removeClass('active');
            var btn = $(ev.currentTarget);
            btn.addClass('active');

            if (btn.hasClass('js-unhappy')) {
                this.$('.js-public').hide();
                this.$('.js-private').fadeIn('slow');
            } else {
                this.$('.js-private').hide();
                this.$('.js-public').fadeIn('slow');
            }
        },

        confirm: function () {
            var self = this;
            var outcome = self.$('.js-outcome').val();
            var comment = self.$('.js-comment').val().trim();
            comment = comment.length > 0 ? comment : null;
            var happiness = null;
            var selbutt = self.$('.btn.active');
            var userid = self.$('.js-user').val();
            console.log("selbutt", selbutt);

            if (selbutt.length > 0) {
                if (selbutt.hasClass('js-happy')) {
                    console.log("Happy");
                    happiness = 'Happy';
                } else if (selbutt.hasClass('js-unhappy')) {
                    console.log("Unhappy");
                    happiness = 'Unhappy';
                } else {
                    console.log("Fine");
                    happiness = 'Fine';
                }
            }

            $.ajax({
                url: API + 'message/' + self.model.get('id'),
                type: 'POST',
                data: {
                    action: 'Outcome',
                    outcome: outcome,
                    happiness: happiness,
                    comment: comment,
                    userid: userid
                }, success: function (ret) {
                    if (ret.ret === 0) {
                        self.trigger('outcame');
                        self.close();
                    }
                }
            })
        },

        render: function () {
            var self = this;
            this.model.set('outcome', this.options.outcome);
            this.open(this.template);
            this.changeOutcome();

            // We want to show the people to whom it's promised first, as they're likely to be correct and most
            // likely to make the user change it if they're not correct.
            var replies = this.model.get('replies');
            replies = _.sortBy(replies, function (reply) {
                return (-reply.promised);
            });
            _.each(replies, function (reply) {
                self.$('.js-user').append('<option value="' + reply.user.id + '" />');
                self.$('.js-user option:last').html(reply.user.displayname);
            })
            self.$('.js-user').append('<option value="0">Someone else</option>');
        }
    });

    Iznik.Views.User.Home.Reply = Iznik.View.extend({
        template: "user_home_reply",

        events: {
            'click .js-chat': 'dm'
        },

        dm: function() {
            console.log("DM");
            var self = this;
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                var chatmodel = Iznik.Session.chats.get(self.model.get('chat').id);
                var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                chatView.restore();
            })
        },

        updateUnread: function() {
            var unread = 0;
            var chat = this.model.get('chat');

            if (chat.unseen > 0) {
                this.$('.js-unreadcount').html(chat.unseen);
                this.$('.js-unreadcountholder').show();
            } else {
                this.$('.js-unreadcountholder').hide();
            }
        },

        render: function() {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.$('.timeago').timeago();

                var chat = Iznik.Session.chats.get(self.model.get('chat').id);
                self.listenTo(chat, 'change:unseen', self.updateUnread);

                self.updateUnread();
            });

            return(p);
        }
    });
});