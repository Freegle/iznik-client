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
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik, autosize) {
    Iznik.Views.User.Feed = {};
    
    Iznik.Views.User.Pages.Newsfeed = Iznik.Views.Page.extend({
        template: "user_newsfeed_main",

        events: {
            'click .js-post': 'post'
        },

        post: function() {
            var self = this;

            var msg = self.$('.js-message').val();

            if (msg) {
                var mod = new Iznik.Models.Newsfeed({
                    message: msg
                });

                mod.save().then(function() {
                    self.feed.add(mod);
                });
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);

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
                
                // List invitations.
                self.feed = new Iznik.Collections.Newsfeed();

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-feed'),
                    modelView: Iznik.Views.User.Feed.Item,
                    collection: self.feed,
                    processKeyEvents: false
                });

                self.collectionView.render();
                self.feed.fetch();

                autosize(self.$('.js-message'));
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Base = Iznik.View.Timeago.extend({
        events: {
            'click .js-profile': 'showProfile'
        },

        showProfile: function() {
            var self = this;

            require([ 'iznik/views/user/user' ], function() {
                var v = new Iznik.Views.UserInfo({
                    model: new Iznik.Model(self.model.get('user'))
                });

                v.render();
            });
        }
    });

    Iznik.Views.User.Feed.Item = Iznik.Views.User.Feed.Base.extend({
        template: 'user_newsfeed_item',

        events: {
            'keyup .js-comment': 'keyUp'
        },

        keyUp: function (e) {
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
                        // mod.fetch().then(function() {
                        //     self.replies.add(mod);
                        // });
                    });
                }
            }
        },

        render: function() {
            var self = this;

            self.model.set('me', Iznik.Session.get('me'));

            var p = Iznik.View.Timeago.prototype.render.call(this, {
                model: self.model
            });

            p.then(function(self) {
                self.replies = new Iznik.Collection(self.model.get('replies'));

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-replies'),
                    modelView: Iznik.Views.User.Feed.Reply,
                    collection: self.replies,
                    processKeyEvents: false
                });

                self.collectionView.render();

                autosize(self.$('.js-comment'));
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Reply = Iznik.Views.User.Feed.Base.extend({
        template: 'user_newsfeed_reply',

        events: {
            'click .js-reply': 'reply'
        }
    })
});