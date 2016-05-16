define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/user/message'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Home = Iznik.Views.Page.extend({
        template: "user_home_main",

        render: function() {
            var self = this;

            Iznik.Views.Page.prototype.render.call(this, {
                noSupporters: true
            });

            var v = new Iznik.Views.Help.Box();
            v.template = 'user_home_offerhelp';
            this.$('.js-offerhelp').html(v.render().el);

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

            // And a collection for all the messages.
            self.messages = new Iznik.Collections.Message(null, {
                collection: 'Approved'
            });

            // We listen for events on the messages collection and ripple them through to the relevant offers/wanteds
            // collection.  CollectionView will then handle rendering/removing the messages view.
            self.listenTo(self.messages, 'add', function(msg) {
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

            self.listenTo(self.messages, 'remove', function(msg) {
                if (this.model.get('type') == 'Offer') {
                    self.offers.remove(msg);
                } else if (this.model.get('type') == 'Wanted') {
                    self.wanteds.remove(msg);
                }
            });

            // Now get the messages.
            self.messages.fetch({
                data: {
                    fromuser: Iznik.Session.get('me').id,
                    types: ['Offer', 'Wanted'],
                    limit: 100
                }
            }).then(function() {
                if (self.offers.length == 0) {
                    self.$('.js-nooffers').fadeIn('slow');
                } else {
                    self.$('.js-nooffers').hide();
                }
            });

            return(this);
        }
    });

    Iznik.Views.User.Home.Offer = Iznik.Views.User.Message.extend({
        template: "user_home_offer",

        events: {
            'click .js-taken': 'taken',
            'click .js-withdraw': 'withdrawn'
        },
        
        taken: function() {
            this.outcome('Taken');
        },
        
        withdrawn: function() {
            this.outcome('Withdrawn');
        },
        
        outcome: function(outcome) {
            var self = this;

            var v = new Iznik.Views.User.Outcome({
                model: this.model,
                outcome: outcome
            });

            self.listenToOnce(v, 'outcame', function() {
                self.$el.fadeOut('slow', function() {
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

        changeOutcome: function() {
            if (this.$('.js-outcome').val() == 'Withdrawn') {
                this.$('.js-user').addClass('reallyHide');
            } else {
                this.$('.js-user').removeClass('reallyHide');
            }
        },
        
        click: function(ev) {
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

        confirm: function() {
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
                }, success: function(ret) {
                    if (ret.ret === 0) {
                        self.trigger('outcame');
                        self.close();
                    }
                }
            })
        },

        render: function() {
            var self = this;
            this.model.set('outcome', this.options.outcome);
            console.log("Outcome is", this);
            this.open(this.template);
            this.changeOutcome();

            // We want to show the people to whom it's promised first, as they're likely to be correct and most
            // likely to make the user change it if they're not correct.
            var replies = this.model.get('replies');
            replies = _.sortBy(replies, function(reply) {
                return(- reply.promised);
            });
            _.each(replies, function(reply) {
                self.$('.js-user').append('<option value="' + reply.user.id + '" />');
                self.$('.js-user option:last').html(reply.user.displayname);
            })
            self.$('.js-user').append('<option value="0">Someone else</option>');
        }
    });
});