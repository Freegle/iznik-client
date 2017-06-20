define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'autosize',
    'iznik/models/message',
    'iznik/models/user/search',
    'iznik/models/newsfeed',
    'iznik/views/group/communityevents',
    'iznik/views/group/volunteering',
    'iznik/views/pages/pages',
    'iznik/views/infinite'
], function($, _, Backbone, Iznik, autosize) {
    Iznik.Views.User.Feed = {};
    
    Iznik.Views.User.Pages.Newsfeed = Iznik.Views.Infinite.extend({
        template: "user_newsfeed_main",

        retField: 'newsfeed',

        events: {
            'click .js-post': 'post',
            'change .js-distance': 'changeDist'
        },

        changeDist: function() {
            var self = this;
            var dist = self.$('.js-distance').val();
            Storage.set('newsfeeddist', dist);
            self.collection.reset();
            self.context = {
                'distance': dist
            };
            self.fetch();
        },

        post: function() {
            var self = this;

            var msg = self.$('.js-message').val();

            if (msg) {
                var mod = new Iznik.Models.Newsfeed({
                    message: msg
                });

                mod.save().then(function() {
                    mod.fetch().then(function() {
                        self.collection.add(mod);
                        self.$('.js-message').val('');
                    });
                });
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Infinite.prototype.render.call(this);

            p.then(function(self) {
                // Left menu is community events
                var v = new Iznik.Views.User.CommunityEventsSidebar();
                v.render().then(function () {
                    $('#js-eventcontainer').append(v.$el);
                });
                
                // Right menu is volunteer vacancies
                var w = new Iznik.Views.User.VolunteeringSidebar();
                w.render().then(function () {
                    $('#js-volunteeringcontainer').append(w.$el);
                });

                // Sticky select.
                var dist = Storage.get('newsfeeddist');
                console.log("Dist is", dist)
                dist = dist !== null ? dist : 32186;
                self.$('.js-distance').val(dist);

                self.context = {
                    'distance': self.$('.js-distance').val()
                };

                self.collection = new Iznik.Collections.Newsfeed();

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-feed'),
                    modelView: Iznik.Views.User.Feed.Item,
                    collection: self.collection,
                    processKeyEvents: false
                });

                self.collectionView.render();
                self.fetch();

                autosize(self.$('.js-message'));
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Base = Iznik.View.Timeago.extend({
        events: {
            'click .js-profile': 'showProfile',
            'click .js-reply': 'reply'
        },

        reply: function() {
            this.$('.js-comment').focus();
        },

        showProfile: function() {
            var self = this;

            require([ 'iznik/views/user/user' ], function() {
                var v = new Iznik.Views.UserInfo({
                    model: new Iznik.Model(self.model.get('user'))
                });

                v.render();
            });
        },

        render: function() {
            var self = this;

            p = Iznik.View.Timeago.prototype.render.call(this);
            p.then(function (self) {
                var v = new Iznik.Views.User.Feed.Loves({
                    model: self.model
                });

                v.template = self.lovetemplate;
                v.render().then(function() {
                    self.$(self.lovesel).html(v.$el);
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Loves = Iznik.View.extend({
        tagName: 'span',

        events: {
            'click .js-love': 'love',
            'click .js-unlove': 'unlove'
        },

        love: function() {
            var self = this;

            self.model.love().then(function() {
                self.model.fetch().then(function() {
                    self.render();
                });
            });
        },

        unlove: function() {
            var self = this;

            self.model.unlove().then(function() {
                self.model.fetch().then(function() {
                    self.render();
                });
            });
        }
    });

    Iznik.Views.User.Feed.Item = Iznik.Views.User.Feed.Base.extend({
        lovetemplate: 'user_newsfeed_itemloves',
        lovesel: '.js-itemloves',

        events: {
            'keydown .js-comment': 'sendComment'
        },

        sendComment: function (e) {
            var self = this;

            if (e.which === 13) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                if (e.altKey || e.shiftKey) {
                    // They've used the alt/shift trick.
                    self.$('.js-comment').val(self.$('.js-comment').val() + "\n");
                } else  {
                    var mod = new Iznik.Models.Newsfeed({
                        replyto: self.model.get('id'),
                        message: self.$('.js-comment').val()
                    });
                    
                    mod.save().then(function() {
                        self.$('.js-comment').val('');
                        mod.fetch().then(function() {
                            self.replies.add(mod);
                        });
                    });
                }
            }
        },

        render: function() {
            var self = this;

            self.model.set('me', Iznik.Session.get('me'));

            self.template = null;
            switch (self.model.get('type')) {
                case 'Message':         self.template = 'user_newsfeed_item'; break;
                case 'CommunityEvent':  self.template = 'user_newsfeed_communityevent'; break;
                case 'VolunteerOpportunity':    self.template = 'user_newsfeed_volunteering'; break;
            }

            var p = resolvedPromise();

            if (self.template) {
                p = Iznik.View.Timeago.prototype.render.call(this, {
                    model: self.model
                });

                if (self.model.get('eventid')) {
                    var v = new Iznik.Views.User.CommunityEvent({
                        model: new Iznik.Model(this.model.get('communityevent'))
                    });

                    v.render().then(function() {
                        self.$('.js-eventsumm').html(v.$el);
                    });
                }

                if (self.model.get('volunteeringid')) {
                    var v = new Iznik.Views.User.Volunteering({
                        model: new Iznik.Model(this.model.get('volunteering'))
                    });

                    v.render().then(function() {
                        self.$('.js-volunteeringsumm').html(v.$el);
                    });
                }

                p.then(function(self) {
                    self.replies = new Iznik.Collections.Replies(self.model.get('replies'));

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-replies'),
                        modelView: Iznik.Views.User.Feed.Reply,
                        collection: self.replies,
                        processKeyEvents: false
                    });

                    self.collectionView.render();

                    var v = new Iznik.Views.User.Feed.Loves({
                        model: self.model
                    });

                    v.template = self.lovetemplate;
                    v.render().then(function() {
                        self.$('.js-itemloves').html(v.$el);
                    });

                    // Each reply can ask us to focus on the reply box.
                    self.listenTo(self.replies, 'reply', _.bind(self.reply, self));

                    autosize(self.$('.js-comment'));
                });
            }

            return(p);
        }
    });

    Iznik.Views.User.Feed.Reply = Iznik.Views.User.Feed.Base.extend({
        template: 'user_newsfeed_reply',
        lovetemplate: 'user_newsfeed_replyloves',
        lovesel: '.js-replyloves',

        events: {
            'click .js-reply': 'reply'
        },

        reply: function() {
            this.model.collection.trigger('reply');
        }
    })
});
