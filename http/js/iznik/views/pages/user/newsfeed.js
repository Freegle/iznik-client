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
            'change .js-distance': 'changeDist',
            'focus .js-message': 'autoSize'
        },

        autoSize: function() {
            // Autosize is expensive, so only do it when we focus on the input field.  That means we only do it
            // when someone is actually going to make a comment.
            var self = this;

            if (!self.autosized) {
                self.autosized = true;
                autosize(self.$('.js-message'));
            }
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

        refetch: function() {
            var self = this;
            var dist = self.$('.js-distance').val();
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

        sidebars: function() {
            var self = this;

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
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Infinite.prototype.render.call(this);

            p.then(function(self) {
                // Sticky select.
                var dist = Storage.get('newsfeeddist');
                dist = dist !== null ? dist : 'nearby';
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
                self.fetch({
                    types: [
                        'Message',
                        'CommunityEvent',
                        'VolunteerOpportunity',
                        'CentralPublicity',
                        'Alert',
                        'Story'
                    ]
                });

                // We can be asked to refetch by the first news
                self.listenTo(self.collection, 'refetch', _.bind(self.refetch, self));

                // Delay load of sidebars to give the main feed chance to load first.
                _.delay(_.bind(self.sidebars, self), 10000);
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.Newsfeed.Single = Iznik.Views.Page.extend({
        template: "user_newsfeed_single",

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);

            p.then(function(self) {
                self.model = new Iznik.Models.Newsfeed({
                    id: self.options.id
                });

                self.model.fetch({
                    success: function() {
                        var v = new Iznik.Views.User.Feed.Item({
                            model: self.model
                        });

                        v.render().then(function() {
                            self.$('.js-item').html(v.$el);
                        })
                    },
                    error: function() {
                        console.log("Error");
                        self.$('.js-error').fadeIn('slow');
                    }
                })
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Base = Iznik.View.Timeago.extend({
        events: {
            'click .js-profile': 'showProfile',
            'click .js-delete': 'deleteMe',
            'click .js-report': 'report',
            'click .js-reply': 'reply'
        },

        report: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation()

            var v = new Iznik.Views.User.Feed.Report({
                model: self.model
            });

            self.listenToOnce(v, 'reported', function() {
                self.$el.fadeOut('slow');
            });

            v.render();
        },

        deleteMe: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation()

            self.model.destroy();
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

            var p = new Promise(function(resolve, reject) {
                var v = new Iznik.Views.User.Feed.Loves({
                    model: self.model
                });

                v.template = self.lovetemplate;
                v.render().then(function() {
                    Iznik.View.Timeago.prototype.render.call(self).then(function () {
                        self.$(self.lovesel).html(v.$el);
                        resolve();

                        if (Iznik.Session.isFreegleMod()) {
                            self.$('.js-modonly').show();
                        }
                    });
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Loves = Iznik.View.extend({
        tagName: 'span',

        events: {
            'click .js-replylove': 'love',
            'click .js-itemlove': 'love',
            'click .js-replyunlove': 'unlove',
            'click .js-itemunlove': 'unlove'
        },

        love: function() {
            var self = this;
            console.log("Love");

            self.model.love().then(function() {
                self.model.fetch().then(function() {
                    self.render();
                });
            });
        },

        unlove: function() {
            var self = this;
            console.log("Unlove");

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
            'keydown .js-comment': 'sendComment',
            'focus .js-comment': 'autoSize'
        },

        autoSize: function() {
            // Autosize is expensive, so only do it when we focus on the input field.  That means we only do it
            // when someone is actually going to make a comment.
            var self = this;

            if (!self.autosized) {
                self.autosized = true;
                autosize(self.$('.js-comment'));
            }
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

        checkUpdate: function() {
            var self = this;
            // console.log("Consider update", self.model.get('id'));

            if (self.inDOM()) {
                // console.log("Newsfeed visible", self.model.get('id'), self.$el.isOnScreen());
                // Only update when we're in the viewport.
                if (self.$el.isOnScreen()) {
                    // Get the latest info to update our view.
                    self.model.fetch().then(function() {
                        // Update the loves.
                        // console.log("Update loves", self.model);
                        self.loves.model = self.model;
                        self.loves.render().then(function() {
                            self.$('.js-itemloves').html(self.loves.$el);
                        });

                        // Update the replies collection.
                        var replies = self.model.get('replies');
                        // console.log("Replies", self.replies.length, replies.length);

                        if (self.replies.length != replies.length) {
                            self.replies.add(replies);
                        }

                        if (self.model.collection.indexOf(self.model) === 0) {
                            // This is the first one.  Fetch the collection so that if there are any new items
                            // we'll pick them up.
                            self.model.collection.trigger('refetch');
                        }

                        _.delay(_.bind(self.checkUpdate, self), 30000);
                    });
                }
            }
        },

        startCheck: function() {
            var self = this;

            if (!self.checking && !self.model.get('replyto')) {
                // Check periodically for updates to this item.  We don't check replies, because they are returned
                // in the parent.
                self.checking = true;
                _.delay(_.bind(self.checkUpdate, self), 30000);
            }
        },

        render: function() {
            var self = this;

            var p = resolvedPromise();

            if (!self.rendered) {
                self.rendered = true;
                // console.log("Render", self.model.get('id')); if (self.model.get('id') == 700) { console.trace(); }

                self.model.set('me', Iznik.Session.get('me'));

                self.template = null;
                switch (self.model.get('type')) {
                    case 'Message':                  self.template = 'user_newsfeed_item'; break;
                    case 'CommunityEvent':           self.template = 'user_newsfeed_communityevent'; break;
                    case 'VolunteerOpportunity':     self.template = 'user_newsfeed_volunteering'; break;
                    case 'CentralPublicity':         self.template = 'user_newsfeed_centralpublicity'; break;
                    case 'Alert':                    self.template = 'user_newsfeed_alert'; self.model.set('sitename', $('meta[name=izniksitename]').attr("content")); break;
                    case 'Story':                    self.template = 'user_newsfeed_story'; break;
                }

                if (self.template) {
                    p = Iznik.Views.User.Feed.Base.prototype.render.call(this, {
                        model: self.model
                    });

                    p.then(function() {
                        if (self.model.get('eventid')) {
                            var v = new Iznik.Views.User.CommunityEvent({
                                model: new Iznik.Model(self.model.get('communityevent'))
                            });

                            v.render().then(function() {
                                self.$('.js-eventsumm').html(v.$el);
                            });
                        }

                        if (self.model.get('volunteeringid')) {
                            var v = new Iznik.Views.User.Volunteering({
                                model: new Iznik.Model(self.model.get('volunteering'))
                            });

                            v.render().then(function() {
                                self.$('.js-volunteeringsumm').html(v.$el);
                            });
                        }

                        self.replies = new Iznik.Collections.Replies(self.model.get('replies'));

                        self.collectionView = new Backbone.CollectionView({
                            el: self.$('.js-replies'),
                            modelView: Iznik.Views.User.Feed.Reply,
                            collection: self.replies,
                            processKeyEvents: false
                        });

                        self.collectionView.render();

                        self.loves = new Iznik.Views.User.Feed.Loves({
                            model: self.model
                        });

                        self.loves.template = self.lovetemplate;
                        self.loves.render().then(function() {
                            self.$('.js-itemloves').html(self.loves.$el);
                        });

                        // Each reply can ask us to focus on the reply box.
                        self.listenTo(self.replies, 'reply', _.bind(self.reply, self));
                        self.startCheck();
                    });
                }
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

    Iznik.Views.User.Feed.Report = Iznik.Views.Modal.extend({
        template: 'user_newsfeed_report',

        events: {
            'click .js-report': 'report'
        },

        report: function() {
            var self = this;
            var reason = self.$('.js-reason').val();

            if (reason.length > 0) {
                self.model.report(reason);
                self.trigger('reported');
            }
        }
    });
});
