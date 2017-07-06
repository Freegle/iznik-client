define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'autosize',
    'jquery-show-last',
    'iznik/models/message',
    'iznik/models/user/search',
    'iznik/models/newsfeed',
    'iznik/views/group/communityevents',
    'iznik/views/group/volunteering',
    'iznik/views/pages/pages',
    'iznik/views/infinite',
    'jquery.scrollTo'
], function($, _, Backbone, Iznik, autosize) {
    Iznik.Views.User.Feed = {};
    
    Iznik.Views.User.Pages.Newsfeed = Iznik.Views.Infinite.extend({
        template: "user_newsfeed_main",

        retField: 'newsfeed',

        events: {
            'click .js-post': 'post',
            'click .js-getloc': 'getLocation',
            'change .js-distance': 'changeDist',
            'click .js-tabevent': 'addEventInline'
        },

        getLocation: function() {
            var self = this;
            self.wait = new Iznik.Views.PleaseWait();
            self.wait.render();

            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        gotLocation: function(position) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                }, success: function(ret) {
                    if (ret.ret == 0 && ret.location) {
                        self.$('.js-nolocation').fadeOut('slow');
                        self.recordLocation(ret.location);
                        self.refetch();
                    }
                }, complete: function() {
                    if (self.wait) {
                        self.wait.close();
                    }
                }
            });
        },

        recordLocation: function(location) {
            var self = this;

            if (!_.isUndefined(location)) {
                try {
                    var l = location;
                    delete l.groupsnear;
                    Iznik.Session.setSetting('mylocation', l);
                    Storage.set('mylocation', JSON.stringify(l))
                } catch (e) {
                    console.log("Exception", e.message);
                };

                self.trigger('gotlocation', location);
            }
        },

        shownFind: false,
        shownGive: false,

        checkMessage: function() {
            var self = this;

            if (self.inDOM()) {
                var msg = self.$('.js-message').val().toLowerCase();

                if (msg.length > 0) {
                    var checks = {
                        'find': [
                            'wanted',
                            'looking for',
                            'has anybody got',
                            'has anyone got',
                            'does anyone have',
                            'i really need',
                            'if anyone has'
                        ],
                        'give': [
                            'offer',
                            'giving away',
                            'does anyone want'
                        ]
                    };

                    if (!self.shownFind) {
                        var showfind = false;

                        _.each(checks.find, function(c) {
                            if (msg.indexOf(c) !== -1) {
                                showfind = true;
                            }
                        });

                        if (showfind) {
                            self.$('.js-find').tooltip('show');
                            self.shownFind = true;
                            _.delay(_.bind(function() {
                                this.$('.js-find').tooltip('hide');
                            }, self), 10000);
                        }
                    }

                    if (!self.shownGive) {
                        var showgive = false;

                        _.each(checks.give, function(c) {
                            if (msg.indexOf(c) !== -1) {
                                showgive = true;
                            }
                        });

                        if (showgive) {
                            self.$('.js-give').tooltip('show');
                            self.shownGive = true;
                            _.delay(_.bind(function() {
                                this.$('.js-give').tooltip('hide');
                            }, self), 10000);
                        }
                    }
                }

                _.delay(_.bind(self.checkMessage, self), 1000);
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

            self.$('.js-message').prop('disabled', true);
            var msg = self.$('.js-message').val();
            msg = twemoji.replace(msg, function(emoji) {
                return '\\\\u' + twemoji.convert.toCodePoint(emoji) + '\\\\u';
            });

            if (msg) {
                var mod = new Iznik.Models.Newsfeed({
                    message: msg
                });

                mod.save().then(function() {
                    mod.fetch().then(function() {
                        self.collection.add(mod);
                        self.$('.js-message').val('');
                        self.$('.js-message').prop('disabled', false);
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

        visible: function(model) {
            var vis = model.get('visible');
            return(vis);
        },

        addEventInline: function() {
            var self = this;

            var v = new Iznik.Views.User.Feed.CommunityEvent({
                model: new Iznik.Models.CommunityEvent({})
            });

            v.render();
            self.$('#js-addevent').html(v.$el);
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Infinite.prototype.render.call(this);

            p.then(function(self) {
                if (!self.autosized) {
                    self.autosized = true;
                    autosize(self.$('.js-message'));
                }

                if (!isXS() && !isSM()) {
                    self.$('.js-message').focus();
                }

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
                    visibleModelsFilter: _.bind(self.visible, self),
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

                _.delay(_.bind(self.checkMessage, self), 1000);

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
                // We are loading something which may be the start of the thread, or a reply.  We want to load
                // the whole thread and focus on the reply.
                self.model = new Iznik.Models.Newsfeed({
                    id: self.options.id
                });

                self.model.fetch({
                    success: function() {
                        if (self.model.get('replyto')) {
                            // Notification is on a reply; render then make sure the reply is visible.
                            self.model = new Iznik.Models.Newsfeed({
                                id: self.model.get('replyto')
                            });

                            self.model.fetch({
                                success: function() {
                                    var v = new Iznik.Views.User.Feed.Item({
                                        model: self.model,
                                        highlight: self.options.id
                                    });

                                    v.render().then(function() {
                                        self.$('.js-item').html(v.$el);
                                        self.$('.js-back').fadeIn('slow');
                                    });
                                },
                                error: function() {
                                    self.$('.js-error').fadeIn('slow');
                                    self.$('.js-back').fadeIn('slow');
                                }
                            });
                        } else {
                            // Start of thread.
                            var v = new Iznik.Views.User.Feed.Item({
                                model: self.model
                            });

                            v.render().then(function() {
                                self.$('.js-item').html(v.$el);
                                self.$('.js-back').fadeIn('slow');
                            })
                        }
                    },
                    error: function() {
                        self.$('.js-error').fadeIn('slow');
                        self.$('.js-back').fadeIn('slow');
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
            'click .js-open': 'open',
            'click .js-report': 'report',
            'click .js-refertowanted': 'referToWanted',
            'click .js-preview': 'clickPreview',
            'click .js-reply': 'reply'
        },

        open: function (e) {
            var self = this;

            e.preventDefault();
            e.stopPropagation()

            var usersite = $('meta[name=iznikusersite]').attr("content");
            var url = 'https://' + usersite + '/newsfeed/' + self.model.get('id');

            window.open(url);
        },

        referToWanted: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation()

            self.model.referToWanted().then(function() {
                console.log("Referred", self);
                self.checkUpdate();
            });
        },

        clickPreview: function() {
            var p = this.model.get('preview');

            if (p && p.hasOwnProperty('url') && p.url) {
                window.open(p.url);
            }
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

            var msg = self.model.get('message');

            if (msg) {
                msg = twem(msg);
                self.model.set('message', msg);
            }

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

            p.then(function() {
                if (self.$('.js-emoji').length) {
                    var el = self.$('.js-emoji').get()[0];
                    twemoji.parse(el);
                }
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
            'click .js-itemunlove': 'unlove',
            'click .js-loves': 'lovelist'
        },

        lovelist: function() {
            var self = this;
            if (self.model.get('loves')) {
                (new Iznik.Views.User.Feed.Loves.List({
                    model: self.model
                })).render();
            }
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

    Iznik.Views.User.Feed.Loves.List = Iznik.Views.Modal.extend({
        template: 'user_newsfeed_lovelist',

        render: function() {
            var self = this;

            var p = self.model.fetch({
                data: {
                    lovelist: true
                }
            });

            p.then(function() {
                Iznik.Views.Modal.prototype.render.call(self, {
                    model: self.model
                }).then(function() {
                    self.collection = new Iznik.Collection(self.model.get('lovelist'));
                    console.log("Loves", self.model.attributes, self.collection);

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.User.Feed.Loves.List.One,
                        collection: self.collection,
                        processKeyEvents: false
                    });

                    self.collectionView.render();
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Loves.List.One = Iznik.View.extend({
        template: 'user_newsfeed_onelove'
    });

    Iznik.Views.User.Feed.Item = Iznik.Views.User.Feed.Base.extend({
        lovetemplate: 'user_newsfeed_itemloves',
        lovesel: '.js-itemloves',

        events: {
            'keydown .js-comment': 'sendComment',
            'focus .js-comment': 'autoSize',
            'click .js-addvolunteer': 'addVolunteer',
            'click .js-addevent': 'addEvent',
            'click .js-showearlier': 'showEarlier'
        },

        showAll: false,

        showEarlier: function(e) {
            var self = this;

            e.preventDefault();
            e.stopPropagation();
            self.showAll = true;
            self.$('.js-showearlier').hide();
            self.collectionView.render();
        },

        addVolunteer: function() {
            var v = new Iznik.Views.User.Volunteering.Editable({
                model: new Iznik.Models.Volunteering({})
            });
            v.render();
        },

        addEvent: function() {
            var v = new Iznik.Views.User.CommunityEvent.Editable({
                model: new Iznik.Models.CommunityEvent({})
            });
            v.render();
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
                    self.$('.js-comment').prop('disabled', true);
                    var msg = self.$('.js-comment').val();

                    if (msg.length > 0) {
                        msg = twemoji.replace(msg, function(emoji) {
                            return '\\\\u' + twemoji.convert.toCodePoint(emoji) + '\\\\u';
                        });

                        var mod = new Iznik.Models.Newsfeed({
                            replyto: self.model.get('id'),
                            message: msg
                        });

                        mod.save().then(function() {
                            self.$('.js-comment').val('');
                            self.$('.js-comment').prop('disabled', false);
                            mod.fetch().then(function() {
                                self.replies.add(mod);
                            });
                        });
                    }
                }
            }
        },

        updateTimer: false,

        checkUpdate: function() {
            var self = this;
            self.updateTimer = false;

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
                            self.loves.delegateEvents();
                        });

                        // Update the replies collection.
                        var replies = self.model.get('replies');
                        // console.log("Replies", self.replies.length, replies.length);

                        if (self.replies.length != replies.length) {
                            self.replies.add(replies);
                        }

                        if (self.model.collection && self.model.collection.indexOf(self.model) === 0) {
                            // This is the first one.  Fetch the collection so that if there are any new items
                            // we'll pick them up.
                            self.model.collection.trigger('refetch');

                            // This is the most recent one we've seen.
                            self.model.seen();
                        }

                        if (!self.updateTimer) {
                            self.updateTimer = true;
                            _.delay(_.bind(self.checkUpdate, self), 30000);
                        }
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

        visible: function(model) {
            var self = this;
            var vis = model.get('visible');

            // Show last few.
            vis = vis && (self.showAll || model.collection.length < 10 || model.collection.indexOf(model) > 10);

            return(vis);
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
                    var msg = self.model.get('message');

                    var preview = self.model.get('preview');
                    if (preview) {
                        // Don't allow previews which are too long.
                        preview.title = ellipsical(strip_tags(preview.title), 120);
                        preview.description = ellipsical(strip_tags(preview.description), 255);
                        self.model.set('preview', preview);
                    }

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

                        var replyel = self.$('.js-replies');

                        if (replyel.length) {
                            self.collectionView = new Backbone.CollectionView({
                                el: replyel,
                                modelView: Iznik.Views.User.Feed.Reply,
                                visibleModelsFilter: _.bind(self.visible, self),
                                modelViewOptions: {
                                    highlight: self.options.highlight
                                },
                                collection: self.replies,
                                processKeyEvents: false
                            });

                            self.collectionView.render();

                            if (self.replies.length > 10) {
                                self.$('.js-showearlier').show();
                            }
                        }

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
            'click .js-reply': 'reply',
            'click .js-replyprofile': 'showProfile'
        },

        reply: function() {
            this.model.collection.trigger('reply');
        },

        render: function() {
            var self = this;

            if (self.model.get('type') == 'ReferToWanted') {
                self.model.set('sitename', $('meta[name=izniksitename]').attr("content"));
                self.template = 'user_newsfeed_refertowanted';
            }

            var preview = self.model.get('preview');
            if (preview) {
                // Don't allow previews which are too long.
                preview.title = ellipsical(strip_tags(preview.title), 120);
                preview.description = ellipsical(strip_tags(preview.description), 255);
                self.model.set('preview', preview);
            }

            var p = Iznik.Views.User.Feed.Base.prototype.render.call(this, {
                model: self.model
            });

            p.then(function() {
                if (self.model.get('id') == self.options.highlight) {
                    // Make sure it's visible.
                    $(window).scrollTo(self.$el);

                    // Set the initial background colour and then fade to normal.  This draws the eye to the
                    // item we've clicked to see.
                    self.$el.addClass('highlightin');
                    _.delay(function() {
                        self.$el.addClass('highlightout');
                    }, 5000);
                }
            });

            return(p);
        }
    });

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

    Iznik.Views.User.Feed.CommunityEvent = Iznik.Views.User.CommunityEvent.Editable.extend({
        template: 'user_newsfeed_event',
        parentClass: Iznik.View,
        closeAfterSave: false,

        afterSave: function() {
            var self = this;
            (new Iznik.Views.User.CommunityEvent.Confirm()).render();
            self.$('.js-addblock').hide();
            self.$('.js-postadd').show();
            $("body").animate({ scrollTop: 0 }, "fast");
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.User.CommunityEvent.Editable.prototype.render.call(this);
            self.listenToOnce(self, 'saved', _.bind(self.afterSave, self));
        }
    });
});
